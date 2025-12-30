<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->required()
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90)
                    ->step(0.0000001),
                TextInput::make('longitude')
                    ->required()
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180)
                    ->step(0.0000001),
                TextInput::make('radius')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->suffix('meters'),
            ]);
    }
}
