<?php

namespace App\Http\Requests\WaliKelas\Siswa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiswaRequest extends FormRequest {
  public function authorize(): bool {
    // Otorisasi binaan dilakukan di controller (ensureBinaan)
    return true;
  }

  public function rules(): array {
    /** @var \App\Models\Siswa|null $siswa */
    $siswa = $this->route('siswa');

    return [
      'nis'             => ['required', 'string', 'max:11', Rule::unique('siswa', 'nis')->ignore($siswa?->id)],
      'nama_lengkap'    => ['required', 'string', 'max:50'],
      'jenis_kelamin'   => ['nullable', Rule::in(['L', 'P'])],
      'tempat_lahir'    => ['nullable', 'string', 'max:255'],
      'tanggal_lahir'   => ['nullable', 'date'],
      'agama'           => ['nullable', 'string', 'max:50'],
      'alamat'          => ['nullable', 'string'],
      'kontak'          => ['nullable', 'string', 'max:30'],

      'nama_ayah'       => ['nullable', 'string', 'max:50'],
      'pekerjaan_ayah'  => ['nullable', 'string', 'max:50'],
      'kontak_ayah'     => ['nullable', 'string', 'max:20'],

      'nama_ibu'        => ['nullable', 'string', 'max:50'],
      'pekerjaan_ibu'   => ['nullable', 'string', 'max:50'],
      'kontak_ibu'      => ['nullable', 'string', 'max:20'],

      'nama_wali_murid' => ['nullable', 'string', 'max:50'],
      'kontak_wali'     => ['nullable', 'string', 'max:20'],
      'alamat_orangtua' => ['nullable', 'string', 'max:255'],
      'alamat_wali'     => ['nullable', 'string', 'max:255'],

      'photo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
      'photo_cropped'   => ['nullable', 'string'],
    ];
  }

  public function messages(): array {
    return [
      'nis.unique'    => 'NIS sudah digunakan.',
      'photo.image'   => 'File foto harus berupa gambar.',
      'photo.mimes'   => 'Format foto harus jpg, jpeg, png, atau webp.',
      'photo.max'     => 'Ukuran foto maksimal 2MB.',
    ];
  }
}
