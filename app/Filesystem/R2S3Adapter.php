<?php

namespace App\Filesystem;

use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\Visibility;

/**
 * Adapter S3 compatible con Cloudflare R2.
 * R2 no implementa GetObjectAcl, por lo que no podemos obtener la visibilidad de un objeto.
 * Este adapter devuelve siempre visibilidad pública al consultarla, sin llamar a la API.
 */
class R2S3Adapter extends AwsS3V3Adapter
{
    /**
     * R2 no soporta GetObjectAcl; devolver visibilidad por defecto sin llamar a la API.
     */
    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            null,           // fileSize
            Visibility::PUBLIC,
            null,           // lastModified
            null            // mimeType
        );
    }
}
