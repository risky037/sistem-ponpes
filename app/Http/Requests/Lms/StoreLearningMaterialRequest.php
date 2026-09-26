<?php

namespace App\Http\Requests\Lms;

use App\Models\LearningMaterial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLearningMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', LearningMaterial::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'mapel_id' => ['required', 'integer', 'exists:mapels,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'target_class_ids' => ['nullable', 'array'],
            'target_class_ids.*' => ['integer', 'exists:kelas,id'],
            'content_type' => ['required', 'string', 'in:text,pdf,video,link,mixed'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4',
                'max:25600', // 25 MB max per file
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul materi wajib diisi.',
            'mapel_id.required' => 'Mata pelajaran wajib dipilih.',
            'mapel_id.exists' => 'Mata pelajaran yang dipilih tidak valid.',
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'content_type.in' => 'Tipe konten tidak valid.',
            'files.*.max' => 'Ukuran setiap berkas lampiran tidak boleh melebihi 25 MB.',
            'files.*.mimes' => 'Format berkas harus berupa PDF, Word, PowerPoint, Excel, Gambar, atau Video.',
        ];
    }
}
