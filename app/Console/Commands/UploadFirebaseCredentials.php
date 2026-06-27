<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFirebaseCredentials extends Command
{
    protected $signature = 'firebase:upload-credentials
                            {--path= : Ruta local del JSON (por defecto storage/app/firebase/service-account-key.json)}';

    protected $description = 'Sube el archivo de credenciales de Firebase al disco configurado (FIREBASE_CREDENTIALS_DISK + FIREBASE_CREDENTIALS_STORAGE_PATH) y registra toda la respuesta en el log';

    public function handle(): int
    {
        $localPath = $this->option('path') ?? storage_path('app/firebase/service-account-key.json');

        if (! is_file($localPath)) {
            $this->error("No existe el archivo: {$localPath}");
            return self::FAILURE;
        }

        $contents = file_get_contents($localPath);
        if ($contents === false || ! $this->isValidJson($contents)) {
            $this->error('El archivo no es un JSON de credenciales de Firebase válido.');
            return self::FAILURE;
        }

        $storagePath = env('FIREBASE_CREDENTIALS_STORAGE_PATH', 'firebase/service-account-key.json');
        $diskName = env('FIREBASE_CREDENTIALS_DISK', 's3');
        $uploadUrl = env('FIREBASE_CREDENTIALS_REMOTE_URL');

        $responseData = [
            'local_path' => $localPath,
            'storage_path' => $storagePath,
            'disk' => $diskName,
            'remote_url_configured' => $uploadUrl !== null && $uploadUrl !== '',
        ];

        // Subir vía disco (S3 u otro)
        try {
            $disk = Storage::disk($diskName);

            if ($disk instanceof \Illuminate\Filesystem\AwsS3V3Adapter) {
                $client = $disk->getClient();
                $bucket = config("filesystems.disks.{$diskName}.bucket");
                $key = $storagePath;

                $result = $client->putObject([
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'Body' => $contents,
                    'ContentType' => 'application/json',
                ]);

                $responseData['method'] = 's3_putObject';
                $responseData['success'] = true;
                $responseData['aws_response'] = method_exists($result, 'toArray')
                    ? $result->toArray()
                    : [
                        'ETag' => $result->get('ETag'),
                        'VersionId' => $result->get('VersionId'),
                        'metadata' => $result->get('@metadata') ?? [],
                    ];
            } else {
                $written = $disk->put($storagePath, $contents, ['visibility' => 'private']);
                $responseData['method'] = 'storage_put';
                $responseData['success'] = $written;
                $responseData['written'] = $written;
            }
        } catch (\Throwable $e) {
            $responseData['method'] = 'storage_disk';
            $responseData['success'] = false;
            $responseData['error'] = $e->getMessage();
            $responseData['exception'] = get_class($e);
            $responseData['trace'] = $e->getTraceAsString();

            Log::error('Firebase upload credentials: error al subir al disco', $responseData);
            $this->error('Error al subir: ' . $e->getMessage());
            return self::FAILURE;
        }

        Log::info('Firebase upload credentials: respuesta completa', $responseData);

        $this->info('Respuesta registrada en el log (storage/logs/laravel.log). Resumen:');
        $this->line('  success: ' . ($responseData['success'] ? 'true' : 'false'));
        $this->line('  disk: ' . $diskName);
        $this->line('  path: ' . $storagePath);
        if (isset($responseData['aws_response'])) {
            $this->line('  ETag: ' . ($responseData['aws_response']['ETag'] ?? 'N/A'));
        }
        if (isset($responseData['error'])) {
            $this->line('  error: ' . $responseData['error']);
        }

        return $responseData['success'] ? self::SUCCESS : self::FAILURE;
    }

    private function isValidJson(string $str): bool
    {
        $decoded = json_decode($str);
        return json_last_error() === JSON_ERROR_NONE
            && isset($decoded->type, $decoded->project_id, $decoded->private_key);
    }
}
