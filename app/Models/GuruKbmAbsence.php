<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToActiveTerm;

class GuruKbmAbsence extends Model {
  use BelongsToActiveTerm;

  protected $fillable = ['term_id','agenda_piket_id','guru_id','status','keterangan'];

  public function agendaPiket(): BelongsTo {
    return $this->belongsTo(AgendaPiket::class);
  }

  public function guru(): BelongsTo {
    return $this->belongsTo(Guru::class);
  }
}
