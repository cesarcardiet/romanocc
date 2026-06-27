<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $pluralNavigationLabel = 'Usuarios';
    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese el nombre completo'),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('usuario@ejemplo.com'),
                    ])->columns(2),

                Forms\Components\Section::make('Tipo y Estatus')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Usuario')
                            ->options(UserType::options())
                            ->required()
                            ->default(UserType::PUBLIC)
                            ->placeholder('Seleccione el tipo de usuario'),
                        Forms\Components\Select::make('status')
                            ->label('Estatus')
                            ->options(UserStatus::options())
                            ->required()
                            ->default(UserStatus::ACTIVE)
                            ->placeholder('Seleccione el estatus'),
                    ])->columns(2),

                Forms\Components\Section::make('Seguridad')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->placeholder('Mínimo 8 caracteres'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->same('password')
                            ->dehydrated(false)
                            ->placeholder('Confirme la contraseña'),
                    ])->columns(2),

                Forms\Components\Section::make('Verificación')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verificado')
                            ->placeholder('Seleccione fecha de verificación'),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                // mostrar el telefono si tiene valor, sino mostrar el correo si tiene valor
                Tables\Columns\TextColumn::make('contact_info')
                    ->label('Email o Teléfono')
                    ->getStateUsing(fn ($record) => $record->phone ?: ($record->email ?: '-'))
                    ->searchable(query: function ($query, $search) {
                        return $query->where('phone', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (UserType $state): string => $state->label())
                    ->color(fn (UserType $state): string => $state->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => $state->color())
                    ->icon(fn (UserStatus $state): string => $state->icon())
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Usuario')
                    ->options(UserType::options()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options(UserStatus::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (User $record): string => $record->status === UserStatus::ACTIVE ? 'Desactivar' : 'Activar')
                    ->icon(fn (User $record): string => $record->status === UserStatus::ACTIVE ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->status === UserStatus::ACTIVE ? 'danger' : 'success')
                    ->action(function (User $record) {
                        $record->update([
                            'status' => $record->status === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar Usuarios')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => UserStatus::ACTIVE]);
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar Usuarios')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => UserStatus::INACTIVE]);
                            });
                        })
                        ->requiresConfirmation(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
