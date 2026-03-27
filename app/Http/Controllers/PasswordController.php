<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function edit() {
        return view('pages.auth.password');
    }

    public function update(Request $request) {
        // logika update password
        return back()->with('success', 'Password updated.');
    }
}