<?php

namespace App\Http\Requests\WaliKelas\AuditAttendance;

class DataRequest extends IndexRequest {
  protected function prepareForValidation(): void {
    parent::prepareForValidation();

    $q = trim((string) ($this->query('q') ?? ''));
    $this->merge(['q' => $q]);
  }

  public function rules(): array {
    return parent::rules() + [
      'q' => ['nullable', 'string', 'max:100'],
    ];
  }

  public function q(): string { return (string) ($this->input('q') ?? ''); }
}