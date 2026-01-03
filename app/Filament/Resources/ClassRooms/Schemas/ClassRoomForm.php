<?php

namespace App\Filament\Resources\ClassRooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClassRoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kelas')
                    ->placeholder('TI-2A, SI-3B, dll')
                    ->required()
                    ->maxLength(255),
                TextInput::make('academic_year')
                    ->label('Tahun Akademik')
                    ->placeholder('2024/2025')
                    ->required()
                    ->maxLength(255),
                Select::make('users')
                    ->label('Mahasiswa')
                    ->relationship('users', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }
}
