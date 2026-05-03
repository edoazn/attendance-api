<div class="flex flex-col items-center gap-6 py-4">

    {{-- QR Code rendered via Google Charts API (no backend package needed) --}}
    @php
        $qrData   = $schedule->qr_token;
        $courseNm = $schedule->course->course_name ?? 'Jadwal';
        $classNm  = $schedule->classRoom->name ?? '';
        $start    = $schedule->start_time?->format('d M Y, H:i');
        $end      = $schedule->end_time?->format('H:i');
        $qrUrl    = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrData);
    @endphp

    {{-- Info header --}}
    <div class="text-center">
        <p class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $courseNm }}</p>
        @if($classNm)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $classNm }}</p>
        @endif
        @if($start)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $start }} – {{ $end }}</p>
        @endif
    </div>

    {{-- QR Image --}}
    <div class="rounded-2xl border-4 border-primary-500 bg-white p-3 shadow-lg">
        <img
            src="{{ $qrUrl }}"
            alt="QR Code Absensi"
            width="220"
            height="220"
            class="block"
        />
    </div>

    {{-- Token text (copy-friendly) --}}
    <div class="w-full rounded-lg bg-gray-100 dark:bg-gray-800 p-3 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Token</p>
        <p class="font-mono text-xs break-all text-gray-700 dark:text-gray-200 select-all">
            {{ $schedule->qr_token }}
        </p>
    </div>

    {{-- Attendance code section (if active) --}}
    @if($schedule->attendance_code)
        <div class="w-full rounded-lg border
            {{ $schedule->isCodeValid() ? 'border-success-400 bg-success-50 dark:bg-success-950' : 'border-gray-300 bg-gray-50 dark:bg-gray-800' }}
            p-4 text-center">
            <p class="text-xs font-medium
                {{ $schedule->isCodeValid() ? 'text-success-600 dark:text-success-400' : 'text-gray-400' }}
                mb-1">
                Kode Manual {{ $schedule->isCodeValid() ? '(Aktif)' : '(Kedaluwarsa)' }}
            </p>
            <p class="text-4xl font-extrabold tracking-widest
                {{ $schedule->isCodeValid() ? 'text-success-700 dark:text-success-300' : 'text-gray-400' }}">
                {{ $schedule->attendance_code }}
            </p>
            @if($schedule->code_expires_at)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Kedaluwarsa: {{ $schedule->code_expires_at->format('H:i, d M Y') }}
                </p>
            @endif
        </div>
    @endif

    <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
        Tunjukkan QR ini kepada mahasiswa untuk scan absensi.
    </p>
</div>
