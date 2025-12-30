<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
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
     * 
     * Requirements: 7.2, 7.3
     */
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
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
            'start_date.date' => 'Start date must be a valid date',
            'start_date.date_format' => 'Start date must be in Y-m-d format',
            'end_date.date' => 'End date must be a valid date',
            'end_date.date_format' => 'End date must be in Y-m-d format',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'schedule_id.integer' => 'Schedule ID must be an integer',
            'schedule_id.exists' => 'Schedule not found',
        ];
    }
}
