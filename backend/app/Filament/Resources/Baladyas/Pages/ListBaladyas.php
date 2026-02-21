<?php

namespace App\Filament\Resources\Baladyas\Pages;

use App\Filament\Resources\Baladyas\BaladyaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaladyas extends ListRecords
{
    protected static string $resource = BaladyaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
