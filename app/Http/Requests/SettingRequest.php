<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('patch') || $this->isMethod('put');

        return [
            'logo' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'favicon' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg,ico,webp', 'max:5120'],
            'kts_master' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'log_activity' => ['required'],
        ];
    }
}
