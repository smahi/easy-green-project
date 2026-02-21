<?php

namespace App\Filament\Resources\ReportTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
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
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name.en')
                                    ->label('Name (English)')
                                    ->required(),
                                TextInput::make('name.ar')
                                    ->label('Name (Arabic)')
                                    ->required(),
                                TextInput::make('name.fr')
                                    ->label('Name (French)')
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('description.en')
                                    ->label('Description (English)'),
                                TextInput::make('description.ar')
                                    ->label('Description (Arabic)'),
                                TextInput::make('description.fr')
                                    ->label('Description (French)'),
                            ]),
                    ]),
                
                Section::make('Settings')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('icon')
                            ->image()
                            ->directory('report-types-icons'),
                        
                        ColorPicker::make('color')
                            ->required(),
                            
                        Select::make('severity_level')
                            ->options([
                                1 => 'Low',
                                2 => 'Medium',
                                3 => 'High',
                                4 => 'Critical',
                                5 => 'Emergency',
                            ])
                            ->required()
                            ->default(1),
                            
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}
