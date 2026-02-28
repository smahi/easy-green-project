<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('farmer') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reports' => ['required', 'array', 'min:1', 'max:50'],
            'reports.*.client_uuid' => ['nullable', 'string', 'max:36'],
            'reports.*.report_type_id' => ['required', 'integer', 'exists:report_types,id'],
            'reports.*.description' => ['nullable', 'string', 'max:1000'],
            'reports.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'reports.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'reports.*.is_synchronized' => ['nullable', 'boolean'],
            'reports.*.created_at' => ['nullable', 'date'], // allowing them to provide original creation time if they want
        ];
    }
}
