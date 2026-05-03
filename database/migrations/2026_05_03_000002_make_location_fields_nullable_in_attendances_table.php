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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('latitude', 17, 14)->nullable()->change();
            $table->decimal('longitude', 18, 14)->nullable()->change();
            $table->decimal('distance', 10, 2)->nullable()->change();
            $table->enum('method', ['geolocation', 'qr_code', 'attendance_code'])
                ->default('geolocation')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('latitude', 17, 14)->nullable(false)->change();
            $table->decimal('longitude', 18, 14)->nullable(false)->change();
            $table->decimal('distance', 10, 2)->nullable(false)->change();
            $table->dropColumn('method');
        });
    }
};
