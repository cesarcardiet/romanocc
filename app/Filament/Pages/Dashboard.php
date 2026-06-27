<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $pluralNavigationLabel = 'Inicio';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = '';
} 