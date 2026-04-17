<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest {
  public function authorize(): bool {
    return auth()->check() && auth()->user()->hasRole('admin');
  }

  public function rules(): array {
  $roleId = $this->route('role')->id;
  return [
    'name' => [
    'required','string','alpha_dash','min:3','max:50',
    Rule::unique('roles','name')->ignore($roleId),
    ],
    'guard_name' => ['nullable','in:web'],
  ];
  }

  public function messages(): array {
  return [
    'name.alpha_dash' => 'Nama role hanya boleh huruf, angka, dash (-), dan underscore (_).',
  ];
  }
}
