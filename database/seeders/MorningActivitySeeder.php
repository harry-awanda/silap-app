<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MorningActivity;

class MorningActivitySeeder extends Seeder {

  public function run(): void {
    MorningActivity::upsert([
      ['kode'=>'upacara',    'nama'=>'Upacara',     'sort_order'=>1, 'active'=>true],
      ['kode'=>'apel',       'nama'=>'APEL Pagi',   'sort_order'=>2, 'active'=>true],
      ['kode'=>'senam',      'nama'=>'Senam Pagi',  'sort_order'=>3, 'active'=>true],
      ['kode'=>'kerohanian', 'nama'=>'Kerohanian',  'sort_order'=>4, 'active'=>true],
      ['kode'=>'lainnya',    'nama'=>'Lainnya',     'sort_order'=>99,'active'=>true],
    ], ['kode'], ['nama','sort_order','active']);
  }
}