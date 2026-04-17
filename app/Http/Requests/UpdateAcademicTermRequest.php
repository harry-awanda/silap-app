<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicTermRequest extends FormRequest {
  public function authorize(): bool {
    return true;
  }

  public function rules(): array {
    $id = $this->route('term')->id ?? null;

    return [
      'name'       => ['required','string','max:100', Rule::unique('academic_terms','name')->ignore($id)],
      'year_start' => ['required','integer','min:2000','max:3000'],
      'year_end'   => ['required','integer','gt:year_start','max:3001'],
      'semester'   => ['required', Rule::in(['ganjil','genap'])],
      'start_date' => ['nullable','date'],
      'end_date'   => ['nullable','date','after_or_equal:start_date'],
      'lock_attendance_at' => ['nullable','date'],
      'lock_violation_at'  => ['nullable','date'],

      'unique_combo' => [function($attr,$value,$fail) use ($id){
        $exists = \DB::table('academic_terms')
          ->where('id', '!=', $id)
          ->where([
            'year_start' => (int)request('year_start'),
            'year_end'   => (int)request('year_end'),
            'semester'   => request('semester'),
          ])->exists();
        if ($exists) $fail('Kombinasi tahun ajaran & semester sudah ada.');
      }],
    ];
  }

  public function messages(): array {
    return ['unique_combo' => 'Kombinasi tahun ajaran & semester sudah ada.'];
  }
}