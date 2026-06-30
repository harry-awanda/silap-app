<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model {
  protected $fillable = ['key', 'value'];

  public static function getValue(string $key, ?string $default = null): ?string {
    $value = static::query()->where('key', $key)->value('value');

    return $value === null ? $default : (string) $value;
  }

  public static function setValue(string $key, ?string $value): self {
    return static::query()->updateOrCreate(
      ['key' => $key],
      ['value' => $value]
    );
  }
}
