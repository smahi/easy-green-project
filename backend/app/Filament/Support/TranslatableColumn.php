<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\TextColumn;

trait TranslatableColumn
{
    public static function makeTranslatable(string $name): TextColumn
    {
        return TextColumn::make($name)
            ->searchable()
            ->sortable()
            ->getStateUsing(fn ($record) => $record->getTranslation($name, app()->getLocale()));
    }
}
