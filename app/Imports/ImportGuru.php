<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Guru;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use Spatie\Permission\Models\Role;

class ImportGuru implements ToModel, WithHeadingRow,
  WithValidation, SkipsOnError, SkipsOnFailure {

  use Importable, SkipsFailures;

  protected string $guruRole = 'guru';
  protected string $guard = 'web';

  /**
   * Mapping heading sheet -> key lokal.
   * Catatan: untuk jenis_kelamin kita dukung dua nama heading: 'jenis_kelamin' dan 'jk'.
   */
  protected array $columns = [
    'nip'           => 'nip',
    'nama_lengkap'  => 'nama_lengkap',
    'username'      => 'username',
    'email'         => 'email',
    'no_hp'         => 'no_hp',
    'tgl_lahir'     => 'tanggal_lahir',
    // heading alternatif untuk JK:
    'jenis_kelamin' => 'jenis_kelamin',
    'jk'            => 'jenis_kelamin',
  ];

  public function __construct() {
    Role::firstOrCreate(['name' => $this->guruRole, 'guard_name' => $this->guard]);
  }

  public function rules(): array {
    return [
      'nip'           => ['required', 'string', 'max:32'],
      'nama_lengkap'  => ['required', 'string', 'max:150'],
      'username'      => ['required', 'string', 'max:50'],
      'email'         => ['nullable', 'email', 'max:150'],
      'no_hp'         => ['nullable', 'string', 'max:50'],
      'tgl_lahir'     => ['nullable'],
      'jenis_kelamin' => ['nullable', 'in:L,P,l,p,Laki-laki,laki-laki,Laki,Perempuan,perempuan'],
      'jk'            => ['nullable', 'in:L,P,l,p,Laki-laki,laki-laki,Laki,Perempuan,perempuan'],
    ];
  }

  public function model(array $row) {
    $row = $this->normalizeRow($row);

    if (empty($row['nip']) || empty($row['nama_lengkap']) || empty($row['username'])) {
      Log::warning('[ImportGuru] Row dilewati karena field wajib kosong.', ['row' => $row]);
      return null;
    }

    $username        = $this->sanitizeUsername($row['username']);
    $email           = $row['email'] ?? null;
    $defaultPassword = env('IMPORT_GURU_DEFAULT_PASSWORD') ?? env('ADMIN_PASSWORD') ?? 'password';
    $tanggalLahir    = $this->parseDate($row['tanggal_lahir'] ?? null);
    $jk              = $this->normalizeJK($row['jenis_kelamin'] ?? null);

    return DB::transaction(function () use ($row, $username, $email, $defaultPassword, $tanggalLahir, $jk) {

      // 1) User
      $user = User::firstOrCreate(
        ['username' => $username],
        [
          'name'     => $row['nama_lengkap'],
          'email'    => $email,
          'password' => Hash::make($defaultPassword),
        ]
      );

      if (!$user->hasRole($this->guruRole)) {
        $user->assignRole($this->guruRole);
      }

      // 2) Guru
      $guru = Guru::firstOrCreate(
        ['nip' => $row['nip']],
        [
          'user_id'       => $user->id,
          'nama_lengkap'  => $row['nama_lengkap'],
          'kontak'        => $row['no_hp'] ?? null,
          'tanggal_lahir' => $tanggalLahir,
          'jenis_kelamin' => $jk, // <— penting
        ]
      );

      // Lengkapi record lama jika belum terhubung user atau JK kosong
      $needSave = false;

      if (is_null($guru->user_id)) {
        $guru->user_id = $user->id;
        $needSave = true;
      }

      // Isi/rapikan JK jika sebelumnya null
      if (!$guru->jenis_kelamin && $jk) {
        $guru->jenis_kelamin = $jk;
        $needSave = true;
      }

      if ($needSave) $guru->save();

      return $guru;
    });
  }

  protected function normalizeRow(array $row): array {
    $out = [];
    // dukung heading ganda (jenis_kelamin/jk) -> satu key lokal 'jenis_kelamin'
    foreach ($this->columns as $sheetKey => $localKey) {
      foreach ($row as $k => $v) {
        if (Str::lower($k) === Str::lower($sheetKey)) {
          // jika sudah terisi dari alias lain, jangan timpa (prioritas jenis_kelamin > jk)
          if (!array_key_exists($localKey, $out) || is_null($out[$localKey])) {
            $out[$localKey] = $v;
          }
        }
      }
      if (!array_key_exists($localKey, $out)) {
        $out[$localKey] = null;
      }
    }
    return $out;
  }

  protected function sanitizeUsername(string $username): string {
    $username = Str::lower($username);
    $username = str_replace(' ', '', $username);
    return preg_replace('/[^a-z0-9._-]/', '', $username);
  }

  protected function parseDate($value): ?string {
    try {
      if (is_numeric($value)) {
        return Date::excelToDateTimeObject($value)->format('Y-m-d');
      }
      if (is_string($value) && !empty($value)) {
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
      }
    } catch (\Throwable $e) {
      Log::warning('[ImportGuru] Gagal parse tanggal.', ['value' => $value, 'err' => $e->getMessage()]);
    }
    return null;
  }

  /**
   * Normalisasi JK menjadi 'L' atau 'P'
   */
  protected function normalizeJK(?string $jk): ?string {
    if (!$jk) return null;
    $jk = Str::lower(trim($jk));
    // hilangkan spasi/karakter tak perlu
    $jk = preg_replace('/\s+/', '', $jk);

    if (in_array($jk, ['l', 'laki-laki', 'laki', 'lakilaki'])) return 'L';
    if (in_array($jk, ['p', 'perempuan'])) return 'P';

    return null;
  }

  public function onError(\Throwable $e): void {
    Log::error('[ImportGuru] Row error: '.$e->getMessage());
  }
}
