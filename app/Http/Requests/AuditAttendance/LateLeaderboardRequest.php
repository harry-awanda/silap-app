<?php

namespace App\Http\Requests\AuditAttendance;

use Illuminate\Foundation\Http\FormRequest;

class LateLeaderboardRequest extends FormRequest {
  public function authorize(): bool {
    return auth()->check() && auth()->user()->hasAnyRole(['admin','kesiswaan','guru','guru_bk', 'guru_piket']);
  }

  protected function prepareForValidation(): void {
    $date = $this->input('date');
    $this->merge([
      'date' => $date ?: now()->toDateString(),
      'classroom_id' => $this->input('classroom_id') ?: null,
    ]);
  }

  public function rules(): array {
    return [
      'date' => ['required', 'date_format:Y-m-d'],
      'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
    ];
  }

  public function selectedDate(): string { return (string) $this->input('date'); }
  public function classroomId(): ?int { return $this->input('classroom_id') ? (int)$this->input('classroom_id') : null; }
}