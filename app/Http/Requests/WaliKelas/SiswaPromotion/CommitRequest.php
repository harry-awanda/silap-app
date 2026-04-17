<?php

namespace App\Http\Requests\WaliKelas\SiswaPromotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommitRequest extends FormRequest {
  private const CONFIRM_PROMOTE  = 'PROMOTE';
  private const CONFIRM_GRADUATE = 'LULUS';

  public function authorize(): bool {
    return auth()->check() && (bool) $this->attributes->get('homeroom');
  }

  protected function prepareForValidation(): void {
    $mode = (string) $this->route('mode');

    $payload = $this->input('payload', []);
    if (!is_array($payload)) $payload = [];

    $ids = $payload['siswa_ids'] ?? [];
    if (is_string($ids)) $ids = [$ids];
    if (!is_array($ids)) $ids = [];

    $ids = array_values(array_unique(array_map('intval', $ids)));

    $payload['siswa_ids'] = $ids;

    // normalisasi confirm uppercase
    $confirm = strtoupper((string) $this->input('confirm', ''));

    $this->merge([
      'mode'    => $mode,
      'payload' => $payload,
      'confirm' => $confirm,
    ]);
  }

  public function rules(): array {
    $mode = (string) $this->input('mode');

    $base = [
      'mode'              => ['required', Rule::in(['promote', 'graduate'])],
      'payload'           => ['required', 'array'],
      'payload.siswa_ids' => ['required', 'array', 'min:1'],
      'payload.siswa_ids.*' => ['integer', 'distinct'],
      'confirm'           => ['required', 'string'],
    ];

    if ($mode === 'promote') {
      return $base + [
        'payload.to_term_id'     => ['required', 'integer', 'exists:academic_terms,id'],
        'payload.target_classid' => ['required', 'integer', 'exists:classrooms,id'],
        'payload.promote_kind'   => ['required', Rule::in(['advance','repeat'])],
        // kunci confirm di validation (mengurangi abort_if di service)
        'confirm' => ['required', Rule::in([self::CONFIRM_PROMOTE])],
      ];
    }

    // graduate
    return $base + [
      'payload.angkatan' => ['required', 'digits:4'],
      'confirm' => ['required', Rule::in([self::CONFIRM_GRADUATE])],
    ];
  }

  public function messages(): array {
    return [
      'mode.in' => 'Mode promosi tidak valid.',

      'payload.required' => 'Payload tidak valid.',
      'payload.siswa_ids.required' => 'Pilih minimal 1 siswa.',
      'payload.siswa_ids.min' => 'Pilih minimal 1 siswa.',

      'confirm.required' => 'Konfirmasi wajib diisi.',
      'confirm.in' => 'Konfirmasi tidak sesuai. Cek kembali kata konfirmasi yang diminta.',

      'payload.to_term_id.required' => 'Term tujuan wajib ada pada payload.',
      'payload.target_classid.required' => 'Kelas tujuan wajib ada pada payload.',
      'payload.angkatan.required' => 'Angkatan wajib ada pada payload.',
      'payload.angkatan.digits' => 'Angkatan harus 4 digit (contoh: 2025).',
    ];
  }
}