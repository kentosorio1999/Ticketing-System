<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MonthlyTicketTrendChart extends ApexChartWidget
{

     
    protected static ?string $chartId = 'monthlyTicketTrendChart';

    protected static ?string $heading = 'Monthly Ticket Trend';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->isSuperAdmin() ||
            $user->hasRole('Admin Unit')
        );
    }

    protected function getOptions(): array
    {
        $user = auth()->user();

        $months = collect(range(5, 0))->map(function ($month) {
            return now()->subMonths($month);
        });

        $data = $months->map(function ($month) use ($user) {
            $query = Ticket::query();

            if ($user->hasRole('Admin Unit')) {
                $query->where('unit_id', $user->unit_id);
            }

            return $query
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        });

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],

            'series' => [
                [
                    'name' => 'Tickets',
                    'data' => $data->toArray(),
                ],
            ],

            'xaxis' => [
                'categories' => $months->map(fn ($month) => $month->format('M'))->toArray(),
            ],

            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth' => '45%',
                ],
            ],

            'dataLabels' => [
                'enabled' => false,
            ],

            'colors' => ['#60a5fa'],
        ];
    }
}