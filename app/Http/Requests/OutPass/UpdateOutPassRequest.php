<?php

namespace App\Http\Requests\OutPass;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\OutPass;

class UpdateOutPassRequest extends FormRequest {
  public function authorize(): bool { return true; }

  public function rules(): array {
    $reasons = implode(',', array_keys(OutPass::REASONS));
    $methods = implode(',', array_keys(OutPass::METHODS));

    return [
      'destination'      => ['sometimes','string','max:255'],
      'reason'           => ['sometimes',"in:$reasons"],
      'approval_at'      => ['sometimes','date','nullable'],
      'approval_method'  => ['sometimes',"in:$methods",'nullable'],
      'approved_by_id'   => ['sometimes','exists:guru,id','nullable'],
      'approved_by_name' => ['sometimes','string','max:120','nullable'],
      'time_out'         => ['sometimes','date'],
      'return_expected'  => ['sometimes','boolean'],
      'notes'            => ['sometimes','string','nullable'],
    ];
  }
}