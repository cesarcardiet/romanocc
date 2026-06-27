<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleOpinion;
use App\Models\ArticleResolution;
use App\Models\ArticleVideo;
use App\Models\Law;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getHeading(): string
    {
        return 'Resumen del Sistema';
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Leyes', Law::count())
                ->description('Leyes y reglamentos registrados')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total de Artículos', Article::count())
                ->description('Artículos de leyes registrados')
                ->descriptionIcon('heroicon-m-document')
                ->color('success'),

            Stat::make('Total de Resoluciones', ArticleResolution::count())
                ->description('Resoluciones registradas')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),

            Stat::make('Total de Adiciones', ArticleOpinion::count())
                ->description('Opiniones registradas')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),

            Stat::make('Total de Videos', ArticleVideo::count())
                ->description('Videos registrados')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('danger'),

        ];
    }
} 