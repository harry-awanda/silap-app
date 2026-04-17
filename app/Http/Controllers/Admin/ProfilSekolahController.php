<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Guru, ProfilSekolah};

class ProfilSekolahController extends Controller {
  public function edit() {
    $title = 'Profil Sekolah';
    $profil = ProfilSekolah::first(); // Ambil data profil sekolah pertama
    // Jika data profil sekolah belum ada, buat instance kosong untuk form
    if (!$profil) {
      $profil = new ProfilSekolah();
    }
    // Ambil data kepala sekolah dan kesiswaan untuk dropdown (opsional, jika diperlukan)
    $kepalaSekolahOptions = Guru::pluck('nama_lengkap', 'id')->toArray();
    $kesiswaanOptions = Guru::pluck('nama_lengkap', 'id')->toArray();
    
    return view('admin.profil.edit', compact('title', 'profil', 'kepalaSekolahOptions', 'kesiswaanOptions'));
    }
  
  public function update(Request $request) {
    $request->validate([
      'nama_sekolah' => 'required',
      'npsn' => 'required',
      'kepala_sekolah_id' => 'nullable|exists:guru,id', // Validasi foreign key
      'kesiswaan_id' => 'nullable|exists:guru,id', // Validasi foreign key
    ]);
    // Cari profil sekolah pertama atau buat baru jika tidak ada
    $profil = ProfilSekolah::firstOrNew();
    $profil->fill($request->all());
    $profil->save();

    return redirect()->route('admin.profil.edit')->with('success', 'Profil Sekolah berhasil disimpan');
  }
}