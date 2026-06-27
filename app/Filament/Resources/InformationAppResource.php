<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InformationAppResource\Pages;
use App\Filament\Resources\InformationAppResource\RelationManagers;
use App\Models\InformationApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InformationAppResource extends Resource
{
    protected static ?string $model = InformationApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Información de la App';
    protected static ?string $pluralNavigationLabel = 'Información de la App';
    protected static ?string $pluralModelLabel = 'Información de la App';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('url_terminos_y_condiciones')
                    ->label('Cargar Archivo de los Términos y Condiciones')
                    ->disk(config('filesystems.filament_upload_disk', 'public'))
                    ->directory('information_apps')
                    ->visibility('public')
                    ->required()
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('url_politica_de_privacidad')
                    ->label('Cargar Archivo de la Política de Privacidad')
                    ->disk(config('filesystems.filament_upload_disk', 'public'))
                    ->directory('information_apps')
                    ->visibility('public')
                    ->required()
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url_terminos_y_condiciones')
                    ->label('Términos y Condiciones')
                    ->url(fn (InformationApp $record) => $record->url_terminos_y_condiciones)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('url_politica_de_privacidad')
                    ->label('Política de Privacidad')
                    ->url(fn (InformationApp $record) => $record->url_politica_de_privacidad)
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListInformationApps::route('/'),
            'create' => Pages\CreateInformationApp::route('/create'),
            'edit' => Pages\EditInformationApp::route('/{record}/edit'),
        ];
    }
}
