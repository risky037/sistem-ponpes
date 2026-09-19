<?php

namespace App\Http\Requests\Profil;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BiodataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('updateBiodata', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tempat_lahir' => ['required', 'string'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-Laki,Perempuan'],
            'whatsapp' => ['required', 'string', 'max:13'],
            'provinsi_id' => ['required', 'exists:provinsis,id'],
            'kabupaten_id' => ['required', 'exists:kabupatens,id'],
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kelurahan_id' => ['required', 'exists:kelurahans,id'],
            'dusun' => ['required', 'string'],
            'nik' => ['required', 'min:16'],
            'kk' => ['required', 'min:16'],
            'foto' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:3020'],
        ];
    }
}
