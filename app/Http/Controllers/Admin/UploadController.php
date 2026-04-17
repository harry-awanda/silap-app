<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller {
  public function index() {
    $title = 'Upload Berkas';
    $uploads = Upload::all();
    return view('admin.uploads.index', compact('title','uploads'));
  }

  public function store(Request $request) {
    $request->validate([
      'file' => 'required|mimes:xlsx,jpg,jpeg,png,pdf|max:2048',
      'description' => 'nullable|string|max:255',
    ]);

    $file = $request->file('file');
    $originalName = $file->getClientOriginalName();
    $filePath = $file->storeAs('uploads', $originalName, 'public');
    $fileType = $file->getClientOriginalExtension();

    Upload::create([
      'file_name' => $originalName,
      'file_path' => $filePath,
      'description' => $request->input('description'),
      'file_type' => $fileType,
    ]);

    return redirect()->route('admin.uploads.index')->with('success', 'File uploaded successfully.');
    
  }

  public function download(Upload $upload) {
    
    // path yang disimpan di DB sebaiknya: "uploads/template_import_siswa.xlsx"
    $path = ltrim($upload->file_path, '/'); 

    if (!Storage::disk('public')->exists($path)) {
      abort(404, 'File tidak ditemukan di storage.');
    }

    // Nama file yang ramah pengguna (opsional ambil dari kolom Upload)
    $downloadName = $upload->original_name ?? basename($path);

    // Stream/force download tanpa bergantung symlink
    return Storage::disk('public')->download($path, $downloadName);
  }

  public function destroy(Upload $upload) {
    // Hapus file dari storage
    Storage::delete('public/' . $upload->file_path);
    // Hapus record dari database
    $upload->delete();
    return redirect()->route('admin.uploads.index')->with('success', 'File deleted successfully.');
  }
}