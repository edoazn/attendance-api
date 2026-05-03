<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique()->after('end_time');
            $table->string('attendance_code', 6)->nullable()->after('qr_token');
            $table->timestamp('code_expires_at')->nullable()->after('attendance_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'attendance_code', 'code_expires_at']);
        });
    }
};
