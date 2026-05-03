<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Waktu Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                // --- Attendance code column ---
                TextColumn::make('attendance_code')
                    ->label('Kode')
                    ->badge()
                    ->color(fn (Schedule $record): string => $record->isCodeValid() ? 'success' : 'gray')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Kode disalin!')
                    ->tooltip(fn (Schedule $record): ?string => $record->code_expires_at
                        ? 'Kedaluwarsa: ' . $record->code_expires_at->format('H:i, d M Y')
                        : null
                    ),
                // --- QR token status column ---
                TextColumn::make('qr_token')
                    ->label('QR Token')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'info' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Aktif' : '—'),
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

                // --- Generate 6-digit attendance code ---
                Action::make('generate_code')
                    ->label('Generate Kode')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        TextInput::make('minutes_valid')
                            ->label('Berlaku (menit)')
                            ->integer()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(1440)
                            ->required()
                            ->helperText('Berapa menit kode ini berlaku sejak di-generate.'),
                    ])
                    ->action(function (Schedule $record, array $data): void {
                        $code = $record->generateAttendanceCode((int) $data['minutes_valid']);
                        Notification::make()
                            ->title('Kode berhasil di-generate')
                            ->body("Kode: **{$code}** — berlaku {$data['minutes_valid']} menit")
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->modalHeading('Generate Kode Absensi Manual')
                    ->modalDescription('Kode 6-digit akan diberikan kepada mahasiswa untuk absensi manual.')
                    ->modalSubmitActionLabel('Generate'),

                // --- Generate / refresh QR token ---
                Action::make('generate_qr')
                    ->label('Generate QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Generate QR Token')
                    ->modalDescription('Ini akan membuat ulang QR token jadwal ini. QR lama akan tidak berfungsi.')
                    ->modalSubmitActionLabel('Generate QR')
                    ->action(function (Schedule $record): void {
                        $token = $record->generateQrToken();
                        Notification::make()
                            ->title('QR Token berhasil di-generate')
                            ->body('Token baru sudah aktif. Tampilkan QR di dashboard.')
                            ->success()
                            ->send();
                    }),

                // --- View QR Code (modal with rendered QR) ---
                Action::make('view_qr')
                    ->label('Lihat QR')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (Schedule $record): bool => filled($record->qr_token))
                    ->modalHeading(fn (Schedule $record): string =>
                        'QR Code — ' . ($record->course->course_name ?? 'Jadwal'))
                    ->modalContent(fn (Schedule $record) => view(
                        'filament.schedules.qr-modal',
                        ['schedule' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
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
