<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChapterResource\Pages;
use App\Filament\Resources\ChapterResource\RelationManagers;
use App\Models\Chapter;
use App\Models\Law;
use App\Models\Title;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChapterResource extends Resource
{
    protected static ?string $model = Chapter::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Leyes y Reglamentos';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Capítulos';
    protected static ?string $pluralNavigationLabel = 'Capítulos';
    protected static ?string $pluralModelLabel = 'Capítulos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                # Ley o reglamento
                Forms\Components\Select::make('law_id')
                    ->label('Ley o Reglamento')
                    ->options(Law::all()->pluck('name', 'id'))
                    ->live()
                    ->searchable(),
                # Título de la ley o reglamento segun la seleccionada en la ley o reglamento
                Forms\Components\Select::make('title_id')
                    ->label('Título de Ley o Reglamento')
                    ->options(function (callable $get) {
                        $lawId = $get('law_id');
                        if (!$lawId) {
                            return [];
                        }
                        return Title::where('law_id', $lawId)->pluck('title', 'id');
                    })
                    ->live()
                    ->searchable(),
                # Número de capítulo segun la seleccionada en la ley o reglamento y el título de la ley o reglamento
                Forms\Components\TextInput::make('chapter_number')
                    ->label('Número de Capítulo')
                    ->required()
                    ->maxLength(255),
                # Título de capítulo segun la seleccionada en la ley o reglamento y el título de la ley o reglamento
                Forms\Components\TextInput::make('chapter_title')
                    ->label('Título de Capítulo')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('law.name')
                    ->label('Ley o Reglamento')
                    ->limit(50)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title.title')
                    ->label('Título de Ley o Reglamento')
                    ->limit(50)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('chapter_title')
                    ->label('Capítulo')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('law_id')
                    ->label('Ley o Reglamento')
                    ->options(Law::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('title_id')
                    ->label('Título de Ley o Reglamento')
                    ->options(Title::all()->pluck('title', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
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
            'index' => Pages\ListChapters::route('/'),
            'create' => Pages\CreateChapter::route('/create'),
            'edit' => Pages\EditChapter::route('/{record}/edit'),
        ];
    }
}
