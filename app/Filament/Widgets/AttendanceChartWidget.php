<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;

class AttendanceChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Attendance Status Today';
    }

    protected function getData(): array
    {
        $hadir = Attendance::whereDate('created_at', today())
            ->where('status', 'hadir')
            ->count();

        $ditolak = Attendance::whereDate('created_at', today())
            ->where('status', 'ditolak')
            ->count();

        return [
            'datasets' => [
                [
                    'data' => [$hadir, $ditolak],
                    'backgroundColor' => ['#10B981', '#EF4444'],
                ],
            ],
            'labels' => ['Hadir', 'Ditolak'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
