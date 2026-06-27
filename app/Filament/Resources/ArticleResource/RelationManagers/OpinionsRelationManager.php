<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OpinionsRelationManager extends RelationManager
{
    protected static string $relationship = 'opinions';

    protected static ?string $title = 'Adiciones';

    protected static ?string $modelLabel = 'adición';

    protected static ?string $pluralModelLabel = 'adiciones';

    public function form(Form $form): Form
    {
        $disk = config('filesystems.filament_upload_disk', 'public');

        return $form
            ->schema([
                Forms\Components\RichEditor::make('opinion')
                    ->label('Contenido')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('url_file')
                    ->label('Archivo adjunto (opcional)')
                    ->disk($disk)
                    ->directory('opinions')
                    ->visibility('public')
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('opinion')
                    ->label('Contenido')
                    ->limit(60)
                    ->formatStateUsing(fn (?string $state): string => strip_tags($state ?? ''))
                    ->tooltip(fn ($record): string => strip_tags($record->opinion ?? ''))
                    ->searchable(),
                Tables\Columns\IconColumn::make('url_file')
                    ->label('Archivo')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => ! empty($record->getRawOriginal('url_file') ?? $record->url_file)),
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
                    ->label('Agregar adición')
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, [
                        'user_id' => Auth::id(),
                    ]))
                    ->after(function ($record): void {
                        $record->load(['article.law']);
                        app(NotificationService::class)->sendArticleOpinionCreatedNotification($record);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->modalHeading('Adición')
                    ->modalContent(fn ($record) => view('filament.resources.article-opinion-resource.pages.view-opinion-modal', [
                        'record' => $record,
                    ]))
                    ->form([]),
                Tables\Actions\EditAction::make()
                    ->after(function ($record): void {
                        $record->load(['article.law']);
                        app(NotificationService::class)->sendArticleOpinionUpdatedNotification($record);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin adiciones')
            ->emptyStateDescription('Publica notas o adiciones interpretativas para este artículo.');
    }
}
