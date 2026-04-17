<?php

namespace App\Http\Requests\OutPass;

use Illuminate\Foundation\Http\FormRequest;

class ReturnDetailRequest extends FormRequest {
  public function authorize(): bool { return true; }

  public function rules(): array {
    return [
      'time_back' => ['sometimes','date'],
      'remarks'   => ['sometimes','string','max:255','nullable'],
    ];
  }
}