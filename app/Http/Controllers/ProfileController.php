<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit() {
        return view('pages.profile.edit');
    }

    public function update(Request $request) {
        return back()->with('success', 'Profile updated.');
    }

    public function destroy(Request $request) {
        return redirect('/login')->with('success', 'Account deleted.');
    }
}