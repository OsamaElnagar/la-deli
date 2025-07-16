<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Operations Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Information')
                    ->description('Basic order details')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order_code')
                                    ->label('Order Code')
                                    ->disabled()
                                    ->dehydrated()
                                    ->placeholder('Auto-generated')
                                    ->helperText('Automatically generated when order is created'),

                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Enter invoice number'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('source_branch_id')
                                    ->label('Source Branch')
                                    ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('delivery_type')
                                    ->label('Delivery Type')
                                    ->options([
                                        'branch_to_branch' => 'Branch to Branch',
                                        'branch_to_customer' => 'Branch to Customer',
                                        'warehouse_to_branch' => 'Warehouse to Branch',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state !== 'branch_to_customer') {
                                            $set('customer_name', null);
                                            $set('customer_phone', null);
                                            $set('customer_address', null);
                                            $set('customer_coordinates.lat', null);
                                            $set('customer_coordinates.lng', null);
                                        }
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('destination_branch_id')
                                    ->label('Destination Branch')
                                    ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn(Get $get) => $get('delivery_type') !== 'branch_to_customer'),

                                Forms\Components\Select::make('status')
                                    ->label('Order Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'assigned_pharmacist' => 'Assigned to Pharmacist',
                                        'preparing' => 'Preparing',
                                        'ready_for_pickup' => 'Ready for Pickup',
                                        'assigned_driver' => 'Assigned to Driver',
                                        'picked_up' => 'Picked Up',
                                        'in_transit' => 'In Transit',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                        'returned' => 'Returned'
                                    ])
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Order Notes')
                            ->maxLength(500)
                            ->placeholder('Enter any additional notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Customer Information')
                    ->description('Customer details for home delivery')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->maxLength(255)
                                    ->placeholder('Enter customer name')
                                    ->required(fn(Get $get) => $get('delivery_type') === 'branch_to_customer'),

                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Customer Phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('Enter customer phone number')
                                    ->required(fn(Get $get) => $get('delivery_type') === 'branch_to_customer'),
                            ]),

                        Forms\Components\Textarea::make('customer_address')
                            ->label('Customer Address')
                            ->maxLength(500)
                            ->placeholder('Enter customer address')
                            ->required(fn(Get $get) => $get('delivery_type') === 'branch_to_customer')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_coordinates.lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('e.g., 40.7128')
                                    ->helperText('Enter latitude coordinate'),

                                Forms\Components\TextInput::make('customer_coordinates.lng')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('e.g., -74.0060')
                                    ->helperText('Enter longitude coordinate'),
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('delivery_type') === 'branch_to_customer')
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Staff Assignment')
                    ->description('Assign staff to handle this order')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('pharmacist_id')
                                    ->label('Assigned Pharmacist')
                                    ->options(User::role('pharmacist')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('driver_id')
                                    ->label('Assigned Driver')
                                    ->options(User::role('driver')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DateTimePicker::make('prepared_at')
                                    ->label('Prepared At')
                                    ->placeholder('Not prepared yet'),

                                DateTimePicker::make('picked_up_at')
                                    ->label('Picked Up At')
                                    ->placeholder('Not picked up yet'),

                                DateTimePicker::make('delivered_at')
                                    ->label('Delivered At')
                                    ->placeholder('Not delivered yet'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Order Items')
                    ->description('Products included in this order')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('product_name')
                                            ->label('Product Name')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('product_code')
                                            ->label('Product Code')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->default(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->minValue(0.01)
                                            ->step(0.01),
                                    ]),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Item Notes')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['product_name'] ?? null)
                            ->addActionLabel('Add Product')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->defaultItems(0),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Documents')
                    ->description('Order related documents')
                    ->icon('heroicon-o-document')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('invoices')
                            ->label('Invoice Document')
                            ->collection('invoices')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->helperText('Upload invoice PDF or image (max 5MB)')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('delivery_proof')
                            ->label('Delivery Proof')
                            ->collection('delivery_proof')
                            ->multiple()
                            ->maxFiles(5)
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Upload delivery proof images (max 5 files, 5MB each)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_code')
                    ->label('Order Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Order code copied!')
                    ->copyMessageDuration(1500),

                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('sourceBranch.name')
                    ->label('Source')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('destinationBranch.name')
                    ->label('Destination')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Home Delivery'),

                TextColumn::make('delivery_type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'branch_to_branch' => 'Branch to Branch',
                            'branch_to_customer' => 'Branch to Customer',
                            'warehouse_to_branch' => 'Warehouse to Branch',
                            default => $state,
                        };
                    })
                    ->colors([
                        'primary' => 'branch_to_branch',
                        'success' => 'branch_to_customer',
                        'warning' => 'warehouse_to_branch',
                    ]),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'pending' => 'Pending',
                            'assigned_pharmacist' => 'Assigned to Pharmacist',
                            'preparing' => 'Preparing',
                            'ready_for_pickup' => 'Ready for Pickup',
                            'assigned_driver' => 'Assigned to Driver',
                            'picked_up' => 'Picked Up',
                            'in_transit' => 'In Transit',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                            'returned' => 'Returned',
                            default => $state,
                        };
                    })
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'assigned_pharmacist',
                        'primary' => 'preparing',
                        'warning' => 'ready_for_pickup',
                        'purple' => 'assigned_driver',
                        'indigo' => 'picked_up',
                        'blue' => 'in_transit',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                        'orange' => 'returned',
                    ])
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pharmacist.name')
                    ->label('Pharmacist')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned_pharmacist' => 'Assigned to Pharmacist',
                        'preparing' => 'Preparing',
                        'ready_for_pickup' => 'Ready for Pickup',
                        'assigned_driver' => 'Assigned to Driver',
                        'picked_up' => 'Picked Up',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'returned' => 'Returned'
                    ])
                    ->multiple(),

                SelectFilter::make('delivery_type')
                    ->label('Delivery Type')
                    ->options([
                        'branch_to_branch' => 'Branch to Branch',
                        'branch_to_customer' => 'Branch to Customer',
                        'warehouse_to_branch' => 'Warehouse to Branch',
                    ])
                    ->multiple(),

                SelectFilter::make('source_branch_id')
                    ->label('Source Branch')
                    ->relationship('sourceBranch', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('pharmacist_id')
                    ->label('Pharmacist')
                    ->relationship('pharmacist', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('driver_id')
                    ->label('Driver')
                    ->relationship('driver', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    TableAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'assigned_pharmacist' => 'Assigned to Pharmacist',
                                    'preparing' => 'Preparing',
                                    'ready_for_pickup' => 'Ready for Pickup',
                                    'assigned_driver' => 'Assigned to Driver',
                                    'picked_up' => 'Picked Up',
                                    'in_transit' => 'In Transit',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                    'returned' => 'Returned'
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Status Update Notes')
                                ->maxLength(255),
                        ])
                        ->action(function (Order $record, array $data): void {
                            $previousStatus = $record->status;
                            $record->update([
                                'status' => $data['status'],
                            ]);

                            // Update timestamps based on status
                            if ($data['status'] === 'picked_up' && !$record->picked_up_at) {
                                $record->update(['picked_up_at' => now()]);
                            } elseif ($data['status'] === 'delivered' && !$record->delivered_at) {
                                $record->update(['delivered_at' => now()]);
                            }

                            // Create status history
                            $record->statusHistories()->create([
                                'from_status' => $previousStatus,
                                'to_status' => $data['status'],
                                'changed_by' => Auth::id(),
                                'notes' => $data['notes'] ?? null,
                                'changed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Order Status Updated')
                                ->body("Order {$record->order_code} status changed from {$previousStatus} to {$data['status']}")
                                ->success()
                                ->send();
                        }),
                    TableAction::make('assign_staff')
                        ->label('Assign Staff')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('pharmacist_id')
                                ->label('Assign Pharmacist')
                                ->options(User::role('pharmacist')->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('driver_id')
                                ->label('Assign Driver')
                                ->options(User::role('driver')->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (Order $record, array $data): void {
                            $updates = [];
                            $messages = [];

                            if (isset($data['pharmacist_id']) && $data['pharmacist_id'] !== $record->pharmacist_id) {
                                $updates['pharmacist_id'] = $data['pharmacist_id'];
                                $pharmacist = User::find($data['pharmacist_id']);
                                $messages[] = "Assigned pharmacist: {$pharmacist->name}";
                            }

                            if (isset($data['driver_id']) && $data['driver_id'] !== $record->driver_id) {
                                $updates['driver_id'] = $data['driver_id'];
                                $driver = User::find($data['driver_id']);
                                $messages[] = "Assigned driver: {$driver->name}";
                            }

                            if (!empty($updates)) {
                                $record->update($updates);

                                Notification::make()
                                    ->title('Staff Assignment Updated')
                                    ->body(implode(', ', $messages))
                                    ->success()
                                    ->send();
                            }
                        }),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'assigned_pharmacist' => 'Assigned to Pharmacist',
                                    'preparing' => 'Preparing',
                                    'ready_for_pickup' => 'Ready for Pickup',
                                    'assigned_driver' => 'Assigned to Driver',
                                    'picked_up' => 'Picked Up',
                                    'in_transit' => 'In Transit',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                    'returned' => 'Returned'
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $previousStatus = $record->status;
                                $record->update(['status' => $data['status']]);

                                // Create status history
                                $record->statusHistories()->create([
                                    'from_status' => $previousStatus,
                                    'to_status' => $data['status'],
                                    'changed_by' => Auth::id(),
                                    'changed_at' => now(),
                                ]);

                                $count++;
                            }

                            Notification::make()
                                ->title('Orders Updated')
                                ->body("{$count} orders have been updated to {$data['status']}")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', '!=', 'delivered')
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('status', '!=', 'delivered')
            ->where('status', '!=', 'cancelled')
            ->count();

        return $pendingCount > 0 ? 'warning' : 'success';
    }
}
