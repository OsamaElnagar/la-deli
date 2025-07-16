<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Set email verification timestamp if not provided
        if (!isset($data['email_verified_at']) && isset($data['email'])) {
            $data['email_verified_at'] = now();
        }

        // Ensure password is hashed
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Show a success notification
        Notification::make()
            ->title('User created successfully')
            ->success()
            ->send();
    }
}
