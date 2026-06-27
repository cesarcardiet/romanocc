<?php

namespace App\Filament\Resources;

use App\Models\Law;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use App\Models\Article;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ArticleOpinion;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ArticleOpinionResource\Pages;
use App\Filament\Resources\ArticleOpinionResource\RelationManagers;

class ArticleOpinionResource extends Resource
{
    protected static ?string $model = ArticleOpinion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Comentarios';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Adiciones';
    protected static ?string $pluralNavigationLabel = 'Adiciones';
    protected static ?string $pluralModelLabel = 'Adiciones';

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
                    ->placeholder('Seleccione un artículo')
                    ->visible(fn (callable $get) => $get('law_id') !== null),
                Forms\Components\RichEditor::make('opinion')
                    ->label('Opinión')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\FileUpload::make('url_file')
                    ->label('Archivo (Opcional)')
                    ->disk(config('filesystems.filament_upload_disk', 'public'))
                    ->directory('opinions')
                    ->visibility('public')
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
                Tables\Columns\TextColumn::make('opinion')
                    ->label('Opinión')
                    ->limit(50)
                    ->formatStateUsing(fn ($state) => strip_tags($state))
                    ->tooltip(fn ($record) => strip_tags($record->opinion))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Ver Opinión')
                    ->modalContent(fn ($record) => view('filament.resources.article-opinion-resource.pages.view-opinion-modal', [
                        'record' => $record
                    ]))
                    ->form([]), // Formulario vacío para evitar campos duplicados
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
            'index' => Pages\ListArticleOpinions::route('/'),
            'create' => Pages\CreateArticleOpinion::route('/create'),
            'edit' => Pages\EditArticleOpinion::route('/{record}/edit'),
        ];
    }
}
