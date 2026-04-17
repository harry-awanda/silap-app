<?php

namespace App\Services\Face;

use Illuminate\Support\Facades\Crypt;

class FaceCryptoService {
  public function encryptEmbedding(string $rawBinary): string {
    // simpan sebagai encrypted string (binary aman disimpan di BLOB)
    return Crypt::encryptString(base64_encode($rawBinary));
  }

  public function decryptEmbedding(string $encrypted): string {
    $b64 = Crypt::decryptString($encrypted);
    $raw = base64_decode($b64, true);
    
    if ($raw === false) return '';
    
    // pastikan minimal 512 bytes (Float32Array 128)
    if (strlen($raw) < 128 * 4) return '';
    
    return $raw;
  }
}
