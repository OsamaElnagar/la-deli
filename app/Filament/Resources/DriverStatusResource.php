<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverStatusResource\Pages;
use App\Models\DriverStatus;
use App\Models\User;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Carbon\Carbon;

class DriverStatusResource extends Resource
{
    protected static ?string $model = DriverStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Operations Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Driver Status';

    protected static ?string $pluralModelLabel = 'Driver Statuses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Driver Information')
                    ->description('Driver details and current status')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('driver_id')
                                    ->label('Driver')
                                    ->options(User::role('driver')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'online' => 'Online',
                                        'offline' => 'Offline',
                                        'busy' => 'Busy',
                                        'on_break' => 'On Break',
                                    ])
                                    ->required()
                                    ->default('offline')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(false),

                Section::make('Current Assignment')
                    ->description('Current order and location information')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('current_order_id')
                                    ->label('Current Order')
                                    ->options(Order::whereIn('status', ['assigned_driver', 'picked_up', 'in_transit'])
                                        ->pluck('order_code', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\DateTimePicker::make('last_location_update')
                                    ->label('Last Location Update')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('current_location.lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('e.g., 40.7128')
                                    ->helperText('Enter latitude coordinate'),

                                Forms\Components\TextInput::make('current_location.lng')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('e.g., -74.0060')
                                    ->helperText('Enter longitude coordinate'),
                            ]),

                        Placeholder::make('map_placeholder')
                            ->label('Location Map')
                            ->content('Map integration will be available in future updates.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('driver.name')
                    ->label('Driver Name')
                    ->searchable()
                    ->sortable(),

                    TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    // ->enum([
                    //     'online' => 'Online',
                    //     'offline' => 'Offline',
                    //     'busy' => 'Busy',
                    //     'on_break' => 'On Break',
                    // ])
                    ->colors([
                        'success' => 'online',
                        'danger' => 'offline',
                        'warning' => 'busy',
                        'gray' => 'on_break',
                    ])
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currentOrder.order_code')
                    ->label('Current Order')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No Active Order'),

                TextColumn::make('last_location_update')
                    ->label('Last Location Update')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Never updated';

                        $date = Carbon::parse($state);
                        $now = Carbon::now();
                        $diffInMinutes = $date->diffInMinutes($now);

                        if ($diffInMinutes < 60) {
                            return $diffInMinutes . ' min ago';
                        } else if ($date->isToday()) {
                            return 'Today at ' . $date->format('g:i A');
                        } else {
                            return $date->format('M j, Y g:i A');
                        }
                    }),

                IconColumn::make('has_recent_location')
                    ->label('Location')
                    ->boolean()
                    ->getStateUsing(function (DriverStatus $record): bool {
                        if (!$record->last_location_update) return false;

                        $lastUpdate = Carbon::parse($record->last_location_update);
                        $now = Carbon::now();

                        return $lastUpdate->diffInMinutes($now) < 30;
                    })
                    ->trueIcon('heroicon-o-map-pin')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
                SelectFilter::make('status')
                    ->label('Driver Status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'busy' => 'Busy',
                        'on_break' => 'On Break',
                    ])
                    ->multiple(),

                SelectFilter::make('has_active_order')
                    ->label('Active Order')
                    ->options([
                        'yes' => 'Has Active Order',
                        'no' => 'No Active Order',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query->when(
                            $data['value'] === 'yes',
                            fn (Builder $query): Builder => $query->whereNotNull('current_order_id'),
                            fn (Builder $query): Builder => $query->whereNull('current_order_id'),
                        );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('set_online')
                        ->label('Set Online')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (DriverStatus $record) => $record->status !== 'online')
                        ->action(function (DriverStatus $record) {
                            $record->update(['status' => 'online']);

                            Notification::make()
                                ->title('Driver Status Updated')
                                ->body("Driver {$record->driver->name} is now online")
                                ->success()
                                ->send();
                        }),
                    Action::make('set_offline')
                        ->label('Set Offline')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (DriverStatus $record) => $record->status !== 'offline')
                        ->action(function (DriverStatus $record) {
                            $record->update(['status' => 'offline']);

                            Notification::make()
                                ->title('Driver Status Updated')
                                ->body("Driver {$record->driver->name} is now offline")
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('set_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'online' => 'Online',
                                    'offline' => 'Offline',
                                    'busy' => 'Busy',
                                    'on_break' => 'On Break',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                                $count++;
                            }

                            Notification::make()
                                ->title('Driver Statuses Updated')
                                ->body("{$count} drivers have been updated to {$data['status']}")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
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
            'index' => Pages\ListDriverStatuses::route('/'),
            'create' => Pages\CreateDriverStatus::route('/create'),
            'edit' => Pages\EditDriverStatus::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'online')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $onlineCount = static::getModel()::where('status', 'online')->count();

        return $onlineCount > 0 ? 'success' : 'danger';
    }
}
