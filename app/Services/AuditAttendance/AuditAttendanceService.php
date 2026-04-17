<?php

namespace App\Services\AuditAttendance;

use App\Queries\AuditAttendance\{
  ClassroomsForTermQuery,
  GlobalRecapQuery,
  PerClassRecapQuery
};

class AuditAttendanceService {
  public function __construct(
    private ClassroomsForTermQuery $classrooms,
    private GlobalRecapQuery $global,
    private PerClassRecapQuery $perClass
  ) {}

  public function pageData(int $termId, string $date, ?string $status, ?int $kelasId): array {
    $title = 'Audit Presensi';

    $global = $this->global->get($termId, $date, $kelasId);
    $kelasRekap = $this->perClass->get($termId, $date, $kelasId);
    $classrooms = $this->classrooms->get($termId);

    return [
      'title' => $title,
      'date' => $date,
      'status' => $status,
      'kelasId' => $kelasId,
      'rekapStatus' => $global['rekapStatus'],
      'kelasRekap' => $kelasRekap,
      'classrooms' => $classrooms,
    ];
  }
}