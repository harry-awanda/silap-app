<?php

namespace App\Http\Requests\OutPass;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Siswa;
use App\Models\OutPass;

class StoreOutPassRequest extends FormRequest {
  public function authorize(): bool { return true; }

  public function rules(): array {
    $reasons = implode(',', array_keys(OutPass::REASONS));
    $methods = implode(',', array_keys(OutPass::METHODS));

    return [
      'classroom_id'     => ['required','exists:classrooms,id'],
      'destination'      => ['required','string','max:255'],
      'reason'           => ['required',"in:$reasons"],
      'approval_at'      => ['required','date'],
      'approval_method'  => ['required',"in:$methods"],
      'approved_by_id'   => ['nullable','exists:guru,id'],
      'approved_by_name' => ['nullable','string','max:120'],
      'time_out'         => ['required','date'],
      'return_expected'  => ['nullable','boolean'],
      'notes'            => ['nullable','string'],

      'siswa_ids'        => ['required','array','min:1'],
      'siswa_ids.*'      => ['integer','exists:siswa,id'],
    ];
  }

  public function withValidator($validator) {
    $validator->after(function ($v) {
      $classroomId = (int) $this->input('classroom_id');
      $siswaIds    = (array) $this->input('siswa_ids', []);

      if (!empty($siswaIds)) {
        $count = Siswa::where('classroom_id', $classroomId)->whereIn('id', $siswaIds)->count();
        if ($count !== count($siswaIds)) {
          $v->errors()->add('siswa_ids', 'Terdapat siswa yang tidak berasal dari kelas terpilih.');
        }
      }

      if (!$this->filled('approved_by_id') && !$this->filled('approved_by_name')) {
        $v->errors()->add('approved_by_id', 'Wajib isi wali kelas (approved_by_id) atau nama wali kelas (approved_by_name).');
      }

      if ($this->input('reason') === 'sakit_pulang' && $this->boolean('return_expected', true) === true) {
        $v->errors()->add('return_expected', 'Untuk sakit pulang, siswa tidak diharapkan kembali (return_expected harus false).');
      }
    });
  }

  public function validatedWithFlags(): array {
    $data = $this->validated();
    $data['return_expected'] = $this->input('reason') === 'sakit_pulang' ? false : (bool)($data['return_expected'] ?? true);
    return $data;
  }
}