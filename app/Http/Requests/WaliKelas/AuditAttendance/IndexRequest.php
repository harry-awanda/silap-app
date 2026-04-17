<?php

namespace App\Http\Requests\WaliKelas\AuditAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\HomeroomAssignment;

class IndexRequest extends FormRequest {
  public function authorize(): bool {
    return auth()->check() && (bool) $this->attributes->get('homeroom');
  }

  protected function prepareForValidation(): void {
    $date = $this->query('date') ?? $this->input('date') ?? Carbon::today()->toDateString();

    $this->merge([
      'date' => $date,
      'status' => $this->query('status') ?? null,
    ]);
  }

  public function rules(): array {
    return [
      'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
      'status' => ['nullable', 'string', Rule::in(['hadir','terlambat','izin','sakit','alpa','belum'])],
    ];
  }

  /** @return HomeroomAssignment */
  public function homeroom() {
    /** @var HomeroomAssignment|null $h */
    $h = $this->attributes->get('homeroom');
    abort_if(!$h, 403, 'Anda bukan wali kelas pada term aktif.');
    return $h;
  }

  public function selectedDate(): string { return (string) $this->input('date'); }
  
  public function status(): ?string {
    $s = $this->input('status');
    return $s === null || $s === '' ? null : strtolower((string) $s);
  }
}