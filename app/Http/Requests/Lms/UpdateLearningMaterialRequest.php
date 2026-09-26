<?php

namespace App\Http\Requests\Lms;

use App\Models\LearningMaterial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLearningMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $material = $this->route('learning_material') ?? $this->route('material');

        if (! $material instanceof LearningMaterial) {
            return false;
        }

        return $this->user() !== null && $this->user()->can('update', $material);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'target_class_ids' => ['nullable', 'array'],
            'target_class_ids.*' => ['integer', 'exists:kelas,id'],
            'content_type' => ['sometimes', 'required', 'string', 'in:text,pdf,video,link,mixed'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,published,archived'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4',
                'max:25600',
            ],
            'remove_file_ids' => ['nullable', 'array'],
            'remove_file_ids.*' => ['integer', 'exists:learning_material_files,id'],
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
            'content_type.in' => 'Tipe konten tidak valid.',
            'files.*.max' => 'Ukuran setiap berkas lampiran tidak boleh melebihi 25 MB.',
            'files.*.mimes' => 'Format berkas harus berupa PDF, Word, PowerPoint, Excel, Gambar, atau Video.',
        ];
    }
}
