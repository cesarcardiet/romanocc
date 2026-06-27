<?php

namespace App\Filesystem;

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter as LaravelAwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemManager;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as LeagueS3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Visibility;

/**
 * Gestor de filesystem que usa R2S3Adapter cuando el disco S3 apunta a Cloudflare R2,
 * para evitar GetObjectAcl (no implementado en R2) al obtener visibilidad de archivos.
 */
class R2FilesystemManager extends FilesystemManager
{
    /**
     * Crear el driver S3; si el endpoint es R2, usar adapter que no llama a GetObjectAcl.
     */
    public function createS3Driver(array $config)
    {
        $s3Config = $this->formatS3Config($config);

        $root = (string) ($s3Config['root'] ?? '');

        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );

        $streamReads = $s3Config['stream_reads'] ?? false;

        $client = new S3Client($s3Config);

        $endpoint = $config['endpoint'] ?? '';
        $isR2 = is_string($endpoint) && str_contains($endpoint, 'r2.cloudflarestorage.com');

        $adapter = $isR2
            ? new R2S3Adapter($client, $s3Config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads)
            : new LeagueS3Adapter($client, $s3Config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);

        return new LaravelAwsS3V3Adapter(
            $this->createFlysystem($adapter, $config),
            $adapter,
            $s3Config,
            $client
        );
    }
}
