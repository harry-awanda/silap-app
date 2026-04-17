<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UserPasswordController extends Controller {

  private function generateAlphaNumPassword(int $length = 12): string {
    $length = max(8, $length);

    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $digit = '0123456789';
    $all   = $lower.$upper.$digit;

    $chars = [];

    // pastikan ada kombinasi minimal (kalau length memungkinkan)
    if ($length >= 3) {
      $chars[] = $lower[random_int(0, strlen($lower) - 1)];
      $chars[] = $upper[random_int(0, strlen($upper) - 1)];
      $chars[] = $digit[random_int(0, strlen($digit) - 1)];
    }

    // isi sisa karakter
    while (count($chars) < $length) {
      $chars[] = $all[random_int(0, strlen($all) - 1)];
    }

    // acak urutan
    for ($i = count($chars) - 1; $i > 0; $i--) {
      $j = random_int(0, $i);
      [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
    }

    return implode('', $chars);
  }

  public function resetTemp(Request $request, User $user) {
    $length = (int) $request->input('length', 12);
    $length = $length >= 8 ? $length : 12;

    // hanya huruf kecil, huruf besar, angka (tanpa karakter spesial)
    $tempPassword = $this->generateAlphaNumPassword($length);

    $user->forceFill([
      'password' => Hash::make($tempPassword),
      'must_change_password' => true,
      'password_changed_at' => null,
      'remember_token' => Str::random(60), // putuskan remember-me session
    ])->save();

    // Jika pakai Sanctum atau PAT, putuskan juga
    if (method_exists($user, 'tokens')) {
      $user->tokens()->delete();
    }

    Log::info('Admin set temporary password', [
      'admin_id' => $request->user()->id,
      'user_id'  => $user->id,
      'ip'       => $request->ip(),
      'time'     => now()->toDateTimeString(),
    ]);

    // Tampilkan SEKALI ke admin via flash (jangan simpan ke log/db!)
    return back()->with([
      'success' => "Password sementara untuk {$user->name} berhasil dibuat. User akan diminta mengganti saat login.",
      'temp_password' => $tempPassword,
    ]);
  }
}
