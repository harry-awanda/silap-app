<?php

namespace App\Models\Concerns;

use App\Support\ActiveTermCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

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
    });
  }

  protected static function resolveActiveTermId(): ?int {
    if (App::has('request')) {
      $req = App::make('request');
      $id = $req->attributes->get('activeTermId');
      if ($id) return (int) $id;
    }

    return ActiveTermCache::activeTermId();
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
