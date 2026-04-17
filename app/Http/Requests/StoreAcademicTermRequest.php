<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicTermRequest extends FormRequest {
  public function authorize(): bool {
    return true;
  }

  public function rules(): array {
    return [
      'name'       => ['required','string','max:100','unique:academic_terms,name'],
      'year_start' => ['required','integer','min:2000','max:3000'],
      'year_end'   => ['required','integer','gt:year_start','max:3001'],
      'semester'   => ['required', Rule::in(['ganjil','genap'])],
      'start_date' => ['nullable','date'],
      'end_date'   => ['nullable','date','after_or_equal:start_date'],
      // tidak bisa dibuat partial unique di MySQL → kita cek kombinasi unik via Rule:
      // (opsional) cegah duplikat kombinasi tahun+semester
      'unique_combo' => [function($attr,$value,$fail){
        $exists = \DB::table('academic_terms')->where([
          'year_start' => (int)request('year_start'),
          'year_end'   => (int)request('year_end'),
          'semester'   => request('semester'),
        ])->exists();
        if ($exists) $fail('Kombinasi tahun ajaran & semester sudah ada.');
      }],
      'lock_attendance_at' => ['nullable','date'],
      'lock_violation_at'  => ['nullable','date'],
    ];
  }

  public function messages(): array {
    return ['unique_combo' => 'Kombinasi tahun ajaran & semester sudah ada.'];
  }
}
