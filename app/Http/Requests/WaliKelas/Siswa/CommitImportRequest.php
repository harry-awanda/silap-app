<?php

namespace App\Http\Requests\WaliKelas\Siswa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;

class CommitImportRequest extends FormRequest {
  public function authorize(): bool {
    // akses sudah dikunci di route middleware
    return true;
  }
  
  public function rules(): array {
    return [
      'tempPath' => [
        'required',
        'string',
        function ($attr, $value, $fail) {
          if (!str_starts_with($value, 'tmp/import_siswa/')) {
            $fail('Path import tidak valid.');
          }
          if (!Storage::exists($value)) {
            $fail('File import tidak ditemukan.');
          }
        }
      ],
    ];
  }

  public function messages(): array {
    return [
      'tempPath.required' => 'Temp path tidak ditemukan. Silakan ulangi proses preview.',
      'tempPath.string'   => 'Temp path tidak valid.',
    ];
  }

  protected function prepareForValidation(): void {
    // normalisasi kecil: trim
    if ($this->has('tempPath')) {
      $this->merge([
        'tempPath' => trim((string) $this->input('tempPath')),
      ]);
    }
  }

  public function withValidator($validator): void {
    $validator->after(function ($v) {
      $tempPath = (string) $this->input('tempPath', '');

      // Guard: hanya izinkan file dari folder tmp/import_siswa
      // Ini penting agar user tidak bisa "menebak" path file lain.
      if (!str_starts_with($tempPath, 'tmp/import_siswa/')) {
        $v->errors()->add('tempPath', 'Temp path tidak valid. Silakan ulangi proses preview.');
        return;
      }

      // Pastikan file temp benar-benar ada
      if (!Storage::exists($tempPath)) {
        $v->errors()->add('tempPath', 'File sementara tidak ditemukan/expired. Silakan upload ulang.');
      }
    });
  }
}