<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyController extends Controller {
  public function index() {
    $title = 'Kebijakan Privasi';
    // render view resources/views/privacy.blade.php
    return view('privacy', compact('title'));
  }
}