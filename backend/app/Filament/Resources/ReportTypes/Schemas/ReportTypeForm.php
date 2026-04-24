<?php

namespace App\Filament\Resources\ReportTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Basic Information'))
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
                        Grid::make(3)
                            ->schema([
                                TextInput::make('description.en')
                                    ->label(__('Description (English)')),
                                TextInput::make('description.ar')
                                    ->label(__('Description (Arabic)')),
                                TextInput::make('description.fr')
                                    ->label(__('Description (French)')),
                            ]),
                    ]),

                Section::make(__('Settings'))
                    ->columns(2)
                    ->schema([
                        FileUpload::make('icon')
                            ->image()
                            ->directory('report-types-icons'),

                        ColorPicker::make('color')
                            ->required(),

                        Select::make('severity_level')
                            ->options([
                                1 => __('Low'),
                                2 => __('Medium'),
                                3 => __('High'),
                                4 => __('Critical'),
                                5 => __('Emergency'),
                            ])
                            ->required()
                            ->default(1),

                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}
