<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Guru, JadwalPiket, User, AcademicTerm};
use Illuminate\Support\Facades\DB;

class JadwalPiketController extends Controller {
  /**
   * Daftar jadwal piket
   */
  public function index() {
    $title = 'Jadwal Piket';
    $activeTerm = AcademicTerm::where('is_active', true)->first();

    $jadwalPiket = JadwalPiket::with(['guru'])
      ->when($activeTerm, fn($q) => $q->where('term_id', $activeTerm->id))
      ->orderBy('hari_piket')
      ->get();

    return view('admin.jadwal_piket.index', compact('title', 'jadwalPiket', 'activeTerm'));
  }

  /**
   * Form tambah jadwal piket
   */
  public function create() {
    $title = 'Tambah Jadwal Piket';
    $guru = Guru::orderBy('nama_lengkap')->get();

    return view('admin.jadwal_piket.create', compact('title', 'guru'));
  }

  /**
   * Simpan data jadwal piket baru
   */
  public function store(Request $request) {
    $request->validate([
      'guru_id' => 'required|exists:guru,id',
      'hari_piket' => 'required|string|max:20',
    ]);

    $activeTerm = AcademicTerm::where('is_active', true)->first();
    if (!$activeTerm) {
      return back()->with('error', 'Tidak ada tahun ajaran aktif. Silakan aktifkan tahun ajaran terlebih dahulu.');
    }

    // Cek duplikasi jadwal pada term aktif
    $exists = JadwalPiket::where('guru_id', $request->guru_id)
      ->where('hari_piket', $request->hari_piket)
      ->where('term_id', $activeTerm->id)
      ->exists();

    if ($exists) {
      return back()->with('error', 'Guru sudah memiliki jadwal piket pada hari tersebut di tahun ajaran aktif.');
    }

    DB::beginTransaction();
    try {
      $guru = Guru::findOrFail($request->guru_id);
      $user = $guru->user;

      // Buat user jika belum ada (untuk kasus guru belum punya akun)
      if (!$user) {
        $username = strtolower(str_replace(' ', '', $guru->nama_lengkap));
        $user = User::create([
          'username' => 'piket.' . $username,
          'name' => $guru->nama_lengkap,
          'password' => bcrypt('smkn4321'),
        ]);
      }

      // Pastikan user memiliki role guru_piket
      if (!$user->hasRole('guru_piket')) {
        $user->assignRole('guru_piket');
      }

      // Simpan jadwal piket
      JadwalPiket::create([
        'guru_id' => $guru->id,
        'hari_piket' => $request->hari_piket,
        'term_id' => $activeTerm->id,
      ]);

      DB::commit();
      return redirect()->route('admin.jadwal-piket.index')
        ->with('success', 'Jadwal piket berhasil ditambahkan untuk tahun ajaran aktif.');
    } catch (\Throwable $th) {
      DB::rollBack();
      return back()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
    }
  }

  /**
   * Form edit jadwal piket
   */
  public function edit(JadwalPiket $jadwalPiket) {
    $title = 'Edit Jadwal Piket';
    $guru = Guru::orderBy('nama_lengkap')->get();

    return view('admin.jadwal_piket.edit', compact('title', 'guru'))
      ->with('data', $jadwalPiket);
  }

  /**
   * Update jadwal piket
   */
  public function update(Request $request, JadwalPiket $jadwalPiket) {
    $request->validate([
      'guru_id' => 'required|exists:guru,id',
      'hari_piket' => 'required|string|max:20',
    ]);

    $activeTerm = AcademicTerm::where('is_active', true)->first();
    if (!$activeTerm) {
      return back()->with('error', 'Tidak ada tahun ajaran aktif.');
    }

    // Cek duplikasi untuk term aktif selain jadwal yang sedang diubah
    $exists = JadwalPiket::where('guru_id', $request->guru_id)
      ->where('hari_piket', $request->hari_piket)
      ->where('term_id', $activeTerm->id)
      ->where('id', '!=', $jadwalPiket->id)
      ->exists();

    if ($exists) {
      return back()->with('error', 'Guru sudah memiliki jadwal piket pada hari ini di tahun ajaran aktif.');
    }

    $jadwalPiket->update([
      'guru_id' => $request->guru_id,
      'hari_piket' => $request->hari_piket,
      'term_id' => $activeTerm->id,
    ]);

    return redirect()->route('admin.jadwal-piket.index')
      ->with('success', 'Jadwal piket berhasil diperbarui.');
  }

  /**
   * Hapus jadwal piket
   */
  public function destroy(JadwalPiket $jadwalPiket) {
    try {
      $jadwalPiket->delete();
      return redirect()->route('admin.jadwal-piket.index')
        ->with('success', 'Jadwal piket berhasil dihapus.');
    } catch (\Throwable $th) {
      return back()->with('error', 'Gagal menghapus jadwal: ' . $th->getMessage());
    }
  }
}
