<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper;
use App\Helpers\Whatsapp;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login()
    {
        return view('pages.auth.login');
    }

    public function auth(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);
            $message = trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]);

            flash($message, 'error');

            return redirect()->back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => $message]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();

            if (Auth::check()) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'activity' => Auth::user()->name.' Login pada '.Helper::formatDateTime(now()),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        flash('Email atau password yang Anda masukkan salah.', 'error');

        return redirect()->back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Email atau password yang Anda masukkan salah.']);
    }

    public function showForgotPassword()
    {
        $adminWhatsapp = config('pesantren.admin_whatsapp', '081234567890');
        $namaPesantren = config('pesantren.nama_pesantren', 'Pondok Pesantren Fatimah Az Zahra');
        $defaultWaUrl = Whatsapp::adminResetUrl();

        return view('pages.auth.forgot-password', compact('adminWhatsapp', 'namaPesantren', 'defaultWaUrl'));
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            flash(trans($status), 'success');

            return back()->with('status', trans($status));
        }

        flash(trans($status), 'error');

        return back()->withErrors(['email' => trans($status)]);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => Auth::user()->name.' Logout pada '.Helper::formatDateTime(now()),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to('login');
    }
}
