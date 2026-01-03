<?php

namespace App\Filament\Resources\Schedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classRoom.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.course_name')
                    ->label('Mata Kuliah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Waktu Mulai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Waktu Selesai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('classRoom', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('course_id')
                    ->label('Mata Kuliah')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),
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
