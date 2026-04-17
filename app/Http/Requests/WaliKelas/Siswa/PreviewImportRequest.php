<?php

namespace App\Http\Requests\WaliKelas\Siswa;

use Illuminate\Foundation\Http\FormRequest;

class PreviewImportRequest extends FormRequest {
  public function authorize(): bool {
    // akses sudah dikunci di route middleware (auth, role:wali_kelas, ensure.homeroom, inject.homeroom)
    return true;
  }

  public function rules(): array {
    return [
      'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
    ];
  }

  public function messages(): array {
    return [
      'file.required' => 'File import wajib diunggah.',
      'file.file'     => 'File import tidak valid.',
      'file.mimes'    => 'Format file harus .xlsx atau .xls.',
      'file.max'      => 'Ukuran file maksimal 20MB.',
    ];
  }
}