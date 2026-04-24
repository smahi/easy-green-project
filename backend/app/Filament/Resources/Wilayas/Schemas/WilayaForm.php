<?php

namespace App\Filament\Resources\Wilayas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WilayaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Basic Information'))
                    ->schema([
                        TextInput::make('code')
                            ->required(),
                    ]),

                Section::make(__('Translations'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name.en')
                                    ->label(__('Name (English)'))
                                    ->required(),
                                TextInput::make('name.ar')
                                    ->label(__('Name (Arabic)'))
                                    ->required(),
                                TextInput::make('name.fr')
                                    ->label(__('Name (French)'))
                                    ->required(),
                            ]),
                    ]),

                Section::make(__('Settings'))
                    ->schema([
                        Toggle::make('is_active')
                            ->required(),
                    ]),
            ]);
    }
}
