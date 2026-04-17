<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Support\HomeroomContext;
use App\Services\WaliKelas\DashboardService;

class DashboardController extends Controller {
  public function __construct(private DashboardService $service) {}

  public function index(Request $r) {
    // konteks wali kelas (term aktif)
    [$assignment, $classroomId] = HomeroomContext::forAuthed($r);
    if (!$assignment || !$classroomId) {
      abort(403, 'Anda tidak memiliki kelas binaan aktif.');
    }

    $termId        = (int) $assignment->term_id;
    $classroom     = $assignment->classroom;
    $today         = Carbon::today()->toDateString();
    $formattedDate = Carbon::today()->format('d-m-Y');
    $title         = 'Dashboard Wali Kelas';
    $namaKelasWali = $classroom?->nama_kelas ?? '-';

    [$rekapStatusWali, $recentActivitiesWali, $topSiswaWali] = $this->service->build(
      termId: $termId,
      classroomId: (int) $classroomId,
      dateYmd: $today,
      userId: (int) auth()->id(),
      cacheSeconds: 90
    );

    return view('wali-kelas.dashboard', compact(
      'title', 'today', 'formattedDate', 'namaKelasWali',
      'rekapStatusWali', 'recentActivitiesWali', 'topSiswaWali'
    ));
  }
}