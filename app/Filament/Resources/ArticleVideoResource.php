<?php

namespace App\Filament\Resources;

use App\Models\Law;
use App\Filament\Resources\ArticleVideoResource\Pages;
use App\Filament\Resources\ArticleVideoResource\RelationManagers;
use App\Models\ArticleVideo;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleVideoResource extends Resource
{
    protected static ?string $model = ArticleVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Comentarios';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Videos';
    protected static ?string $pluralNavigationLabel = 'Videos';
    protected static ?string $pluralModelLabel = 'Videos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                # ley o reglamento
                Forms\Components\Select::make('law_id')
                    ->label('Ley o Reglamento')
                    ->options(Law::all()->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('article_id', null);
                    })
                    ->default(function ($record) {
                        return $record?->article?->law_id;
                    })
                    ->required()
                    ->placeholder('Seleccione una ley o reglamento'),
                # Articulos segun la ley seleccionada
                Forms\Components\Select::make('article_id')
                    ->label('Artículo de la Ley o Reglamento')
                    ->options(function (callable $get, $record) {
                        $lawId = $get('law_id');
                        if (!$lawId) {
                            // Si estamos editando y no hay law_id seleccionado, usar el del registro
                            if ($record && $record->article) {
                                $lawId = $record->article->law_id;
                            }
                        }
                        if (!$lawId) {
                            return [];
                        }
                        return Article::where('law_id', $lawId)->pluck('article_title', 'id');
                    })
                    ->searchable()
                    ->default(function ($record) {
                        return $record?->article_id;
                    })
                    ->required()
                    ->placeholder('Seleccione un artículo'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Video')
                    ->maxLength(255)
                    ->required()
                    ->placeholder('Ingrese el nombre del video'),
                Forms\Components\TextInput::make('url')
                    ->label('URL del Video')
                    ->url()
                    ->required()
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('article.law.name')
                    ->label('Ley o Reglamento')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('article.article_title')
                    ->label('Artículo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Video')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL del Video')
                    ->url(fn (ArticleVideo $record) => $record->url)
                    ->openUrlInNewTab()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticleVideos::route('/'),
            'create' => Pages\CreateArticleVideo::route('/create'),
            'edit' => Pages\EditArticleVideo::route('/{record}/edit'),
        ];
    }
}
