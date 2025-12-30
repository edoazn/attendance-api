<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Location;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Locations', Location::count())
                ->description('Campus locations')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Total Courses', Course::count())
                ->description('Available courses')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning'),

            Stat::make('Attendance Today', Attendance::whereDate('created_at', today())->count())
                ->description('Records today')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),
        ];
    }
}
