<?php

namespace App\Filament\Resources\ReportTypes\Pages;

use App\Filament\Resources\ReportTypes\ReportTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportType extends EditRecord
{
    protected static string $resource = ReportTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
