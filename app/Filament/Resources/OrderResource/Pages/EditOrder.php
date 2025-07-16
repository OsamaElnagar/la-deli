<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\Order;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make('view')
                ->label('View Details')
                ->icon('heroicon-o-eye')
                ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),

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

                    Notification::make()
                        ->title('Order Status Updated')
                        ->body("Order {$record->order_code} status changed from {$previousStatus} to {$data['status']}")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If delivery type is not branch_to_customer, clear customer fields
        if ($data['delivery_type'] !== 'branch_to_customer') {
            $data['customer_name'] = null;
            $data['customer_phone'] = null;
            $data['customer_address'] = null;
            $data['customer_coordinates'] = null;
        }

        return $data;
    }
}
