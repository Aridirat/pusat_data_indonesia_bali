<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginView()
    {
        if (Auth::check()){
            return back();
        }
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        if (Auth::check()){
            return back();
        }
        
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Cari user berdasarkan username
        $user = User::where('username', $request->username)->first();

        // Username tidak ditemukan
        if (!$user) {
            return back()
                ->with('error', 'Username tidak ditemukan.')
                ->withInput();
        }

        // Jika status tidak aktif
        if ($user->activation !== 'activated') {
            return back()->with('error', 'Akun belum aktif.');
        }

        // Password salah
        if (!Hash::check($request->password, $user->password)) {
            return back()
                ->with('error', 'Password yang Anda masukkan salah.')
                ->withInput();
        }

        // Login berhasil
        Auth::login($user);
        $request->session()->regenerate();
        return redirect('/')->with('success', 'Berhasil masuk.');
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}