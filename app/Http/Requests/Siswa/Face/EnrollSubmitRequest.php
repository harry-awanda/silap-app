<?php

namespace App\Http\Requests\Siswa\Face;

use Illuminate\Foundation\Http\FormRequest;

class EnrollSubmitRequest extends FormRequest {
  public function authorize(): bool { return auth()->check(); }

  public function rules(): array {
    return [
      'session_id'       => ['required', 'string', 'max:120'],

      'latitude'         => ['required','numeric','between:-90,90'],
      'longitude'        => ['required','numeric','between:-180,180'],
      'accuracy'         => ['nullable','numeric','min:0'],

      'device_id'        => ['nullable','string','max:120'],
      'user_agent'       => ['nullable','string','max:255'],

      'liveness_passed'  => ['required','boolean'],
      'liveness_score'   => ['nullable','numeric','min:0','max:1'],

      'model_version'    => ['nullable','string','max:50'],
      'meta'             => ['nullable','array'],

      // ✅ single OR multi
      'embedding_b64'    => ['nullable','string'],
      'embeddings_b64'   => ['nullable','array','min:1','max:3'],
      'embeddings_b64.*' => ['string'],
    ];
  }

  public function withValidator($validator) {
    $validator->after(function ($v) {
      $single = $this->filled('embedding_b64');
      $multi  = $this->filled('embeddings_b64');

      if (!$single && !$multi) {
        $v->errors()->add('embedding_b64', 'Embedding wajib diisi (embedding_b64 atau embeddings_b64).');
      }

      if ($single && $multi) {
        $v->errors()->add('embeddings_b64', 'Pilih salah satu: embedding_b64 atau embeddings_b64.');
      }
    });
  }
}
