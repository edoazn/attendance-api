<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'distance' => (float) $this->distance,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationship loading
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'schedule' => $this->whenLoaded('schedule', function () {
                return new ScheduleResource($this->schedule);
            }),
        ];
    }
}
