<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use App\Models\{Guru, User};
use App\Imports\ImportGuru;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class GuruController extends Controller {
  /**
   * Pastikan role 'guru' tersedia.
   */
  protected function ensureGuruRole(): void {
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
  }

  public function index() {
    $title = 'Data Guru';
    // Eager load user agar tampilan lebih cepat jika butuh name/username
    // $guru = Guru::with('user')->get();
    $guru = Guru::all();
    return view('admin.guru.index', compact('guru', 'title'));
  }

  public function create() {
    $title = 'Tambah Data Guru';
    return view('admin.guru.create', compact('title'));
  }

  public function store(Request $request) {
    // Validasi input. Username kini hanya untuk tabel users
    $request->validate([
      'nip'            => 'required|string|max:50|unique:guru,nip',
      'nama_lengkap'   => 'required|string|max:255',
      'username'       => 'required|string|max:50|unique:users,username',
      'tempat_lahir'   => 'nullable|string|max:100',
      'tanggal_lahir'  => 'nullable|date',
      'jenis_kelamin'  => 'nullable|in:L,P,l,p',
      'alamat'         => 'nullable|string|max:255',
      'kontak'         => 'nullable|string|max:50',
      'photo'          => 'nullable|image|max:2048',
    ]);

    $this->ensureGuruRole();

    try {
      DB::transaction(function () use ($request) {

        // Buat akun login untuk guru (users)
        $user = User::create([
          'name'     => $request->nama_lengkap,
          'username' => strtolower(str_replace(' ', '', $request->username)),
          'password' => bcrypt(env('IMPORT_GURU_DEFAULT_PASSWORD')
                        ?? env('ADMIN_PASSWORD')
                        ?? 'password'), // ganti di .env untuk keamanan
        ]);

        // Assign role 'guru' via Spatie (tanpa menimpa role lain)
        if (!$user->hasRole('guru')) {
          $user->assignRole('guru');
        }

        // Upload foto (nama acak)
        $photoPath = null;
        if ($request->hasFile('photo')) {
          // gunakan nama random (sesuai catatan Bapak)
          $photoPath = $request->file('photo')->store('photos', 'public');
        }

        // Simpan ke tabel guru (tanpa kolom username)
        Guru::create([
          'nip'            => $request->nip,
          'user_id'        => $user->id,
          'nama_lengkap'   => $request->nama_lengkap,
          'jenis_kelamin'  => $request->jenis_kelamin ? strtoupper($request->jenis_kelamin) : null,
          'tempat_lahir'   => $request->tempat_lahir,
          'tanggal_lahir'  => $request->tanggal_lahir,
          'alamat'         => $request->alamat,
          'kontak'         => $request->kontak,
          'photo'          => $photoPath,
        ]);
      });

      return redirect()->route('admin.guru.index')->with('success', 'Data Guru berhasil ditambahkan.');
    } catch (\Throwable $e) {
      return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
    }
  }

  public function show(Guru $guru) {
    // opsional: detail guru
  }

  public function edit(Guru $guru) {
    $title = 'Edit Data Guru';
    // pastikan relasi user termuat jika diperlukan di form
    $guru->load('user');
    return view('admin.guru.edit', compact('guru', 'title'));
  }

  public function update(Request $request, Guru $guru)
  {
    // Validasi. Username & email mengarah ke tabel users (kecualikan id user terkait)
    $request->validate([
      'nip'            => 'required|string|max:50|unique:guru,nip,' . $guru->id,
      'nama_lengkap'   => 'required|string|max:255',
      'username'       => 'required|string|max:50|unique:users,username,' . $guru->user_id,
      'email'          => 'nullable|email|max:150|unique:users,email,' . $guru->user_id,
      'tempat_lahir'   => 'nullable|string|max:100',
      'tanggal_lahir'  => 'nullable|date',
      'jenis_kelamin'  => 'nullable|in:L,P,l,p',
      'alamat'         => 'nullable|string|max:255',
      'kontak'         => 'nullable|string|max:50',
      'photo'          => 'nullable|image|max:2048',
    ]);

    $this->ensureGuruRole();

    try {
      DB::transaction(function () use ($request, $guru) {

        // Update akun user terkait
        $user = $guru->user; // asumsi relasi belongsTo('user') sudah ada
        if ($user) {
          $user->name     = $request->nama_lengkap;
          $user->username = strtolower(str_replace(' ', '', $request->username));
          $user->email    = $request->email;
          $user->save();

          // pastikan role 'guru' tetap ada
          if (!$user->hasRole('guru')) {
            $user->assignRole('guru');
          }
        }

        // Upload foto (hapus lama bila ada)
        $data = [];
        if ($request->hasFile('photo')) {
          if ($guru->photo) {
            Storage::disk('public')->delete($guru->photo);
          }
          $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        // Update tabel guru (tanpa kolom username)
        $guru->update(array_merge($data, [
          'nip'            => $request->nip,
          'nama_lengkap'   => $request->nama_lengkap,
          'jenis_kelamin'  => $request->jenis_kelamin ? strtoupper($request->jenis_kelamin) : null,
          'tempat_lahir'   => $request->tempat_lahir,
          'tanggal_lahir'  => $request->tanggal_lahir,
          'alamat'         => $request->alamat,
          'kontak'         => $request->kontak,
        ]));
      });

      return redirect()->route('admin.guru.index')->with('success', 'Data Guru berhasil diperbarui');
    } catch (\Throwable $e) {
      return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
    }
  }

  public function destroy(Guru $guru) {
    try {
      DB::transaction(function () use ($guru) {
        // Hapus foto jika ada
        if ($guru->photo) {
          Storage::disk('public')->delete($guru->photo);
        }

        // Opsional: jika ingin MENGHAPUS user juga saat guru dihapus,
        // aktifkan blok berikut. Default: hanya hapus data guru.
        $user = $guru->user;
        $guru->delete();
        if ($user) { $user->delete(); }

        $guru->delete();
      });

      return redirect()->route('admin.guru.index')->with('success', 'Data Guru berhasil dihapus');
    } catch (\Throwable $e) {
      return redirect()->route('admin.guru.index')->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
  }

  public function import(Request $request) {
    $request->validate([
      'file' => 'required|mimes:xlsx,csv|max:20480', // naikkan limit bila perlu
    ]);

    $this->ensureGuruRole();

    Excel::import(new ImportGuru, $request->file('file'));

    if (session()->has('import_guru_failed')) {
      return back()->with('error', 'Beberapa data gagal diimpor karena field wajib kosong atau duplikat.');
    }

    return back()->with('success', 'Data guru berhasil diimpor.');
  }
}
