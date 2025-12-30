<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected ?string $startDate;
    protected ?string $endDate;
    protected ?int $scheduleId;

    public function __construct(?string $startDate = null, ?string $endDate = null, ?int $scheduleId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->scheduleId = $scheduleId;
    }

    public function query()
    {
        $query = Attendance::query()
            ->with(['user', 'schedule.course', 'schedule.location']);

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->scheduleId) {
            $query->where('schedule_id', $this->scheduleId);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Mahasiswa',
            'Email',
            'Mata Kuliah',
            'Kode MK',
            'Lokasi',
            'Waktu Jadwal',
            'Status',
            'Jarak (m)',
            'Latitude',
            'Longitude',
            'Waktu Absensi',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->id,
            $attendance->user->name,
            $attendance->user->email,
            $attendance->schedule->course->course_name,
            $attendance->schedule->course->course_code,
            $attendance->schedule->location->name,
            $attendance->schedule->start_time->format('d/m/Y H:i') . ' - ' . $attendance->schedule->end_time->format('H:i'),
            $attendance->status === 'hadir' ? 'Hadir' : 'Ditolak',
            round($attendance->distance, 2),
            $attendance->latitude,
            $attendance->longitude,
            $attendance->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
