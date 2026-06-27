<?php

namespace App\Filament\Widgets;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getHeading(): string
    {
        return 'Estadísticas de Usuarios';
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Usuarios', User::count())
                ->description('Todos los usuarios registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Usuarios Activos', User::where('status', UserStatus::ACTIVE)->count())
                ->description('Usuarios con estatus activo')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Usuarios Inactivos', User::where('status', UserStatus::INACTIVE)->count())
                ->description('Usuarios con estatus inactivo')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('gray'),

            // Stat::make('Administradores', User::where('type', UserType::ADMIN)->count())
            //     ->description('Usuarios administradores')
            //     ->descriptionIcon('heroicon-m-shield-check')
            //     ->color('danger'),

            // Stat::make('Abogados', User::where('type', UserType::LAWYER)->count())
            //     ->description('Usuarios abogados')
            //     ->descriptionIcon('heroicon-m-academic-cap')
            //     ->color('primary'),

            // Stat::make('Estudiantes', User::where('type', UserType::STUDENT)->count())
            //     ->description('Usuarios estudiantes')
            //     ->descriptionIcon('heroicon-m-academic-cap')
            //     ->color('success'),
        ];
    }
} 