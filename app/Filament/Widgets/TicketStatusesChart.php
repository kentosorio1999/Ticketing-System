<?php

namespace App\Filament\Widgets;

use App\Models\TicketStatus;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TicketStatusesChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'ticketStatusesChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Tickets';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 2;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $ticketStatuses = TicketStatus::select('id', 'name', 'color')->withCount(['tickets'])->get();

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
            ],
            'series' => $ticketStatuses->pluck('tickets_count')->toArray(),
            'labels' => $ticketStatuses->pluck('name')->toArray(),
            'colors' => $ticketStatuses->pluck('color')->filter()->pad($ticketStatuses->count(), '#c4c3c3')->toArray(),
            'legend' => [
                'labels' => [
                    'colors' => '#9ca3af',
                    'fontWeight' => 600,
                ],
            ],
        ];
    }
}
