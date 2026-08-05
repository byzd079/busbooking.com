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
            return redirect()->route('home');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput()
                ->with('error', 'Validation failed. Please correct the errors and try again.');
        }
    }

    public function log_in(Request $request)
    {
        $remember = $request->input('remember', false);
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $remember)) {
            // Expire any legacy plaintext password cookies from older code
            setcookie('password', '', time() - 3600, '/');

            Session::flash('success', 'Logged in successfully!');
            // Removed: Insecure plaintext password storage in cookies
            // Laravel's Auth::attempt() with $remember already handles secure "Remember Me" via encrypted session cookies

            return redirect()->intended(route('home'));
        }

        Session::flash('error', 'Invalid email or password.');
        return redirect()->back()->withInput();
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
