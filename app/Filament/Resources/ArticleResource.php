<?php

namespace App\Filament\Resources;

use App\Models\Law;
use App\Models\Title;
use App\Models\Chapter;
use App\Models\Subchapter;
use Filament\Forms;
use Filament\Tables;
use App\Models\Article;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ArticleResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ArticleResource\RelationManagers;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationGroup = 'Leyes y Reglamentos';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Artículos';
    protected static ?string $pluralNavigationLabel = 'Artículos';
    protected static ?string $pluralModelLabel = 'Artículos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                # Ley o reglamento
                Forms\Components\Select::make('law_id')
                    ->label('Ley o Reglamento')
                    ->options(Law::all()->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => null),
                
                # Título de la ley o reglamento
                Forms\Components\Select::make('title_id')
                    ->label('Título')
                    ->options(function (callable $get) {
                        $lawId = $get('law_id');
                        if (!$lawId) {
                            return [];
                        }
                        return Title::where('law_id', $lawId)->pluck('title', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => null)
                    ->visible(fn (callable $get) => $get('law_id') !== null),
                
                # Capítulo de la ley o reglamento
                Forms\Components\Select::make('chapter_id')
                    ->label('Capítulo')
                    ->options(function (callable $get) {
                        $lawId = $get('law_id');
                        $titleId = $get('title_id');
                        if (!$lawId || !$titleId) {
                            return [];
                        }
                        return Chapter::where('law_id', $lawId)
                            ->where('title_id', $titleId)
                            ->pluck('chapter_title', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => null)
                    ->visible(fn (callable $get) => $get('law_id') !== null && $get('title_id') !== null),
                
                # Subcapítulo de la ley o reglamento
                Forms\Components\Select::make('subchapter_id')
                    ->label('Subcapítulo')
                    ->options(function (callable $get) {
                        $lawId = $get('law_id');
                        $chapterId = $get('chapter_id');
                        if (!$lawId || !$chapterId) {
                            return [];
                        }
                        return Subchapter::where('law_id', $lawId)
                            ->where('chapter_id', $chapterId)
                            ->pluck('subchapter_title', 'id');
                    })
                    ->searchable()
                    ->visible(fn (callable $get) => $get('law_id') !== null && $get('title_id') !== null && $get('chapter_id') !== null),
                Forms\Components\TextInput::make('article_number')
                    ->label('Número de Artículo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('article_title')
                    ->label('Título de Artículo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('article_content')
                    ->label('Contenido de Artículo')
                    ->required()
                    ->columnSpanFull(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('article_title')
                    ->label('Título de Artículo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('law.name')
                    ->label('Ley')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title.title')
                    ->label('Título')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chapter_id')
                    ->label('Capítulo')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->chapter ? $record->chapter->chapter_title : 'No aplica';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('subchapter_id')
                    ->label('Subcapítulo')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->subchapter ? $record->subchapter->subchapter_title : 'No aplica';
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('files_count')
                    ->label('Archivos')
                    ->counts('files')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} archivo(s)" : 'Sin archivos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('videos_count')
                    ->label('Videos')
                    ->counts('videos')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('opinions_count')
                    ->label('Adiciones')
                    ->counts('opinions')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('resolutions_count')
                    ->label('Resoluciones')
                    ->counts('resolutions')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray')
                    ->toggleable(),
                
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
            RelationManagers\FilesRelationManager::class,
            RelationManagers\VideosRelationManager::class,
            RelationManagers\OpinionsRelationManager::class,
            RelationManagers\ResolutionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
