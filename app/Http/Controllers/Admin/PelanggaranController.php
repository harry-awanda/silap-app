<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pelanggaran;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataPelanggaranImport;

class PelanggaranController extends Controller {

  public function index() {
    $title = 'Data Pelanggaran';
    $pelanggaran = Pelanggaran::all();
    return view('admin.pelanggaran.index', compact('title','pelanggaran'));
  }

  public function store(Request $request) {
    $request->validate([
      'jenis' => 'required|string|max:255',
      'nama' => 'required|string|max:255',
    ]);
    Pelanggaran::create($request->all());

    return redirect()->route('admin.pelanggaran.index')->with('success', 'Data pelanggaran berhasil ditambahkan.');
  }

  public function update(Request $request, Pelanggaran $pelanggaran) {
    $request->validate([
      'jenis' => 'required|string|max:255',
      'nama' => 'required|string|max:255',
    ]);
    $pelanggaran->update($request->all());

    return redirect()->route('admin.pelanggaran.index')->with('success', 'Data pelanggaran berhasil diperbarui.');
  }

  public function destroy(Pelanggaran $pelanggaran) {
    $pelanggaran->delete();
    return redirect()->route('admin.pelanggaran.index')->with('success', 'Data pelanggaran berhasil dihapus.');
  }

  public function importExcel(Request $request) {
    $request->validate([
      'file' => 'required|mimes:xlsx',
    ]);
    Excel::import(new DataPelanggaranImport, $request->file('file'));
    return redirect()->route('admin.pelanggaran.index')->with('success', 'Data pelanggaran berhasil diimpor.');
  }
}