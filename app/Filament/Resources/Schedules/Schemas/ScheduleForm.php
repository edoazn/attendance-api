<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('class_id')
                    ->label('Kelas')
                    ->relationship('classRoom', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('course_id')
                    ->label('Mata Kuliah')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('start_time')
                    ->label('Waktu Mulai')
                    ->required()
                    ->native(false),
                DateTimePicker::make('end_time')
                    ->label('Waktu Selesai')
                    ->required()
                    ->native(false)
                    ->after('start_time'),
            ]);
    }
}
