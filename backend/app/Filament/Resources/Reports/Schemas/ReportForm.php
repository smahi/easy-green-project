<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Report Details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(),
                                Select::make('report_type_id')
                                    ->relationship('reportType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(),
                            ]),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->disabled(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('longitude')
                                    ->numeric()
                                    ->disabled(),
                            ]),
                        Select::make('status')
                            ->options([
                                'new' => __('New'),
                                'processing' => __('Processing'),
                                'resolved' => __('Resolved'),
                                'rejected' => __('Rejected'),
                            ])
                            ->required()
                            ->default('new'),
                        Textarea::make('inspector_feedback')
                            ->columnSpanFull()
                            ->label(__('Inspector Feedback')),
                        Toggle::make('is_synchronized')
                            ->required()
                            ->disabled(),
                    ]),
                Section::make(__('Media'))
                    ->schema([
                        FileUpload::make('media_attachments')
                            ->multiple()
                            ->image()
                            ->directory('reports-media')
                            ->columnSpanFull()
                            ->disabled(),
                    ]),
            ]);
    }
}
