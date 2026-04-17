<?php

namespace App\Http\Requests\WaliKelas\SiswaPromotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewRequest extends FormRequest {
  public function authorize(): bool
  {
    // Minimal: harus sudah auth + punya homeroom aktif (di-inject middleware)
    // (tetap ada safety check di service untuk siswa binaan)
    return auth()->check() && (bool) $this->attributes->get('homeroom');
  }

  protected function prepareForValidation(): void {
    $mode = (string) $this->route('mode');

    // normalisasi siswa_ids: array<int>, unique
    $ids = $this->input('siswa_ids', []);
    if (is_string($ids)) $ids = [$ids];
    if (!is_array($ids)) $ids = [];

    $ids = array_values(array_unique(array_map('intval', $ids)));

    $this->merge([
      'mode' => $mode,
      'siswa_ids' => $ids,
    ]);
  }

  public function rules(): array {
    $mode = (string) $this->input('mode');

    $base = [
      'mode'       => ['required', Rule::in(['promote', 'graduate'])],
      'siswa_ids'  => ['required', 'array', 'min:1'],
      'siswa_ids.*'=> ['integer', 'distinct'],
    ];

    if ($mode === 'promote') {
      return $base + [
        'to_term_id'     => ['required', 'integer', 'exists:academic_terms,id'],
        'target_classid' => ['required', 'integer', 'exists:classrooms,id'],
        'promote_kind'   => ['required', Rule::in(['advance','repeat'])],
      ];
    }

    // graduate
    return $base + [
      'angkatan' => ['required', 'digits:4'],
    ];
  }

  public function messages(): array {
    return [
      'mode.in'              => 'Mode promosi tidak valid.',
      'siswa_ids.required'   => 'Pilih minimal 1 siswa.',
      'siswa_ids.min'        => 'Pilih minimal 1 siswa.',
      'to_term_id.required'  => 'Term tujuan wajib dipilih.',
      'target_classid.required' => 'Kelas tujuan wajib dipilih.',
      'angkatan.required'    => 'Angkatan wajib diisi.',
      'angkatan.digits'      => 'Angkatan harus 4 digit (contoh: 2025).',
    ];
  }
}