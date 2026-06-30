<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QrToken;
use App\Support\LateAttendanceQr;

class QrTokenController extends Controller {

  public function index() {
    $title = 'Manajemen QR Token';
    $stats = [
      'total' => QrToken::count(),
      'expired' => QrToken::where('expires_at', '<', now())->count(),
      'used' => QrToken::whereNotNull('used_at')->count(),
      'active' => QrToken::where('expires_at', '>=', now())->whereNull('used_at')->count(),
    ];

    $latest = QrToken::orderByDesc('id')->limit(20)->get();
    $lateQrUrl = LateAttendanceQr::url();

    return view('admin.qr_tokens.index', compact('stats', 'latest', 'title', 'lateQrUrl'));
  }

  public function generateLateQr(Request $request) {
    LateAttendanceQr::generate();

    return back()->with('success', 'QR presensi terlambat berhasil digenerate. QR lama tidak berlaku lagi.');
  }

  public function cleanup(Request $request) {
    $data = $request->validate([
      'mode' => ['required', 'in:expired,used,expired_or_used,before_date,all'],
      'before_date' => ['nullable', 'date'],
    ]);

    $q = QrToken::query();

    $deleted = 0;

    switch ($data['mode']) {
      case 'expired':
        $deleted = $q->where('expires_at', '<', now())->delete();
        break;

      case 'used':
        $deleted = $q->whereNotNull('used_at')->delete();
        break;

      case 'expired_or_used':
        $deleted = $q->where(function ($qq) {
          $qq->where('expires_at', '<', now())
             ->orWhereNotNull('used_at');
        })->delete();
        break;

      case 'before_date':
        $date = $data['before_date'] ? now()->parse($data['before_date'])->endOfDay() : now()->subDays(7);
        $deleted = $q->where('created_at', '<=', $date)->delete();
        break;

      case 'all':
        $deleted = $q->delete();
        break;
    }

    return back()->with('success', "Cleanup selesai. Token terhapus: {$deleted}");
  }
}
