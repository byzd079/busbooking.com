<?php

namespace App\Http\Controllers;

use App\Models\forgot_password;
use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Session;

class ForgotPasswordManager extends Controller
{
    //
    public function forgot_password()
    {
        return view("auth.forgot_password");

    }
    public function forgot_passwordPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'We could not find an account with that email address.',
        ]);

        // Drop any earlier tokens for this email so only the newest link works.
        forgot_password::where('email', $request->email)->delete();

        $token = Str::random(30);
        $forgot = new forgot_password();
        $forgot->email = $request->email;
        $forgot->token = $token;
        $forgot->save();

        try {
            Mail::send('auth.email', ['token' => $token], function ($message) use ($request) {
                $message->to($request->email)->subject('Reset Password');
            });
        } catch (\Throwable $e) {
            // Do not leave a live reset token behind for a link the user never received.
            $forgot->delete();

            Log::error('Password reset email failed to send', [
                'email' => $request->email,
                'mailer' => config('mail.default'),
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('forgot_password.view')
                ->withErrors(['email' => 'We could not send the reset email right now. Please try again in a few minutes, or contact support if it keeps failing.'])
                ->withInput();
        }

        return redirect()->route('forgot_password.view')
            ->with('status', 'We sent a password reset link to ' . $request->email . '. Check your inbox, including the spam folder.');

    }
    public function resetPassword($token)
    {
        $forgot = forgot_password::where('token', $token)->first();

        if (!$forgot) {
            return redirect()->route('forgot_password.view')
                ->withErrors(['email' => 'That reset link is invalid or has already been used. Request a new one below.']);
        }

        // Links older than 60 minutes are treated as expired.
        if ($forgot->created_at && $forgot->created_at->diffInMinutes(now()) > 60) {
            $forgot->delete();
            return redirect()->route('forgot_password.view')
                ->withErrors(['email' => 'That reset link has expired. Request a new one below.']);
        }

        return view('auth.newpass', ['token' => $token, 'email' => $forgot->email]);
    }
    public function resetPasswordPost(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $forgot = forgot_password::where('email', $validatedData['email'])
            ->where('token', $validatedData['token']);

        if ($forgot->exists()) {
            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                return redirect()->back()
                    ->withErrors(['email' => 'We could not find an account with that email address.']);
            }

            $user->password = bcrypt($validatedData['password']);
            $user->save();
            $forgot->delete();

            return redirect()->route('login')
                ->with('success', 'Your password has been reset. Sign in with your new password.');
        }

        return redirect()->back()
            ->withErrors(['email' => 'That reset link is invalid or has already been used. Request a new one.']);


    }
}
