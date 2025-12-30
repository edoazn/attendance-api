<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('course_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Course Name'),
                TextInput::make('course_code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->label('Course Code'),
                TextInput::make('lecturer_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Lecturer Name'),
                TextInput::make('location_room')
                    ->maxLength(100)
                    ->label('Location/Room'),
            ]);
    }
}
