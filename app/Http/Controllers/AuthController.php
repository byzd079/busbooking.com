<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $remember = $request->input('remember', false);
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'mobile_no' => 'required|unique:users|digits:11',
            ]);

            $data['name'] = $validatedData['name'];
            $data['mobile_no'] = $validatedData['mobile_no'];
            $data['email'] = $validatedData['email'];
            $data['password'] = bcrypt($validatedData['password']);
            $user = User::create($data);

            // Removed: Insecure plaintext password storage in cookies
            // Laravel's Auth::login() with $remember already handles secure "Remember Me" via encrypted session cookies

            Auth::login($user);
            Session::flash('success', 'Registration successful! Welcome to JatraPoth.');

            // A guest bounced off the seat-confirm step may choose to register
            // rather than sign in, so honour the same intended URL log_in() does
            // — otherwise registering silently discards their seat selection.
            return redirect()->intended(route('home'));
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput()
                ->with('error', 'Validation failed. Please correct the errors and try again.');
        }
    }

    public function log_in(Request $request)
    {
        $remember = $request->input('remember', false);

        // B1: a single identifier that may be an email OR a mobile number (no
        // OTP, no SMS). 'login' is the new field name; keep accepting legacy
        // 'email' so older cached forms / bookmarks still work.
        $request->validate([
            'login' => 'required_without:email|string',
            'email' => 'required_without:login|string',
            'password' => 'required|string',
        ]);

        $identifier = trim((string) ($request->input('login') ?? $request->input('email')));
        $password = $request->input('password');
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        // An '@' means authenticate by email; otherwise normalise to digits and
        // authenticate against the 11-digit mobile_no we store.
        if ($isEmail) {
            $credentials = ['email' => $identifier, 'password' => $password];
        } else {
            $credentials = ['mobile_no' => preg_replace('/\D+/', '', $identifier), 'password' => $password];
        }

        if (Auth::attempt($credentials, $remember)) {
            // Expire any legacy plaintext password cookies from older code
            setcookie('password', '', time() - 3600, '/');

            $request->session()->regenerate();

            Session::flash('success', 'Logged in successfully!');
            // Removed: Insecure plaintext password storage in cookies
            // Laravel's Auth::attempt() with $remember already handles secure "Remember Me" via encrypted session cookies

            return redirect()->intended(route('home'));
        }

        // An unclaimed (NULL-password) auto-created account can never satisfy
        // Auth::attempt — Hash::check rejects an empty hash — so a matching
        // account with no password lands here. Point them at the claim flow.
        $match = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('mobile_no', preg_replace('/\D+/', '', $identifier))->first();

        if ($match && $match->isUnclaimed()) {
            Session::flash('error', 'This account was created automatically at checkout and has no password yet. Use "Forgot password?" to set one, then sign in with your mobile or email.');
            return redirect()->back()->withInput();
        }

        Session::flash('error', 'Invalid login or password.');
        return redirect()->back()->withInput();
    }

    /**
     * A3 tail: let a just-auto-provisioned (unclaimed) user set a password so
     * they can log back in later with their mobile or email.
     */
    public function claim_account()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to set your password.');
        }
        return view('claim_account');
    }

    public function claim_account_post(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to set your password.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->save();

        return redirect()->route('home')->with('success', 'Password set! You can now log in any time with your mobile number or email.');
    }

    public function log_out()
    {
        Session::flush();
        Auth::logout();
        return redirect('/')->with('success', 'Logged out successfully.');
    }

    public function edit_profile()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view profile.');
        }
        return view('edit_profile');
    }

    public function update_profile(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to update profile.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        $user = Auth::user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('edit_profile')->with('success', 'Profile updated successfully.');
    }

    public function change_password()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to change password.');
        }
        return view('change_password');
    }

    public function update_password(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to change password.');
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:current_password|confirmed',
        ], [
            'new_password.different' => 'The new password must be different from the current password.',
        ]);

        $user = Auth::user();

        // Verify current password with Hash::check
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'The current password is incorrect.');
        }

        // Update password using bcrypt
        $user->password = bcrypt($request->new_password);
        $user->save();

        return redirect()->route('change_password')->with('success', 'Password changed successfully.');
    }

    public function view_profile()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view profile.');
        }
        return view('view_profile');
    }

    public function purchase_history()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to view purchase history.');
        }
        $email = $user->email;
        $order = Order::where('email', $email)
                     ->orderBy('created_at', 'desc')
                     ->paginate(5);
        return view('purchase_history', compact('order'));
    }
}
