<?php

namespace App\Filament\Resources\DriverStatusResource\Pages;

use App\Filament\Resources\DriverStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDriverStatuses extends ListRecords
{
    protected static string $resource = DriverStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
