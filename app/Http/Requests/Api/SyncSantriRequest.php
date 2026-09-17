<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SyncSantriRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Administrator', 'Pengurus']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kelas' => 'required|exists:kelas,id',
            'kamar' => 'required|exists:kamars,id',
            'nama_lengkap' => 'required|string|min:3|max:225',
            'dusun' => 'required|string|min:3',
            'desa' => 'required|string|min:3',
            'kecamatan' => 'required|string|min:3',
            'kabupaten' => 'required|string|min:3',
            'jenis_kelamin' => 'required|in:Laki-Laki,Perempuan',
            'nik' => 'required|digits:16',
            'kk' => 'required|digits:16',
            'whatsapp' => 'required|numeric|digits_between:10,13',
            'tanggal_lahir' => 'required|numeric|min:1|max:31',
            'bulan_lahir' => 'required|numeric|min:1|max:12',
            'tahun_lahir' => 'required',
            'tempat_lahir' => 'required|string',
            'tahun_masuk' => 'required',
            'tanggal_boyong' => 'nullable',
            'nama_ayah' => 'required',
            'nama_ibu' => 'required',
            'foto' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Unauthorized action',
                'errors' => [
                    'authorization' => ['User does not have required permissions.'],
                ],
            ], 403)
        );
    }
}
