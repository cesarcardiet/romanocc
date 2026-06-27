<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WriteFirebaseCredentials extends Command
{
    protected $signature = 'firebase:write-credentials
                            {--force : Sobrescribir el archivo si ya existe}';

    protected $description = 'Escribe el archivo de credenciales de Firebase desde la variable de entorno FIREBASE_CREDENTIALS_BASE64 o FIREBASE_CREDENTIALS_JSON (útil en Laravel Cloud sin subida de archivos)';

    public function handle(): int
    {
        $json = $this->getCredentialsJson();
        if ($json === null) {
            $this->error('No se encontró FIREBASE_CREDENTIALS_BASE64 ni FIREBASE_CREDENTIALS_JSON en .env. Añade una de las dos con el contenido del JSON (usa BASE64 para evitar problemas con saltos de línea).');
            return self::FAILURE;
        }

        $path = storage_path('app/firebase/service-account-key.json');
        $dir = dirname($path);

        if (file_exists($path) && !$this->option('force')) {
            $this->warn("El archivo ya existe: {$path}. Usa --force para sobrescribir.");
            return self::SUCCESS;
        }

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (file_put_contents($path, $json) === false) {
            $this->error("No se pudo escribir en {$path}");
            return self::FAILURE;
        }

        $this->info("Credenciales escritas correctamente en: {$path}");
        $this->line('Asegúrate de que FIREBASE_CREDENTIALS no esté definido, o que apunte a: ' . $path);
        return self::SUCCESS;
    }

    private function getCredentialsJson(): ?string
    {
        $base64 = env('FIREBASE_CREDENTIALS_BASE64');
        if ($base64 !== null && $base64 !== '') {
            $decoded = base64_decode($base64, true);
            if ($decoded !== false && $this->isValidJson($decoded)) {
                return $decoded;
            }
            $this->error('FIREBASE_CREDENTIALS_BASE64 no es un Base64 válido o el JSON está mal formado.');
            return null;
        }

        $json = env('FIREBASE_CREDENTIALS_JSON');
        if ($json !== null && $json !== '') {
            if ($this->isValidJson($json)) {
                return $json;
            }
            $this->error('FIREBASE_CREDENTIALS_JSON no es un JSON válido.');
            return null;
        }

        return null;
    }

    private function isValidJson(string $str): bool
    {
        $decoded = json_decode($str);
        return json_last_error() === JSON_ERROR_NONE
            && isset($decoded->type, $decoded->project_id, $decoded->private_key);
    }
}
