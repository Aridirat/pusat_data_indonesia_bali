<?php

namespace App\Http\Controllers;

class TwoFactorController extends Controller
{
    public function show() {
        return view('pages.auth.twofactor');
    }
}