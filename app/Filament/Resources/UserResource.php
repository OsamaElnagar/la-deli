<?php

namespace App\Filament\Resources;

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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\ExportAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->description('Basic user information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20),

                                Select::make('roles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                            ]),
                    ]),

                Section::make('Authentication')
                    ->description('User authentication details')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(
                                        fn($state) =>
                                        !empty($state) ? Hash::make($state) : null
                                    )
                                    ->dehydrated(fn($state) => filled($state))
                                    ->required(fn(string $operation): bool => $operation === 'create')
                                    ->maxLength(255),

                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->nullable(),

                                Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),

                Section::make('Address Information')
                    ->description('User address details')
                    ->icon('heroicon-o-map-pin')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address')
                                    ->maxLength(255),

                                TextInput::make('city')
                                    ->maxLength(100),

                                TextInput::make('state')
                                    ->maxLength(100),

                                TextInput::make('zip')
                                    ->maxLength(20),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn(User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=6366F1')
                    ->size(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple()
                    ->label('Filter by Role'),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->indicator('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn(User $record) => $record->update(['is_active' => true]))
                    ->visible(fn(User $record): bool => !$record->is_active && auth('web')->user()->can('update_user', User::class)),

                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn(User $record) => $record->update(['is_active' => false]))
                    ->visible(fn(User $record): bool => $record->is_active && auth('web')->user()->can('update_user', User::class)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                        ->visible(auth('web')->user()->can('update_user', User::class)),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => false]))
                        ->visible(auth('web')->user()->can('update_user', User::class)),
                ]),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\BranchesRelationManager::class,
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
