<?php

namespace App\Http\Requests\Siswa\Face;

use Illuminate\Foundation\Http\FormRequest;

class FaceAttendanceRequest extends FormRequest {
  public function authorize(): bool { return auth()->check(); }

  public function rules(): array {
    return [
      'latitude'        => 'required|numeric|between:-90,90',
      'longitude'       => 'required|numeric|between:-180,180',
      'accuracy'        => 'nullable|numeric|min:0',

      'device_id'       => 'nullable|string|max:191',
      'user_agent'      => 'nullable|string|max:255',

      'liveness_passed' => 'required|boolean',
      'liveness_score'  => 'nullable|numeric|min:0|max:1',

      // tahap awal: embedding base64 dari client
      'embedding_b64'   => 'required|string',
      'model_version'   => 'nullable|string|max:50',
    ];
  }
}