<?php

namespace App\Filament\Resources\ClassRooms;

use App\Filament\Resources\ClassRooms\Pages\CreateClassRoom;
use App\Filament\Resources\ClassRooms\Pages\EditClassRoom;
use App\Filament\Resources\ClassRooms\Pages\ListClassRooms;
use App\Filament\Resources\ClassRooms\Schemas\ClassRoomForm;
use App\Filament\Resources\ClassRooms\Tables\ClassRoomsTable;
use App\Models\ClassRoom;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClassRoomResource extends Resource
{
    protected static ?string $model = ClassRoom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kelas';

    protected static ?string $pluralModelLabel = 'Kelas';

    protected static ?string $navigationLabel = 'Kelas';

    public static function form(Schema $schema): Schema
    {
        return ClassRoomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassRoomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassRooms::route('/'),
            'create' => CreateClassRoom::route('/create'),
            'edit' => EditClassRoom::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
