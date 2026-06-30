<?php

namespace App\Http\Requests\AuditAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends IndexRequest {
  
  public function authorize(): bool {
    // Sesuaikan role sesuai kebijakan kamu.
    // Contoh: admin/kesiswaan/guru_bk boleh audit.
    return auth()->check() && auth()->user()->hasAnyRole(['admin','guru','kesiswaan','guru_bk', 'guru_piket']);
  }

  protected function prepareForValidation(): void {
    $date = $this->input('date');
    $this->merge([
      'date' => $date ?: now()->toDateString(),
      'status' => $this->input('status') ?: null,
      'classroom_id' => $this->input('classroom_id') ?: null,
    ]);
  }

  public function rules(): array {
    return [
      'date' => ['required', 'date_format:Y-m-d'],
      'status' => ['nullable', 'string', Rule::in(['hadir','terlambat','izin','sakit','alpa','belum'])],
      'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
    ];
  }

  public function selectedDate(): string { return (string) $this->input('date'); }
  public function status(): ?string { return $this->input('status') ? (string)$this->input('status') : null; }
  public function classroomId(): ?int { return $this->input('classroom_id') ? (int)$this->input('classroom_id') : null; }
}
