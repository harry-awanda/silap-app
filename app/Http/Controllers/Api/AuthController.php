<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
  /**
   * Login dan buat personal access token Sanctum.
   */
  public function login(Request $request) {
    $data = $request->validate([
      'username' => 'required|string',
      'password' => 'required|string',
      'device_name' => 'required|string',
    ]);
    
    if (!Auth::attempt(['username' => $data['username'], 'password' => $data['password']])) {
      return response()->json(['message' => 'Username atau password salah'], 422);
    }
    
    $user = Auth::user();
    $token = $user->createToken($data['device_name'])->plainTextToken;
    
    return response()->json([
      'token' => $token,
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'role' => $user->role,
      ]
    ]);
  }

  /**
   * Logout dan hapus token saat ini.
   */
  public function logout(Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Berhasil logout']);
  }

  /**
   * Ambil data profil pengguna aktif.
   */
  public function me(Request $request) {
    $user = $request->user();
    return response()->json([
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email,
      'role' => $user->role,
    ]);
  }
}