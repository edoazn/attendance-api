<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'day' => $this->start_time?->format('l'), // Day name (e.g., Monday, Tuesday)
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationship loading
            'class_room' => $this->whenLoaded('classRoom', function () {
                return new ClassRoomResource($this->classRoom);
            }),
            'course' => $this->whenLoaded('course', function () {
                return new CourseResource($this->course);
            }),
            'location' => $this->whenLoaded('location', function () {
                return new LocationResource($this->location);
            }),
        ];
    }
}
