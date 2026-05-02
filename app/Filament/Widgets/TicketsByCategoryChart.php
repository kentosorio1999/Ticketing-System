<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Ticket;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TicketsByCategoryChart extends ApexChartWidget
{
    protected static ?string $chartId = 'ticketsByCategoryChart';

    protected static ?string $heading = 'Tickets by Category';

    protected static ?int $sort = 3;

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

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        $data = $categories->map(function ($category) use ($user) {
            $query = Ticket::where('category_id', $category->id);

            if ($user->hasRole('Admin Unit')) {
                $query->where('unit_id', $user->unit_id);
            }

            return $query->count();
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
                'categories' => $categories->pluck('name')->toArray(),
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

            'colors' => ['#22c55e'],
        ];
    }
}