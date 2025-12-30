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
                    ->label('Nama Mata Kuliah'),
                TextInput::make('course_code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->label('Kode MK'),
                TextInput::make('lecturer_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Dosen'),
                TextInput::make('location_room')
                    ->maxLength(100)
                    ->label('Ruangan'),
            ]);
    }
}
