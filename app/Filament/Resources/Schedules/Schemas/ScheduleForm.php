<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\Course;
use App\Models\Location;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('start_time')
                    ->label('Start Time')
                    ->required()
                    ->native(false),
                DateTimePicker::make('end_time')
                    ->label('End Time')
                    ->required()
                    ->native(false)
                    ->after('start_time'),
            ]);
    }
}
