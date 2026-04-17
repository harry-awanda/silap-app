<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

trait BelongsToActiveTerm {
  protected static string $termColumn = 'term_id';
  protected static bool $useActiveTermScope = true;
  protected static string $activeScopeName = 'activeTerm';

  public static function bootBelongsToActiveTerm(): void {
    if (static::$useActiveTermScope) {
      static::addGlobalScope(static::$activeScopeName, function (Builder $builder) {
        $termId = static::resolveActiveTermId();
        if ($termId) {
          $table = $builder->getModel()->getTable();
          $builder->where($table . '.' . static::$termColumn, $termId);
        }
      });
    }

    static::creating(function (Model $model) {
      $col = static::$termColumn;

      if (!empty($model->getAttribute($col))) return;

      $termId = static::resolveActiveTermId();
      if ($termId) {
        $model->setAttribute($col, $termId);
      }
      // opsi: kalau mau ketat (fail fast), aktifkan ini:
    });
  }

  protected static function resolveActiveTermId(): ?int {
    // 1) Request attribute (paling benar)
    if (App::has('request')) {
      $req = App::make('request');
      $id = $req->attributes->get('activeTermId');
      if ($id) return (int) $id;
    }

    // 2) Cache fallback (pastikan setter cache kamu konsisten)
    $termId = Cache::get('active_term_id.v1'); // ✅ lebih stabil simpan ID saja
    if ($termId) return (int) $termId;

    // fallback lama (jika masih menyimpan object/array)
    $term = Cache::get('active_term.v1');
    if (is_object($term) && isset($term->id)) return (int) $term->id;
    if (is_array($term) && isset($term['id'])) return (int) $term['id'];

    return null;
  }

  public function scopeWithoutActiveTerm(Builder $query): Builder {
    return $query->withoutGlobalScope(static::$activeScopeName);
  }

  public function scopeForTerm(Builder $query, int $termId): Builder {
    $table = $query->getModel()->getTable();
    return $query->where($table . '.' . static::$termColumn, $termId);
  }

  public function scopeForTermOnly(Builder $query, int $termId): Builder {
    $table = $query->getModel()->getTable();
    return $query->withoutGlobalScope(static::$activeScopeName)
      ->where($table . '.' . static::$termColumn, $termId);
  }
}
