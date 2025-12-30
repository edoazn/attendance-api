<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Mahasiswa')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Nama Mahasiswa'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                    ])
                    ->columns(2),

                Section::make('Informasi Jadwal')
                    ->schema([
                        TextEntry::make('schedule.course.course_name')
                            ->label('Mata Kuliah'),
                        TextEntry::make('schedule.course.course_code')
                            ->label('Kode Mata Kuliah'),
                        TextEntry::make('schedule.location.name')
                            ->label('Lokasi'),
                        TextEntry::make('schedule.start_time')
                            ->label('Waktu Mulai')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('schedule.end_time')
                            ->label('Waktu Selesai')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),

                Section::make('Detail Absensi')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'hadir' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('distance')
                            ->label('Jarak dari Lokasi')
                            ->suffix(' meter'),
                        TextEntry::make('latitude')
                            ->label('Latitude'),
                        TextEntry::make('longitude')
                            ->label('Longitude'),
                        TextEntry::make('created_at')
                            ->label('Waktu Absensi')
                            ->dateTime('d M Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }
}
