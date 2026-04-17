<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
  // Method untuk menampilkan form login
  public function showLoginForm() {
    return view('auth.login');
  }

  // Method untuk menangani proses login
  public function postLogin(Request $request) {
    // Validasi input
    $request->validate([
      'username' => 'required|string',
      'password' => 'required|string',
    ]);

    $remember = (bool) $request->boolean('remember');

    // Ambil data input
    $credentials = [
      'username' => $request->username,
      'password' => $request->password,
    ];

    // Cek kredensial
    if (Auth::attempt($credentials, $remember)) {
      // Regenerasi sesi setelah login untuk mencegah session fixation
      $request->session()->regenerate();
      // Jika berhasil login, redirect ke dashboard atau halaman yang sesuai
      return redirect()->route('dashboard')->with('loginSuccess', 'Selamat datang kembali');
    } else {
      // Jika gagal login, redirect kembali ke halaman login dengan pesan error
      return redirect()->back()->with('loginError', 'Username atau password salah.')->withInput();
    }
  }

  // Method untuk logout
  public function logout(Request $request) {
    // Melakukan proses logout pengguna yang sedang login
    Auth::logout();
    // Menghapus semua sesi pengguna untuk mencegah sesi yang masih aktif setelah logout
    $request->session()->invalidate();
    // Menghasilkan kembali token sesi baru untuk menghindari serangan CSRF 
    $request->session()->regenerateToken();
    return redirect()->route('login')->with('logout', 'Anda telah logout.');
  }
}
