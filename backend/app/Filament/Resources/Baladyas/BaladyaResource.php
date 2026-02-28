<?php

namespace App\Filament\Resources\Baladyas;

use App\Filament\Resources\Baladyas\Pages\CreateBaladya;
use App\Filament\Resources\Baladyas\Pages\EditBaladya;
use App\Filament\Resources\Baladyas\Pages\ListBaladyas;
use App\Filament\Resources\Baladyas\Schemas\BaladyaForm;
use App\Filament\Resources\Baladyas\Tables\BaladyasTable;
use App\Models\Baladya;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BaladyaResource extends Resource
{
    protected static ?string $model = Baladya::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Baladya');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Baladyas');
    }

    public static function form(Schema $schema): Schema
    {
        return BaladyaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BaladyasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBaladyas::route('/'),
            'create' => CreateBaladya::route('/create'),
            'edit' => EditBaladya::route('/{record}/edit'),
        ];
    }
}
