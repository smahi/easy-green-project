<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use EduardoRibeiroDev\FilamentLeaflet\Enums\Color;
use EduardoRibeiroDev\FilamentLeaflet\Widgets\MapWidget;
use EduardoRibeiroDev\FilamentLeaflet\Support\Markers\Marker;

class ReportsMap extends MapWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected array $mapCenter = [28.0339, 1.6596]; // Center of Algeria
    protected int $defaultZoom = 5;

    protected function getMarkers(): array
    {
        return Report::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('reportType')
            ->get()
            ->map(function (Report $report) {
                // Map severity to plugin color
                $color = match ($report->reportType->severity_level ?? 1) {
                    1 => Color::Green,
                    2 => Color::Blue,
                    3 => Color::Orange,
                    4 => Color::Red,
                    5 => Color::Black,
                    default => Color::Blue,
                };

                return Marker::make((float) $report->latitude, (float) $report->longitude)
                    ->color($color)
                    ->popupContent("
                        <div class='p-2'>
                            <strong>
                                " . ($report->reportType->name ?? 'Unknown') . "
                            </strong>
                            <p class='text-sm mt-1'>" . ($report->description ?? 'No description') . "</p>
                            <span class='text-xs text-gray-500 capitalize'>Status: {$report->status}</span>
                        </div>
                    ");
            })
            ->all();
    }
}


