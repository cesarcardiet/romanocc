<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ResolutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'resolutions';

    protected static ?string $title = 'Resoluciones';

    protected static ?string $modelLabel = 'resolución';

    protected static ?string $pluralModelLabel = 'resoluciones';

    public function form(Form $form): Form
    {
        $disk = config('filesystems.filament_upload_disk', 'public');

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('Enlace externo')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Enlace web a la resolución (opcional si subes un archivo).'),
                Forms\Components\FileUpload::make('url_pdf')
                    ->label('Archivo PDF / documento')
                    ->disk($disk)
                    ->directory('resolutions')
                    ->visibility('public')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->downloadable()
                    ->openable()
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
                Tables\Columns\IconColumn::make('url_pdf')
                    ->label('PDF')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => ! empty($record->getRawOriginal('url_pdf') ?? $record->url_pdf)),
                Tables\Columns\TextColumn::make('url')
                    ->label('Enlace')
                    ->limit(30)
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->toggleable(),
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
                    ->label('Agregar resolución')
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, [
                        'user_id' => Auth::id(),
                    ]))
                    ->after(function ($record): void {
                        $record->load(['article.law']);
                        app(NotificationService::class)->sendArticleResolutionCreatedNotification($record);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open_pdf')
                    ->label('Abrir PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => $record->url_pdf_full)
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => ! empty($record->getRawOriginal('url_pdf'))),

                Tables\Actions\EditAction::make()
                    ->after(function ($record): void {
                        $record->load(['article.law']);
                        app(NotificationService::class)->sendArticleResolutionUpdatedNotification($record);
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin resoluciones')
            ->emptyStateDescription('Agrega resoluciones o documentos oficiales vinculados a este artículo.');
    }
}
