<?php

namespace App\Http\Requests\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AcademicIntelligenceFilterRequest extends FormRequest
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
        return [
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'compare_year_id' => 'nullable|integer|different:academic_year_id|exists:academic_years,id',
            'kelas_id' => 'nullable|integer|exists:kelas,id',
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'academic_year_id.exists' => 'Tahun ajaran yang dipilih tidak valid.',
            'compare_year_id.exists' => 'Tahun ajaran pembanding tidak valid.',
            'compare_year_id.different' => 'Tahun ajaran pembanding harus berbeda dengan tahun ajaran utama.',
            'kelas_id.exists' => 'Kelas yang dipilih tidak valid.',
        ];
    }
}
