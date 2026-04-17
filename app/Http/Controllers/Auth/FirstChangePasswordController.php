<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FirstChangePasswordController extends Controller {
  public function create() {
    $title = 'Ganti Password Pertama Kali';
    return view('auth.first-change-password', compact('title'));
  }
  
  public function store(Request $request) {
    $request->validate(
      [
        'password' => [
          'required',
          'string',
          'min:8',
          'confirmed',
          'regex:/[A-Z]/', // wajib ada huruf kapital
          function ($attr, $value, $fail) use ($request) {
            // cegah password = NIS/username (opsional, tapi bagus)
            if (strcasecmp($value, $request->user()->username) === 0) {
              $fail('Password baru tidak boleh sama dengan NIS/username.');
            }
          },
        ],
      ],
      [
        'password.required'  => 'Password wajib diisi.',
        'password.min'       => 'Password minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'password.regex'     => 'Password harus mengandung minimal satu huruf kapital (A–Z).',
      ]
    );
    
    $user = $request->user();
    $user->forceFill([
      'password' => Hash::make($request->password),
      'must_change_password' => false,
      'password_changed_at'  => now(),
    ])->save();
    
    return redirect()->route('dashboard')->with('success', 'Password berhasil diubah.');
  }
}