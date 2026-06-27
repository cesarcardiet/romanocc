<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = 'Videos';

    protected static ?string $modelLabel = 'video';

    protected static ?string $pluralModelLabel = 'videos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del video')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('URL del video')
                    ->url()
                    ->required()
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creado por')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar video')
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, [
                        'user_id' => Auth::id(),
                    ]))
                    ->after(function ($record): void {
                        $record->load(['article.law']);
                        app(NotificationService::class)->sendArticleVideoCreatedNotification($record);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record): void {
                        $record->load(['article.law']);
                        app(NotificationService::class)->sendArticleVideoUpdatedNotification($record);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin videos')
            ->emptyStateDescription('Agrega enlaces a videos explicativos de este artículo.');
    }
}
