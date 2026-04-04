<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Schedule;
use App\Http\Resources\ScheduleResource;

try {
    $schedule = Schedule::with(['classRoom', 'course', 'location'])->first();
    if (!$schedule) {
        echo "No schedule found.\n";
        exit(0);
    }
    echo "Creating resource...\n";
    $resource = new ScheduleResource($schedule);
    echo "Resolving resource array...\n";
    $array = $resource->resolve();
    echo "Success:\n";
    print_r($array);
} catch (\Throwable $e) {
    echo "FATAL EXCEPTION:\n";
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
