<?php

namespace App\Providers;

use Aws\S3\S3Client;
use App\Filesystem\R2FilesystemManager;
use App\Models\Article;
use App\Models\Chapter;
use App\Models\Law;
use App\Models\Subchapter;
use App\Models\Title;
use App\Observers\ArticleObserver;
use App\Observers\ChapterObserver;
use App\Observers\LawObserver;
use App\Observers\SubchapterObserver;
use App\Observers\TitleObserver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Usar FilesystemManager que soporta R2 (evita GetObjectAcl al subir archivos en contenido de artículos)
        $this->app->singleton('filesystem', function ($app) {
            return new R2FilesystemManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire: forzar disco local para subidas temporales. Con FILESYSTEM_DISK=s3 (R2) en el servidor,
        // Livewire usaría forS3() y createPresignedRequest(), que falla con R2. Así siempre usa la ruta
        // firmada local (forLocal()) y las subidas de Filament (Términos, etc.) funcionan.
        config(['livewire.temporary_file_upload.disk' => 'local']);

        if ($this->shouldResolveFirebaseCredentialsAtBoot()) {
            $this->resolveFirebaseCredentialsFromEnv();
        }

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Registrar observers para invalidar cache del índice automáticamente
        Law::observe(LawObserver::class);
        Title::observe(TitleObserver::class);
        Chapter::observe(ChapterObserver::class);
        Subchapter::observe(SubchapterObserver::class);
        Article::observe(ArticleObserver::class);
    }

    /**
     * Evita I/O de red (S3/HTTP) durante composer install / build (package:discover, filament:upgrade).
     */
    private function shouldResolveFirebaseCredentialsAtBoot(): bool
    {
        if (filter_var(env('SKIP_FIREBASE_BOOT', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        if (! $this->app->runningInConsole()) {
            return true;
        }

        $command = $_SERVER['argv'][1] ?? '';

        $skipCommands = [
            'package:discover',
            'filament:upgrade',
            'vendor:publish',
            'clear-compiled',
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
            'event:clear',
            'optimize:clear',
            'about',
            'list',
            'help',
        ];

        return ! in_array($command, $skipCommands, true);
    }

    /**
     * Resuelve credenciales de Firebase desde .env:
     * 0. Si el archivo por defecto no existe, intenta descargarlo desde URL o del bucket (Laravel Cloud).
     * 1. Si FIREBASE_CREDENTIALS está definido → ruta al archivo.
     * 2. Si están definidas las variables individuales → se construye el JSON.
     * 3. Si no, FIREBASE_CREDENTIALS_BASE64 o FIREBASE_CREDENTIALS_JSON.
     * 4. Si no, se usa el archivo por defecto.
     */
    private function resolveFirebaseCredentialsFromEnv(): void
    {
        $this->ensureFirebaseCredentialsFileExists();

        if (env('FIREBASE_CREDENTIALS') !== null && env('FIREBASE_CREDENTIALS') !== '') {
            $this->syncFirebaseDefaultProject();

            return;
        }

        $json = $this->getFirebaseCredentialsFromIndividualEnvVars();
        if ($json !== null) {
            $this->applyFirebaseCredentialsToProjects($json);
            $this->syncFirebaseDefaultProject($json);

            return;
        }

        $json = $this->getFirebaseCredentialsJsonFromEnv();
        if ($json !== null) {
            $this->applyFirebaseCredentialsToProjects($json);
        }

        $this->syncFirebaseDefaultProject($json);
    }

    /**
     * Si el archivo de credenciales no existe localmente (ej. Laravel Cloud), lo descarga.
     * Se intenta primero el disco cloud (FIREBASE_CREDENTIALS_STORAGE_PATH + DISK), que usa
     * credenciales AWS/R2 y funciona con buckets privados. Luego, si no hay path de disco,
     * se intenta FIREBASE_CREDENTIALS_REMOTE_URL (solo funciona si el bucket/objeto es público).
     */
    private function ensureFirebaseCredentialsFileExists(): void
    {
        $path = storage_path('app/firebase/service-account-key.json');
        if (File::exists($path)) {
            return;
        }

        $json = null;
        $storagePath = env('FIREBASE_CREDENTIALS_STORAGE_PATH');
        $storageDisk = env('FIREBASE_CREDENTIALS_DISK', 's3');
        $remoteUrl = env('FIREBASE_CREDENTIALS_REMOTE_URL');

        // Primero: disco con credenciales (funciona con bucket privado)
        if ($storagePath !== null && $storagePath !== '') {
            $json = $this->fetchFirebaseCredentialsFromStorageDisk($storagePath, $storageDisk);
        }

        // Fallback: URL pública (solo si el objeto es público; si no, devuelve Authorization error)
        if ($json === null && $remoteUrl !== null && $remoteUrl !== '') {
            try {
                $response = Http::timeout(10)->get($remoteUrl);
                if ($response->successful()) {
                    $json = $response->body();
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($json !== null && $this->isValidFirebaseCredentialsJson($json)) {
            $dir = dirname($path);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put($path, $json);
        }
    }

    /**
     * Descarga credenciales desde S3/R2/Garage con timeout corto (evita colgar el build o deploy).
     */
    private function fetchFirebaseCredentialsFromStorageDisk(string $storagePath, string $storageDisk): ?string
    {
        try {
            $diskConfig = config("filesystems.disks.{$storageDisk}");
            if (! is_array($diskConfig) || ($diskConfig['driver'] ?? '') !== 's3') {
                $disk = Storage::disk($storageDisk);
                if ($disk->exists($storagePath)) {
                    return $disk->get($storagePath) ?: null;
                }

                return null;
            }

            $s3Config = array_filter([
                'version' => 'latest',
                'region' => $diskConfig['region'] ?? 'us-east-1',
                'credentials' => array_filter([
                    'key' => $diskConfig['key'] ?? null,
                    'secret' => $diskConfig['secret'] ?? null,
                    'token' => $diskConfig['token'] ?? null,
                ]),
                'endpoint' => $diskConfig['endpoint'] ?? null,
                'use_path_style_endpoint' => $diskConfig['use_path_style_endpoint'] ?? false,
                'http' => [
                    'connect_timeout' => 5,
                    'timeout' => 10,
                ],
            ], fn ($value) => $value !== null && $value !== []);

            $client = new S3Client($s3Config);
            $bucket = $diskConfig['bucket'] ?? null;
            if ($bucket === null || $bucket === '') {
                return null;
            }

            if (! $client->doesObjectExist($bucket, $storagePath)) {
                return null;
            }

            $result = $client->getObject([
                'Bucket' => $bucket,
                'Key' => $storagePath,
            ]);

            $body = (string) ($result['Body'] ?? '');

            return $body !== '' ? $body : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function applyFirebaseCredentialsToProjects(string $json): void
    {
        $decoded = json_decode($json, true);
        if (is_array($decoded) && ! empty($decoded['project_id'])) {
            $this->registerFirebaseProjectIfMissing($decoded['project_id']);
        }

        $projects = config('firebase.projects', []);
        foreach (array_keys($projects) as $name) {
            config(["firebase.projects.{$name}.credentials" => $json]);
        }
    }

    /**
     * Alinea firebase.default con el project_id del JSON (cuenta de servicio).
     * Evita "Firebase project [romanocc-4114f] not configured" cuando FIREBASE_PROJECT
     * en .env no coincide con las claves en config/firebase.php.
     */
    private function syncFirebaseDefaultProject(?string $credentialsJson = null): void
    {
        $projects = config('firebase.projects', []);
        if ($projects === []) {
            return;
        }

        $templateKey = array_key_first($projects);
        $projectId = $this->resolveFirebaseProjectId($credentialsJson);

        if ($projectId !== null && $projectId !== '') {
            $this->registerFirebaseProjectIfMissing($projectId);
        } else {
            $projectId = $templateKey;
        }

        if (! isset(config('firebase.projects')[$projectId])) {
            $projectId = $templateKey;
        }

        config(['firebase.default' => $projectId]);
    }

    private function resolveFirebaseProjectId(?string $credentialsJson = null): ?string
    {
        if ($credentialsJson !== null) {
            $decoded = json_decode($credentialsJson, true);
            if (is_array($decoded) && ! empty($decoded['project_id'])) {
                return (string) $decoded['project_id'];
            }
        }

        $fromFile = $this->readProjectIdFromCredentialsFile();
        if ($fromFile !== null) {
            return $fromFile;
        }

        $fromEnv = env('FIREBASE_PROJECT_ID') ?: env('FIREBASE_PROJECT');
        if ($fromEnv !== null && $fromEnv !== '') {
            return (string) $fromEnv;
        }

        return null;
    }

    private function readProjectIdFromCredentialsFile(): ?string
    {
        $path = env('FIREBASE_CREDENTIALS');
        if ($path === null || $path === '') {
            $path = storage_path('app/firebase/service-account-key.json');
        }

        if (! is_string($path) || str_starts_with(trim($path), '{')) {
            return null;
        }

        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) && ! empty($decoded['project_id'])
            ? (string) $decoded['project_id']
            : null;
    }

    private function registerFirebaseProjectIfMissing(string $projectId): void
    {
        $projects = config('firebase.projects', []);

        if (isset($projects[$projectId])) {
            return;
        }

        $templateKey = array_key_first($projects);
        if ($templateKey === null) {
            return;
        }

        $projects[$projectId] = $projects[$templateKey];
        config(['firebase.projects' => $projects]);
    }

    /**
     * Construye el JSON de credenciales desde variables individuales del .env.
     * Necesarias: FIREBASE_PROJECT, FIREBASE_KEY_ID, FIREBASE_PRIVATE_KEY, FIREBASE_CLIENT_EMAIL.
     */
    private function getFirebaseCredentialsFromIndividualEnvVars(): ?string
    {
        $projectId = trim((string) env('FIREBASE_PROJECT', ''));
        $privateKeyId = trim((string) env('FIREBASE_KEY_ID', ''));
        $privateKey = env('FIREBASE_PRIVATE_KEY');
        $clientEmail = trim((string) env('FIREBASE_CLIENT_EMAIL', ''));

        if ($projectId === '' || $privateKeyId === '' || $clientEmail === '' || $privateKey === null || $privateKey === '') {
            return null;
        }

        $privateKey = trim((string) $privateKey);
        // En .env la clave puede ir con \n literal (backslash+n); el SDK necesita saltos de línea reales
        $privateKey = str_replace('\\n', "\n", $privateKey);
        // Por si el .env interpreta \n y ya hay newlines pero con \r\n
        $privateKey = str_replace("\r\n", "\n", $privateKey);

        $credentials = [
            'type' => 'service_account',
            'project_id' => $projectId,
            'private_key_id' => $privateKeyId,
            'private_key' => $privateKey,
            'client_email' => $clientEmail,
            'client_id' => env('FIREBASE_CLIENT_ID', ''),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/'.rawurlencode($clientEmail),
            'universe_domain' => 'googleapis.com',
        ];

        $json = json_encode($credentials);
        if ($json === false) {
            return null;
        }

        return $json;
    }

    private function getFirebaseCredentialsJsonFromEnv(): ?string
    {
        $base64 = env('FIREBASE_CREDENTIALS_BASE64');
        if ($base64 !== null && $base64 !== '') {
            $decoded = base64_decode($base64, true);
            if ($decoded !== false && $this->isValidFirebaseCredentialsJson($decoded)) {
                return $decoded;
            }
            return null;
        }

        $json = env('FIREBASE_CREDENTIALS_JSON');
        if ($json !== null && $json !== '' && $this->isValidFirebaseCredentialsJson($json)) {
            return $json;
        }

        return null;
    }

    private function isValidFirebaseCredentialsJson(string $str): bool
    {
        $decoded = json_decode($str);
        return json_last_error() === JSON_ERROR_NONE
            && isset($decoded->type, $decoded->project_id, $decoded->private_key);
    }
}
