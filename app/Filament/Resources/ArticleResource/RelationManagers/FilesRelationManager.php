<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Archivos adjuntos';

    protected static ?string $modelLabel = 'archivo';

    protected static ?string $pluralModelLabel = 'archivos';

    public function form(Form $form): Form
    {
        $disk = config('filesystems.filament_upload_disk', 'public');

        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Archivo')
                    ->disk($disk)
                    ->required()
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain',
                        'text/csv',
                    ])
                    ->maxSize(10240)
                    ->directory('article-files')
                    ->visibility('public')
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $disk = config('filesystems.filament_upload_disk', 'public');

        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Vista previa')
                    ->disk($disk)
                    ->visibility('public')
                    ->square()
                    ->checkFileExistence(false),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar archivo'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->file_url)
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->label('Reemplazar'),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin archivos adjuntos')
            ->emptyStateDescription('Agrega imágenes, PDFs u otros documentos relacionados con este artículo.');
    }
}
