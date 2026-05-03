<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Schedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'class_id',
        'course_id',
        'location_id',
        'start_time',
        'end_time',
        'qr_token',
        'attendance_code',
        'code_expires_at',
    ];

    protected $casts = [
        'start_time'      => 'datetime',
        'end_time'        => 'datetime',
        'code_expires_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function isActive(): bool
    {
        $now = Carbon::now();
        // Toleransi 5 menit sebelum dan sesudah jadwal
        $startWithTolerance = $this->start_time->copy()->subMinutes(5);
        $endWithTolerance   = $this->end_time->copy()->addMinutes(5);

        return $now->between($startWithTolerance, $endWithTolerance);
    }

    /**
     * Generate a cryptographically unique QR token for this schedule.
     * Saves the token to the model.
     */
    public function generateQrToken(): string
    {
        $token = Str::uuid()->toString();
        $this->update(['qr_token' => $token]);

        return $token;
    }

    /**
     * Generate a 6-character uppercase alphanumeric attendance code
     * and set an expiry time (default: 30 minutes from now).
     */
    public function generateAttendanceCode(int $minutesValid = 30): string
    {
        $code = strtoupper(Str::random(6));
        $this->update([
            'attendance_code' => $code,
            'code_expires_at' => Carbon::now()->addMinutes($minutesValid),
        ]);

        return $code;
    }

    /**
     * Check whether the current attendance_code is still valid.
     */
    public function isCodeValid(): bool
    {
        if (empty($this->attendance_code) || empty($this->code_expires_at)) {
            return false;
        }

        return Carbon::now()->lessThanOrEqualTo($this->code_expires_at);
    }
}
