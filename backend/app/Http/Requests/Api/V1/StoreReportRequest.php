<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'client_uuid' => ['nullable', 'string', 'max:36'],
            'report_type_id' => ['required', 'integer', 'exists:report_types,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_synchronized' => ['nullable', 'boolean'],
            'media_attachments' => ['nullable', 'array', 'max:5'],
            'media_attachments.*' => ['file', 'mimes:jpeg,png,jpg,mp4,mov,m4a', 'max:20480'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Log::error('Report Validation Failed', [
            'errors' => $validator->errors()->toArray(),
            'request' => $this->all(),
            'files' => $this->file(),
        ]);
        parent::failedValidation($validator);
    }
}
