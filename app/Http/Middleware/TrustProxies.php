<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware {
  /**
   * Daftar proxy tepercaya.
   * Atur via .env TRUSTED_PROXIES (comma-separated) atau "all"/"*".
   * Biarkan kosong untuk tidak mempercayai proxy apa pun.
   */
  protected $proxies;

  /**
   * Header yang dipakai untuk mendeteksi informasi asli dari client/proxy.
   * Kita atur di __construct agar bisa conditional.
   */
  protected $headers;

  public function __construct() {
    // === Konfigurasi proxy tepercaya dari ENV ===
    $raw = env('TRUSTED_PROXIES', '');

    if ($raw === '*' || strtolower($raw) === 'all') {
      $this->proxies = '*';
    } elseif (trim($raw) !== '') {
      // contoh: "10.0.0.1,10.0.0.2"
      $this->proxies = array_map('trim', explode(',', $raw));
    } else {
      $this->proxies = null; // default: tidak ada proxy tepercaya
    }

    // === Kendali privasi: matikan pembacaan X-Forwarded-For ketika COLLECT_IP=false ===
    $collectIp = (bool) config('privacy.collect_ip', false);

    $base = Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_AWS_ELB;

    // Tambahkan X-Forwarded-For hanya jika kamu memang ingin "mengetahui" IP client
    $this->headers = $collectIp ? ($base | Request::HEADER_X_FORWARDED_FOR) : $base;
  }

  /**
   * Kita tidak lagi override REMOTE_ADDR dari Cloudflare.
   * Cukup serahkan ke parent (Laravel) agar honor header proxy sesuai $this->headers.
   */
  public function handle($request, Closure $next)
  {
    return parent::handle($request, $next);
  }
}
