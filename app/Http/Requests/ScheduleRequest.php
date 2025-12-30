<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'Course not found',
            'location_id.required' => 'Location ID is required',
            'location_id.exists' => 'Location not found',
            'start_time.required' => 'Start time is required',
            'start_time.date' => 'Start time must be a valid date',
            'end_time.required' => 'End time is required',
            'end_time.date' => 'End time must be a valid date',
            'end_time.after' => 'End time must be after start time',
        ];
    }
}
