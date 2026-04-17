<?php

namespace App\Http\Requests\PelanggaranSiswa;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest {
  public function authorize(): bool { return true; }

  public function rules(): array {
    return [
      'siswa_id'            => 'required|exists:siswa,id',
      'pelanggaran'         => 'required|array|min:1',
      'pelanggaran.*'       => 'exists:pelanggaran,id',
      'tanggal_pelanggaran' => 'required|date',
      'keterangan'          => 'nullable|string',
      'status'              => 'nullable|in:diproses,selesai',
      'tindakan'            => 'nullable|in:pembinaan_wali_kelas,pembinaan_guru_bk,pembinaan_kepala_sekolah',
      'catatan_waliKelas'   => 'nullable|string',
      'catatan_kesiswaan'   => 'nullable|string',
      'catatan_guruBK'      => 'nullable|string',
    ];
  }
}