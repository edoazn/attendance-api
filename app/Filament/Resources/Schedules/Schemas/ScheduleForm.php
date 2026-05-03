<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- Main schedule fields ---
                Section::make('Informasi Jadwal')
                    ->columns(2)
                    ->schema([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->relationship('classRoom', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Select::make('course_id')
                            ->label('Mata Kuliah')
                            ->relationship('course', 'course_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Select::make('location_id')
                            ->label('Lokasi')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        DateTimePicker::make('start_time')
                            ->label('Waktu Mulai')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                        DateTimePicker::make('end_time')
                            ->label('Waktu Selesai')
                            ->required()
                            ->native(false)
                            ->after('start_time')
                            ->columnSpan(1),
                    ]),

                // --- Attendance code & QR info (read-only, visible on edit only) ---
                Section::make('Kode & QR Absensi')
                    ->description('Generate kode/QR menggunakan tombol aksi di atas halaman.')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        TextInput::make('attendance_code')
                            ->label('Kode Manual (6 digit)')
                            ->placeholder('Belum di-generate')
                            ->disabled()
                            ->columnSpan(1),
                        DateTimePicker::make('code_expires_at')
                            ->label('Kedaluwarsa Kode')
                            ->placeholder('—')
                            ->disabled()
                            ->native(false)
                            ->columnSpan(1),
                        TextInput::make('qr_token')
                            ->label('QR Token')
                            ->placeholder('Belum di-generate')
                            ->disabled()
                            ->columnSpan(2),
                    ])
                    ->visibleOn('edit'),
            ]);
    }
}
