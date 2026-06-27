<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadFirebaseCredentials extends Command
{
    protected $signature = 'firebase:download-credentials
                            {--path= : Ruta local de destino (por defecto storage/app/firebase/service-account-key.json)}
                            {--force : Sobrescribir si el archivo local ya existe}';

    protected $description = 'Descarga el JSON de credenciales de Firebase desde el disco configurado (FIREBASE_CREDENTIALS_DISK + FIREBASE_CREDENTIALS_STORAGE_PATH) o FIREBASE_CREDENTIALS_REMOTE_URL';

    public function handle(): int
    {
        $localPath = $this->option('path') ?? storage_path('app/firebase/service-account-key.json');

        if (File::exists($localPath) && ! $this->option('force')) {
            $this->warn("El archivo ya existe: {$localPath}. Usa --force para sobrescribir.");
            return self::SUCCESS;
        }

        $json = $this->fetchFromStorageDisk();
        if ($json === null) {
            $json = $this->fetchFromRemoteUrl();
        }

        if ($json === null) {
            $this->error('No se pudo obtener el archivo de credenciales.');
            $this->line('');
            $this->line('Opciones:');
            $this->line('  1. Sube el JSON al bucket: php artisan firebase:upload-credentials');
            $this->line('  2. O define FIREBASE_CREDENTIALS_BASE64 y ejecuta: php artisan firebase:write-credentials');
            $this->line('  3. O define FIREBASE_CREDENTIALS_REMOTE_URL con una URL pública al JSON');
            $this->line('');
            $this->line('Variables actuales:');
            $this->line('  FIREBASE_CREDENTIALS_DISK=' . (env('FIREBASE_CREDENTIALS_DISK') ?: '(no definido)'));
            $this->line('  FIREBASE_CREDENTIALS_STORAGE_PATH=' . (env('FIREBASE_CREDENTIALS_STORAGE_PATH') ?: '(no definido)'));
            $this->line('  FIREBASE_CREDENTIALS_REMOTE_URL=' . (env('FIREBASE_CREDENTIALS_REMOTE_URL') ?: '(no definido)'));

            return self::FAILURE;
        }

        if (! $this->isValidJson($json)) {
            $this->error('El contenido descargado no es un JSON de credenciales de Firebase válido.');
            return self::FAILURE;
        }

        $dir = dirname($localPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (File::put($localPath, $json) === false) {
            $this->error("No se pudo escribir en: {$localPath}");
            return self::FAILURE;
        }

        $this->info("Credenciales descargadas correctamente en: {$localPath}");
        $this->printCredentialsSummary($json);

        return self::SUCCESS;
    }

    private function fetchFromStorageDisk(): ?string
    {
        $storagePath = env('FIREBASE_CREDENTIALS_STORAGE_PATH');
        $diskName = env('FIREBASE_CREDENTIALS_DISK', 's3');

        if ($storagePath === null || $storagePath === '') {
            $this->line('FIREBASE_CREDENTIALS_STORAGE_PATH no está definido; se omite descarga desde disco.');
            return null;
        }

        try {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($storagePath)) {
                $this->warn("No existe en el disco «{$diskName}»: {$storagePath}");
                return null;
            }

            $json = $disk->get($storagePath);
            $this->line("Descargado desde disco «{$diskName}»: {$storagePath}");

            return $json !== false && $json !== '' ? $json : null;
        } catch (\Throwable $e) {
            $this->error('Error al leer del disco: ' . $e->getMessage());
            report($e);

            return null;
        }
    }

    private function fetchFromRemoteUrl(): ?string
    {
        $remoteUrl = env('FIREBASE_CREDENTIALS_REMOTE_URL');

        if ($remoteUrl === null || $remoteUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($remoteUrl);

            if (! $response->successful()) {
                $this->warn("FIREBASE_CREDENTIALS_REMOTE_URL respondió HTTP {$response->status()}");
                return null;
            }

            $this->line('Descargado desde URL: ' . $remoteUrl);

            return $response->body();
        } catch (\Throwable $e) {
            $this->error('Error al descargar desde URL: ' . $e->getMessage());
            report($e);

            return null;
        }
    }

    private function isValidJson(string $str): bool
    {
        $decoded = json_decode($str);

        return json_last_error() === JSON_ERROR_NONE
            && isset($decoded->type, $decoded->project_id, $decoded->private_key);
    }

    private function printCredentialsSummary(string $json): void
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || empty($decoded['project_id'])) {
            return;
        }

        $projectId = (string) $decoded['project_id'];
        $expected = env('FIREBASE_PROJECT_ID') ?: env('FIREBASE_PROJECT');
        $senderId = env('FIREBASE_SENDER_ID');

        $this->line('');
        $this->line('  project_id (JSON): ' . $projectId);
        if ($expected !== null && $expected !== '') {
            $this->line('  FIREBASE_PROJECT_ID (.env): ' . $expected);
            if ($projectId !== $expected) {
                $this->warn('  El project_id del JSON no coincide con FIREBASE_PROJECT_ID. FCM fallará o dará SenderId mismatch.');
            } else {
                $this->info('  project_id coincide con .env.');
            }
        }
        if ($senderId !== null && $senderId !== '') {
            $this->line('  FIREBASE_SENDER_ID (.env): ' . $senderId);
            $this->line('  (Debe ser el mismo número que en google-services.json → project_number)');
        }
    }
}
