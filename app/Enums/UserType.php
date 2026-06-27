<?php

namespace App\Enums;

enum UserType: string
{
    case ADMIN = 'admin';
    case PUBLIC = 'public';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrador',
            self::PUBLIC => 'Público',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ADMIN => 'primary',
            self::PUBLIC => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label()
        ])->toArray();
    }
} 