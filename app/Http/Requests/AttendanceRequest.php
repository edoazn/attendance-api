<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'schedule_id'     => 'required|exists:schedules,id',
            'method'          => 'required|in:geolocation,qr_code,attendance_code',
            // Geolocation fields — only required when method is geolocation
            'latitude'        => 'required_if:method,geolocation|numeric|between:-90,90',
            'longitude'       => 'required_if:method,geolocation|numeric|between:-180,180',
            // QR Code field — only required when method is qr_code
            'qr_token'        => 'required_if:method,qr_code|string',
            // Manual attendance code — only required when method is attendance_code
            'attendance_code' => 'required_if:method,attendance_code|string|size:6',
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
            'schedule_id.required'          => 'Schedule ID is required',
            'schedule_id.exists'            => 'Schedule not found',
            'method.required'               => 'Attendance method is required',
            'method.in'                     => 'Method must be one of: geolocation, qr_code, attendance_code',
            'latitude.required_if'          => 'Latitude is required for geolocation method',
            'latitude.numeric'              => 'Latitude must be a number',
            'latitude.between'             => 'Latitude must be between -90 and 90',
            'longitude.required_if'         => 'Longitude is required for geolocation method',
            'longitude.numeric'             => 'Longitude must be a number',
            'longitude.between'            => 'Longitude must be between -180 and 180',
            'qr_token.required_if'          => 'QR token is required for QR Code method',
            'attendance_code.required_if'   => 'Attendance code is required for manual code method',
            'attendance_code.size'          => 'Attendance code must be exactly 6 characters',
        ];
    }
}
