<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables;
use App\Models\Siswa;
use App\Models\Classroom;
use Illuminate\Support\Facades\Storage;

class SiswaController extends Controller {

  public function index() {
    $title = 'Data Siswa';
    return view('admin.siswa.index', compact('title'));
  }
  
  public function getData() {
    $data = Siswa::with('classroom')->select('siswa.*');
    
    return DataTables::of($data)
    ->addIndexColumn()
    ->addColumn('kelas', fn($row) => $row->classroom->nama_kelas ?? '-')
    ->make(true);
  }

  public function create() {
    $title = 'Data Siswa';
    $classrooms = Classroom::all();
    return view('admin.siswa.create', compact('classrooms', 'title'));
  }

  public function store(Request $request) {
    $validated = $request->validate([
      'nis' => 'required|unique:siswa,nis',
      'nama_lengkap' => 'required|string|max:50',
      'classroom_id' => 'required|exists:classrooms,id',
      'tempat_lahir' => 'nullable|string',
      'tanggal_lahir' => 'nullable',
      'agama' => 'nullable|string',
      'jenis_kelamin' => 'nullable|in:L,P',
      'alamat' => 'nullable|string|max:255',
      'kontak' => 'nullable|string|max:20',
      'photo' => 'nullable|image|max:2048',
      'nama_ayah' => 'nullable|string',
      'pekerjaan_ayah' => 'nullable|string',
      'kontak_ayah' => 'nullable|string',
      'nama_ibu' => 'nullable|string',
      'pekerjaan_ibu' => 'nullable|string',
      'kontak_ibu' => 'nullable|string',
      'nama_wali_murid' => 'nullable|string',
      'kontak_wali' => 'nullable|string',
      'alamat_orangtua' => 'nullable|string',
      'alamat_wali' => 'nullable|string',
    ]);

    if ($request->hasFile('photo')) {
      $validated['photo'] = $request->file('photo')->store('foto_siswa', 'public');
    }

    Siswa::create($validated);
    return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil disimpan.');
  }

  public function edit(Siswa $siswa)
  {
    $title = 'Data Siswa';
    $classrooms = Classroom::all();
    return view('admin.siswa.edit', compact('title', 'siswa', 'classrooms'));
  }

  public function update(Request $request, Siswa $siswa) {
    $validated = $request->validate([
      'nis' => 'required|unique:siswa,nis,' . $siswa->id,
      'nama_lengkap' => 'required|string|max:50',
      'classroom_id' => 'required|exists:classrooms,id',
      'tempat_lahir' => 'nullable|string',
      'tanggal_lahir' => 'nullable',
      'agama' => 'nullable|string',
      'jenis_kelamin' => 'nullable|in:L,P',
      'alamat' => 'nullable|string|max:255',
      'kontak' => 'nullable|string|max:20',
      'photo' => 'nullable|image|max:2048',
      'nama_ayah' => 'nullable|string',
      'pekerjaan_ayah' => 'nullable|string',
      'kontak_ayah' => 'nullable|string',
      'nama_ibu' => 'nullable|string',
      'pekerjaan_ibu' => 'nullable|string',
      'kontak_ibu' => 'nullable|string',
      'nama_wali_murid' => 'nullable|string',
      'kontak_wali' => 'nullable|string',
      'alamat_orangtua' => 'nullable|string',
      'alamat_wali' => 'nullable|string',
    ]);

    if ($request->hasFile('photo')) {
      if ($siswa->photo && Storage::disk('public')->exists($siswa->photo)) {
        Storage::disk('public')->delete($siswa->photo);
      }
      $validated['photo'] = $request->file('photo')->store('foto_siswa', 'public');
    }

    $siswa->update($validated);
    return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
  }

  public function destroy(Siswa $siswa)
  {
    if ($siswa->photo && Storage::disk('public')->exists($siswa->photo)) {
      Storage::disk('public')->delete($siswa->photo);
    }
    $siswa->delete();
    return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil dihapus.');
  }
}
