<?php

namespace App\Filament\Resources\ClassRooms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassRoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academic_year')
                    ->label('Tahun Akademik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Jumlah Mahasiswa')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('schedules_count')
                    ->label('Jumlah Jadwal')
                    ->counts('schedules')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('academic_year')
                    ->label('Tahun Akademik')
                    ->options(fn () => \App\Models\ClassRoom::distinct()->pluck('academic_year', 'academic_year')->toArray()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100]);
    }
}
