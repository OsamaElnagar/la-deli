<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the creator ID to the current user
        $data['created_by'] = Auth::id();

        // If delivery type is not branch_to_customer, clear customer fields
        if ($data['delivery_type'] !== 'branch_to_customer') {
            $data['customer_name'] = null;
            $data['customer_phone'] = null;
            $data['customer_address'] = null;
            $data['customer_coordinates'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
