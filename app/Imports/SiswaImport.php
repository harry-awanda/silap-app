<?php

namespace App\Imports;

use App\Models\{User, Siswa};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{DB, Log, Hash};
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\{
  Importable,
  SkipsOnFailure,
  SkipsFailures,
  SkipsOnError,
  SkipsEmptyRows,
  ToModel,
  WithHeadingRow,
  WithValidation
};

use PhpOffice\PhpSpreadsheet\Shared\Date;
use Spatie\Permission\Models\Role;

class SiswaImport implements
  ToModel,
  WithHeadingRow,
  WithValidation,
  SkipsOnError,
  SkipsOnFailure,
  SkipsEmptyRows
{
  use Importable, SkipsFailures;

  protected string $siswaRole = 'siswa';
  protected string $guard = 'web';

  protected int $forcedClassroomId;
  protected int $forcedTermId;

  protected int $importedCount = 0;
  
  protected int $updatedCount = 0;
  protected int $skippedCount = 0;
  
  public function getUpdatedCount(): int { return $this->updatedCount; }
  public function getSkippedCount(): int { return $this->skippedCount; }

  protected array $columns = [
    'nis'           => 'nis',
    'nama_lengkap'  => 'nama_lengkap',
    'username'      => 'username',
    'email'         => 'email',
    'jenis_kelamin' => 'jenis_kelamin',
    // 'kelas'         => 'nama_kelas',     // diabaikan untuk penentuan classroom
    // 'id_kelas'      => 'classroom_id',   // diabaikan untuk penentuan classroom
    'tempat_lahir'  => 'tempat_lahir',
    'tanggal_lahir' => 'tanggal_lahir',
    'agama'         => 'agama',
    'kontak'        => 'kontak',
    'alamat'        => 'alamat',
    
    // data orang tua / wali
    'nama_ayah'       => 'nama_ayah',
    'pekerjaan_ayah'  => 'pekerjaan_ayah',
    'kontak_ayah'     => 'kontak_ayah',
    'nama_ibu'        => 'nama_ibu',
    'pekerjaan_ibu'   => 'pekerjaan_ibu',
    'kontak_ibu'      => 'kontak_ibu',
    'nama_wali_murid' => 'nama_wali_murid',
    'kontak_wali'     => 'kontak_wali',
    'alamat_orangtua' => 'alamat_orangtua',
    'alamat_wali'     => 'alamat_wali',
  ];

  public function __construct(int $forcedClassroomId, int $forcedTermId)
  {
    $this->forcedClassroomId = $forcedClassroomId;
    $this->forcedTermId = $forcedTermId;

    Role::firstOrCreate([
      'name' => $this->siswaRole,
      'guard_name' => $this->guard,
    ]);
  }

  public function rules(): array {
    return [
      'nis'           => ['required', 'max:32'],
      'nama_lengkap'  => ['required', 'string', 'max:150'],
      'username'      => ['nullable', 'string', 'max:50'],
      'email'         => ['nullable', 'email', 'max:150'],
      'jenis_kelamin' => ['nullable', 'in:L,P,l,p,laki-laki,perempuan,laki'],
      'tanggal_lahir' => ['nullable'],
      'kontak'        => ['nullable', 'max:50', 'regex:/^[0-9+()\s.-]+$/'],
      'alamat'        => ['nullable', 'string', 'max:255'],
    ];
  }

  public function model(array $row) {
    $row = $this->normalizeRow($row);

    $row['nis']      = $this->stringifyCell($row['nis'] ?? null);
    $row['username'] = $this->stringifyCell($row['username'] ?? null);

    $row['nis'] = trim((string) ($row['nis'] ?? ''));

    if (empty($row['nis']) || empty($row['nama_lengkap'])) {
      Log::warning('[SiswaImport] Skip row (NIS / nama kosong)', ['row' => $row]);
      return null;
    }

    // $usernameBase = $row['username'] ?: ($row['nis'] ?: Str::slug($row['nama_lengkap'], ''));
    // $username = $this->ensureUniqueUsername($this->sanitizeUsername($usernameBase));
    $usernameBase = $row['username'] ?: $row['nis'];
    $username = $this->sanitizeUsername($usernameBase);

    $email = $row['email'] ?? null;
    $tanggalLahir = $this->parseDate($row['tanggal_lahir'] ?? null);

    $classroomId = $this->forcedClassroomId;
    $termId      = $this->forcedTermId;

    return DB::transaction(function () use ($row, $username, $email, $tanggalLahir, $classroomId, $termId) {
      
      // kalau siswa (berdasarkan NIS) sudah ada, cukup pastikan pivot term ada lalu SKIP create user baru
      $existingSiswa = Siswa::where('nis', $row['nis'])->first();
      
      if ($existingSiswa) {
        // Pivot term-classroom tetap dijaga
        DB::table('term_classroom_siswa')->updateOrInsert(
          ['term_id' => $termId, 'siswa_id' => $existingSiswa->id],
          [
            'classroom_id' => $classroomId,
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
          ]
        );
      
        // Fill-only update
        $updates = [];
      
        // Field siswa (fill-only)
        $this->applyFillIfEmpty($updates, $existingSiswa, [
          'tempat_lahir' => 'tempat_lahir',
          'agama'        => 'agama',
          'alamat'       => 'alamat',
          'kontak'       => 'kontak',
        ], $row);
        
        // ===============================
        // TANGGAL LAHIR (FILL-ONLY, AMAN)
        // ===============================
        $rawTgl = $existingSiswa->getRawOriginal('tanggal_lahir');
        
        if ($this->isBlankDateRaw($rawTgl) && !empty($tanggalLahir)) {
          $updates['tanggal_lahir'] = $tanggalLahir; // format Y-m-d
        }
      
        // Jenis kelamin fill-only (pakai normalisasi)
        if ($this->isBlank($existingSiswa->jenis_kelamin) && !$this->isBlank($row['jenis_kelamin'] ?? null)) {
          $updates['jenis_kelamin'] = $this->normalizeJK($row['jenis_kelamin']);
        }
      
        // Data orang tua / wali (fill-only)
        $this->applyFillIfEmpty($updates, $existingSiswa, [
          'nama_ayah'       => 'nama_ayah',
          'pekerjaan_ayah'  => 'pekerjaan_ayah',
          'kontak_ayah'     => 'kontak_ayah',
          'nama_ibu'        => 'nama_ibu',
          'pekerjaan_ibu'   => 'pekerjaan_ibu',
          'kontak_ibu'      => 'kontak_ibu',
          'nama_wali_murid' => 'nama_wali_murid',
          'kontak_wali'     => 'kontak_wali',
          'alamat_orangtua' => 'alamat_orangtua',
          'alamat_wali'     => 'alamat_wali',
        ], $row);
      
        // Snapshot kelas (opsional: biasanya memang ingin ikut forced classroom)
        if ((int) $existingSiswa->classroom_id !== (int) $classroomId) {
          $updates['classroom_id'] = $classroomId;
        }
      
        if (!empty($updates)) {
          $existingSiswa->update($updates);
          $this->updatedCount++;
        } else {
          $this->skippedCount++;
        }
      
        // Optional: isi email user jika kosong
        if ($existingSiswa->user_id && !$this->isBlank($email)) {
          $u = User::find($existingSiswa->user_id);
          if ($u && $this->isBlank($u->email)) {
            $u->email = $email;
            $u->save();
          }
        }
      
        return null;
      }

      // return null; // penting agar ToModel tidak bikin record baru
      // === USER ===
      $rawNis = $this->stringifyCell($row['nis']);
      $password = Hash::make($rawNis ?: 'password');

      $user = User::firstOrCreate(
        ['username' => $username],
        [
          'name'     => $row['nama_lengkap'],
          'email'    => $email,
          'password' => $password,
        ]
      );

      if ($user->wasRecentlyCreated) {
        $user->must_change_password = true;
        $user->password_changed_at = null;
        $user->save();
      }

      if (!$user->hasRole($this->siswaRole)) {
        $user->assignRole($this->siswaRole);
      }

      // === SISWA MASTER ===
      $siswa = Siswa::create([
        'nis'           => $row['nis'],
        'user_id'       => $user->id,
        'classroom_id'  => $classroomId,
        'nama_lengkap'  => $row['nama_lengkap'],
        'jenis_kelamin' => $this->normalizeJK($row['jenis_kelamin'] ?? null),
        'tanggal_lahir' => $tanggalLahir,
        'kontak'        => $row['kontak'] ?? null,
        'alamat'        => $row['alamat'] ?? null,
      ]);

      // === PIVOT TERM + CLASSROOM (WAJIB) ===
      DB::table('term_classroom_siswa')->updateOrInsert(
        [
          'term_id'  => $termId,
          'siswa_id' => $siswa->id,
        ],
        [
          'classroom_id' => $classroomId,
          'status'       => 'active',
          'created_at'   => now(),
          'updated_at'   => now(),
        ]
      );

      $this->importedCount++;

      return $siswa;
    });
  }

  public function getImportedCount(): int {
    return $this->importedCount;
  }

  // =========================
  // Helpers
  // =========================
  protected function normalizeRow(array $row): array {
    $out = [];
  
    foreach ($this->columns as $sheetKey => $localKey) {
  
      // kalau localKey sudah terisi, jangan ditimpa alias lain
      if (
        array_key_exists($localKey, $out) &&
        $out[$localKey] !== null &&
        trim((string) $out[$localKey]) !== ''
      ) {
        continue;
      }
  
      $value = null;
      foreach ($row as $k => $v) {
        if (Str::lower($k) === Str::lower($sheetKey)) {
          $value = $v;
          break;
        }
      }
  
      // kalau kolom alias tidak ada, jangan overwrite jadi null
      if ($value === null) {
        continue;
      }
  
      $out[$localKey] = $value;
    }
  
    return $out;
  }

  protected function sanitizeUsername(string $username): string {
    $username = Str::lower($username);
    $username = str_replace(' ', '', $username);
    return preg_replace('/[^a-z0-9._-]/', '', $username);
  }

  protected function stringifyCell($value): ?string {
    if ($value === null) return null;
    if (is_numeric($value)) {
      return preg_replace('/\.0+$/', '', (string) $value);
    }
    return trim((string) $value);
  }
  
  protected function parseDate($value): ?string {
    try {
      if ($value === null) return null;
  
      // Kadang PhpSpreadsheet mengembalikan object tanggal
      if ($value instanceof \DateTimeInterface) {
        return Carbon::instance($value)->format('Y-m-d');
      }
  
      // Serial number Excel (angka)
      if (is_numeric($value)) {
        return Carbon::instance(Date::excelToDateTimeObject((float) $value))->format('Y-m-d');
      }
  
      // String
      if (is_string($value)) {
        $value = trim($value);
        if ($value === '') return null;
  
        // String angka: tetap treat sebagai serial Excel
        if (is_numeric($value)) {
          return Carbon::instance(Date::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }
  
        // Tangani format umum Indonesia: dd/mm/yyyy atau dd-mm-yyyy
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y'] as $fmt) {
          try {
            return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
          } catch (\Throwable $e) {
            // lanjut format berikutnya
          }
        }
  
        // Tangani ISO / format lain yang dikenali Carbon
        try {
          return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
          return null;
        }
      }
    } catch (\Throwable $e) {
      Log::warning('[SiswaImport] Gagal parse tanggal', [
        'value' => $value,
        'type'  => gettype($value),
        'err'   => $e->getMessage(),
      ]);
    }
  
    return null;
  }

  protected function isBlankDateRaw($raw): bool {
    if ($raw === null) return true;
    
    $raw = trim((string) $raw);
    
    return $raw === ''
      || $raw === '0000-00-00'
      || $raw === '0000-00-00 00:00:00';
  }

  protected function isBlank($v): bool {
    return $v === null || trim((string) $v) === '';
  }
  
  protected function cell($v): ?string {
    $s = $this->stringifyCell($v);
    return $this->isBlank($s) ? null : $s;
  }
  
  /**
   * Isi $updates[$field] jika:
   * - kolom di DB kosong
   * - nilai dari Excel ada (tidak kosong)
   */
  protected function fillIfEmpty(array &$updates, $model, string $field, $incoming): void {
    if (!$this->isBlank($model->{$field} ?? null)) return;
  
    $value = is_string($incoming) || is_numeric($incoming)
      ? $this->cell($incoming)
      : $incoming;
  
    if ($this->isBlank($value)) return;
  
    $updates[$field] = $value;
  }
  
  /**
   * Helper untuk banyak field sekaligus:
   * $map = ['db_field' => $rowKeyOrValue]
   */
  protected function applyFillIfEmpty(array &$updates, $model, array $map, array $row): void {
    foreach ($map as $dbField => $rowKey) {
      $incoming = is_string($rowKey) ? ($row[$rowKey] ?? null) : $rowKey;
      $this->fillIfEmpty($updates, $model, $dbField, $incoming);
    }
  }

  protected function ensureUniqueUsername(string $base): string {
    $base = $base ?: 'user';
    $username = $base;
    $i = 1;

    while (User::where('username', $username)->exists()) {
      $i++;
      $username = $base . $i;
    }
    return $username;
  }

  protected function normalizeJK(?string $jk): ?string {
    if (!$jk) return null;
    $jk = Str::lower(trim($jk));

    if (in_array($jk, ['l', 'laki-laki', 'laki'])) return 'L';
    if (in_array($jk, ['p', 'perempuan'])) return 'P';

    return null;
  }

  public function onError(\Throwable $e): void {
    Log::error('[SiswaImport] Error: ' . $e->getMessage());
  }
}