<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Order;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('status')
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
                    \Filament\Forms\Components\Textarea::make('notes')
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
                        'changed_by' => auth('web')->id(),
                        'notes' => $data['notes'] ?? null,
                        'changed_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Order Status Updated')
                        ->body("Order {$record->order_code} status changed from {$previousStatus} to {$data['status']}")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('assign_staff')
                ->label('Assign Staff')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Select::make('pharmacist_id')
                        ->label('Assign Pharmacist')
                        ->options(\App\Models\User::role('pharmacist')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    \Filament\Forms\Components\Select::make('driver_id')
                        ->label('Assign Driver')
                        ->options(\App\Models\User::role('driver')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (Order $record, array $data): void {
                    $updates = [];
                    $messages = [];

                    if (isset($data['pharmacist_id']) && $data['pharmacist_id'] !== $record->pharmacist_id) {
                        $updates['pharmacist_id'] = $data['pharmacist_id'];
                        $pharmacist = \App\Models\User::find($data['pharmacist_id']);
                        $messages[] = "Assigned pharmacist: {$pharmacist->name}";
                    }

                    if (isset($data['driver_id']) && $data['driver_id'] !== $record->driver_id) {
                        $updates['driver_id'] = $data['driver_id'];
                        $driver = \App\Models\User::find($data['driver_id']);
                        $messages[] = "Assigned driver: {$driver->name}";
                    }

                    if (!empty($updates)) {
                        $record->update($updates);

                        \Filament\Notifications\Notification::make()
                            ->title('Staff Assignment Updated')
                            ->body(implode(', ', $messages))
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
