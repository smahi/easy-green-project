<?php

namespace App\Filament\Resources\Baladyas\Pages;

use App\Filament\Resources\Baladyas\BaladyaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBaladya extends EditRecord
{
    protected static string $resource = BaladyaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
