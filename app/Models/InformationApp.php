<?php

namespace App\Models;

use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class InformationApp extends Model
{
    protected $fillable = [
        'url_terminos_y_condiciones',
        'url_politica_de_privacidad',
    ];

    protected function urlTerminosYCondiciones(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => StorageUrl::forPath($value),
        );
    }

    protected function urlPoliticaDePrivacidad(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => StorageUrl::forPath($value),
        );
    }
}
