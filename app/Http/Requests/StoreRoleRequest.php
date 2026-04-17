<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest {
  public function authorize(): bool {
    return auth()->check() && auth()->user()->hasRole('admin');
  }

  public function rules(): array {
  return [
    // Spatie bebas spasi, tapi kita standarkan alpha_dash agar konsisten dengan peran yang ada di SILAP
    'name' => ['required','string','alpha_dash','min:3','max:50','unique:roles,name'],
    // Kalau suatu saat pakai multi-guard, field ini bisa ditampilkan di form.
    'guard_name' => ['nullable','in:web'],
  ];
  }

  public function messages(): array {
  return [
    'name.alpha_dash' => 'Nama role hanya boleh huruf, angka, dash (-), dan underscore (_).',
  ];
  }
}
