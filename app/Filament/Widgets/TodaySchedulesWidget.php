<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodaySchedulesWidget extends BaseWidget
{
    protected static ?string $heading = 'Today\'s Schedules';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                Schedule::query()
                    ->with(['course', 'location'])
                    ->whereDate('start_time', $today)
                    ->orderBy('start_time')
            )
            ->columns([
                TextColumn::make('course.course_name')
                    ->label('Course')
                    ->searchable(),

                TextColumn::make('course.course_code')
                    ->label('Code')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('location.name')
                    ->label('Location'),

                TextColumn::make('start_time')
                    ->label('Start')
                    ->time('H:i'),

                TextColumn::make('end_time')
                    ->label('End')
                    ->time('H:i'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Schedule $record): string {
                        $now = Carbon::now();
                        if ($now->lt($record->start_time)) {
                            return 'Upcoming';
                        } elseif ($now->between($record->start_time, $record->end_time)) {
                            return 'Active';
                        } else {
                            return 'Completed';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Upcoming' => 'warning',
                        'Completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
