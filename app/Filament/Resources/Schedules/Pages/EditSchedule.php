<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // --- Generate 6-digit code ---
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
                        ->helperText('Berapa menit kode ini berlaku.'),
                ])
                ->action(function (array $data): void {
                    /** @var Schedule $record */
                    $record = $this->record;
                    $code   = $record->generateAttendanceCode((int) $data['minutes_valid']);
                    Notification::make()
                        ->title('Kode berhasil di-generate')
                        ->body("Kode: **{$code}** — berlaku {$data['minutes_valid']} menit")
                        ->success()
                        ->persistent()
                        ->send();
                })
                ->modalHeading('Generate Kode Absensi Manual')
                ->modalDescription('Kode 6-digit baru akan menggantikan kode sebelumnya.')
                ->modalSubmitActionLabel('Generate'),

            // --- Generate / refresh QR token ---
            Action::make('generate_qr')
                ->label('Generate QR')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Generate QR Token')
                ->modalDescription('QR token lama akan diganti. Mahasiswa harus scan ulang.')
                ->modalSubmitActionLabel('Generate QR')
                ->action(function (): void {
                    /** @var Schedule $record */
                    $record = $this->record;
                    $record->generateQrToken();
                    Notification::make()
                        ->title('QR Token berhasil di-generate')
                        ->success()
                        ->send();
                }),

            // --- View QR modal ---
            Action::make('view_qr')
                ->label('Lihat QR')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible(fn (): bool => filled($this->record->qr_token))
                ->modalHeading(fn (): string =>
                    'QR Code — ' . ($this->record->course->course_name ?? 'Jadwal'))
                ->modalContent(fn () => view(
                    'filament.schedules.qr-modal',
                    ['schedule' => $this->record]
                ))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            DeleteAction::make(),
        ];
    }
}
