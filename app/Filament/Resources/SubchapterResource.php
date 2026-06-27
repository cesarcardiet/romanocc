<?php

namespace App\Filament\Resources;

use App\Models\Law;
use Filament\Forms;
use Filament\Tables;
use App\Models\Title;
use App\Models\Chapter;
use Filament\Forms\Form;
use App\Models\Subchapter;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SubchapterResource\Pages;
use App\Filament\Resources\SubchapterResource\RelationManagers;

class SubchapterResource extends Resource
{
    protected static ?string $model = Subchapter::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Leyes y Reglamentos';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Subcapítulos';
    protected static ?string $pluralNavigationLabel = 'Subcapítulos';
    protected static ?string $pluralModelLabel = 'Subcapítulos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                # Select de Ley o Reglamento
                Forms\Components\Select::make('law_id')
                    ->label('Ley o Reglamento')
                    ->options(Law::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('title_id', null);
                        $set('chapter_id', null);
                    }),
                # Título de la ley o reglamento seleccionado
                Forms\Components\Select::make('title_id')
                    ->label('Título de Ley o Reglamento')
                    ->options(
                        function (callable $get) {
                            $law = $get('law_id');
                            if (!$law) {
                                return [];
                            }
                            return Title::where('law_id', $law)->pluck('title', 'id');
                        }
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('chapter_id', null);
                    })
                    ->visible(fn (callable $get) => $get('law_id') !== null),
                # Capítulo seleccionado
                Forms\Components\Select::make('chapter_id')
                    ->label('Capítulo')
                    ->options(
                        function (callable $get) {
                            $law = $get('law_id');
                            $title = $get('title_id');
                            if (!$law || !$title) {
                                return [];
                            }
                            return Chapter::where('law_id', $law)
                                ->where('title_id', $title)
                                ->pluck('chapter_title', 'id');
                        }
                    )
                    ->searchable()
                    ->required()
                    ->visible(fn (callable $get) => $get('law_id') !== null && $get('title_id') !== null),
                Forms\Components\TextInput::make('subchapter_number')
                    ->label('Número de Subcapítulo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('subchapter_title')
                    ->label('Título de Subcapítulo')
                    ->required()
                    ->maxLength(255),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subchapter_title')
                    ->label('Subcapítulo')
                    ->searchable(), 
                Tables\Columns\TextColumn::make('law.name')
                    ->label('Ley o Reglamento')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('chapter.title.title')
                    ->label('Título de Ley o Reglamento')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('chapter.chapter_title')
                    ->label('Capítulo')
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('chapter_id')
                    ->label('Capítulo')
                    ->options(Chapter::all()->pluck('chapter_title', 'id'))
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
            'index' => Pages\ListSubchapters::route('/'),
            'create' => Pages\CreateSubchapter::route('/create'),
            'edit' => Pages\EditSubchapter::route('/{record}/edit'),
        ];
    }
}
