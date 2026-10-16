<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * GET /login - form login pustakawan.
     * Email terakhir dibaca dari cookie agar pengguna tidak perlu mengetik ulang.
     */
    public function showLogin(Request $request): View
    {
        $emailTerakhir = $request->cookie('sipus_last_email');

        return view('auth.login', compact('emailTerakhir'));
    }

    /**
     * POST /login - autentikasi dengan Auth::attempt().
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email'    => 'Format :attribute tidak valid.',
        ], [
            'email'    => 'Email',
            'password' => 'Password',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // WAJIB: ganti ID sesi setelah login untuk mencegah Session Fixation.
            $request->session()->regenerate();

            // State sesi (disimpan di server, tabel sessions).
            session(['login_timestamp' => now()->format('d M Y, H:i') . ' WIB']);

            // HTTP Cookie (disimpan di browser, terenkripsi, HttpOnly): ingat email selama 7 hari.
            Cookie::queue('sipus_last_email', $request->input('email'), 60 * 24 * 7);

            return redirect()->intended(route('buku.index'))
                ->with('success', 'Selamat datang, ' . Auth::user()->name . '. Sesi Anda telah aktif.');
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->with('error', 'Email atau password salah.');
    }

    /**
     * POST /logout - hapus sesi dengan aman.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();       // hapus seluruh data sesi di server
        $request->session()->regenerateToken();  // buat token CSRF baru

        return redirect()->route('login')->with('success', 'Anda telah keluar. Sesi server telah dihapus dengan aman.');
    }
}