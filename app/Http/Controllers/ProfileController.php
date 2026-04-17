<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller {
  // Menampilkan form edit profil
  public function edit() {
    $title = 'Informasi Akun';

    $user   = Auth::user();
    $guru   = $user->guru ?? null;   // relasi jika role guru/guru_bk
    $siswa  = $user->siswa ?? null;  // relasi jika role siswa

    // View tunggal untuk semua role. Blade akan kondisional.
    return view('profile.edit', compact('title', 'user', 'guru', 'siswa'));
  }

  // Update profil (selain photo dan password)
  public function update(Request $request) {
    $user  = Auth::user();
    $guru  = $user->guru ?? null;
    $siswa = $user->siswa ?? null;

    // Validasi dasar (berlaku untuk semua role)
    $request->validate([
      'username'      => 'required|string|max:255|unique:users,username,' . $user->id,
      'nama_lengkap'  => 'required|string|max:255',
    ]);

    // Normalisasi username
    $normalizedUsername = strtolower(preg_replace('/\s+/', '', $request->username));

    // Update data akun (Users)
    $user->update([
      'username' => $normalizedUsername,
      // Optional: sinkronkan name untuk role non-guru/non-siswa agar tampilan konsisten
      'name'     => $request->nama_lengkap,
    ]);

    // ====== Update data profil GURU (jika relasi ada) ======
    if ($guru) {
      $request->validate([
        'nip'           => 'nullable|string|max:50',
        'tempat_lahir'  => 'nullable|string|max:255',
        'tanggal_lahir' => 'nullable|date',
        'kontak'        => 'nullable|string|max:50',
        'alamat'        => 'nullable|string',
      ]);

      $guru->update([
        'nip'           => $request->nip,
        'nama_lengkap'  => $request->nama_lengkap,
        'tempat_lahir'  => $request->tempat_lahir,
        'tanggal_lahir' => $request->tanggal_lahir,
        'kontak'        => $request->kontak,
        'alamat'        => $request->alamat,
      ]);
    }

    // ====== Update data profil SISWA (jika relasi ada) ======
    if ($siswa) {
      // Catatan: hindari ubah NIS di halaman profil (biasanya kunci unik). Jika ingin, tambahkan validasi unique.
      $request->validate([
        'jenis_kelamin' => 'nullable|in:L,P,l,p',
        'tanggal_lahir' => 'nullable|date',
        'kontak'        => 'nullable|string|max:50',
        'alamat'        => 'nullable|string',
      ]);

      $payload = [
        'nama_lengkap'  => $request->nama_lengkap,
        'jenis_kelamin' => $request->jenis_kelamin,
        'tanggal_lahir' => $request->tanggal_lahir,
        'kontak'        => $request->kontak,
        'alamat'        => $request->alamat,
      ];

      // Filter null agar tidak menimpa kolom yang mungkin tidak ada
      $siswa->update(array_filter($payload, fn($v) => !is_null($v)));
    }

    return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
  }

  // Update photo profil (dengan dukungan crop base64)
  public function updatePhoto(Request $request) {
    $user  = Auth::user();
    $guru  = $user->guru ?? null;
    $siswa = $user->siswa ?? null;
    
    // ✅ Batasi hak akses: siswa tidak boleh ubah foto (Spatie)
    if ($user->hasRole('siswa')) {
      abort(403, 'Siswa tidak diizinkan mengubah foto profil.');
      // atau kalau mau tetap balik dengan error:
      // return back()->withErrors(['photo' => 'Siswa tidak diizinkan mengubah foto.']);
    }

    
    // Jika ada base64 cropped, file 'photo' boleh kosong. Jika tidak ada, wajib unggah file.
    $rules = [
      'photo'   => ['nullable', 'image', 'max:2048'],
      'cropped' => ['nullable', 'string'], // dataURL
    ];
    $request->validate($rules);
    
    $deleteOld = function ($path) {
      if ($path && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
      }
    };
    
    $savePath = null;
    
    // 1) Prioritas: cropped base64
    if ($request->filled('cropped') && strpos($request->cropped, 'data:image') === 0) {
      $dataUrl = $request->cropped;
      
      // Ekstrak mime & data
      if (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/i', $dataUrl, $m)) {
        $ext  = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $data = base64_decode($m[2], true);
        
        if ($data === false) {
          return back()->withErrors(['photo' => 'Gagal memproses data gambar.']);
        }
        
        // (Opsional) Validasi ukuran base64 ~2MB
        if (strlen($m[2]) * 3 / 4 > 2 * 1024 * 1024) {
          return back()->withErrors(['photo' => 'Ukuran hasil crop melebihi 2 MB. Perkecil sedikit.']);
        }
        
        $filename = 'photos/user-' . $user->id . '-' . time() . '.' . $ext;
        Storage::disk('public')->put($filename, $data);
        $savePath = $filename;
      } else {
        return back()->withErrors(['photo' => 'Format data crop tidak valid.']);
      }
    }

    // 2) Fallback: file upload langsung (tanpa crop)
    elseif ($request->hasFile('photo')) {
      $savePath = $request->file('photo')->store('photos', 'public');
    } else {
      return back()->withErrors(['photo' => 'Tidak ada gambar yang dikirim.']);
    }
    
    // Simpan dan hapus lama
    if ($guru) {
      $deleteOld($guru->photo);
      $guru->photo = $savePath;
      $guru->save();
    } elseif ($siswa) {
      // seharusnya tidak masuk (karena diblok di atas), tapi tetap aman.
      $deleteOld($siswa->photo);
      $siswa->photo = $savePath;
      $siswa->save();
    } else {
      $deleteOld($user->photo ?? null);
      $user->photo = $savePath;
      $user->save();
    }
    
    return redirect()->route('profile.edit')->with('success', 'Foto profil berhasil diperbarui.');
  }

  // Update password
  public function updatePassword(Request $request) {
    $user = Auth::user();

    $request->validate([
      'current_password' => 'required|string',
      'new_password'     => 'required|string|min:8|confirmed',
    ]);

    if (!Hash::check($request->current_password, $user->password)) {
      return back()->withErrors(['current_password' => 'Password saat ini tidak cocok.']);
    }

    $user->update(['password' => bcrypt($request->new_password)]);
    return redirect()->route('profile.edit')->with('success', 'Password berhasil diperbarui.');
  }
}