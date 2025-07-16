<?php

namespace App\Filament\Resources\DriverStatusResource\Pages;

use App\Filament\Resources\DriverStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriverStatus extends EditRecord
{
    protected static string $resource = DriverStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
