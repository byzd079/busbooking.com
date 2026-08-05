@extends('layout')

@section('title', 'Forgot Password - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8 col-sm-11">
        <div class="glass-card">
            <!-- Back to sign in -->
            <div class="mb-3">
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Sign In
                </a>
            </div>

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                    style="width:64px; height:64px; background: var(--primary-light);">
                    <i class="fas fa-key fs-3" style="color: var(--primary);"></i>
                </div>
                <h1 class="h3 fw-bold text-dark mb-2">Forgot Password?</h1>
                <p class="text-muted mb-0">
                    Enter the email address on your account and we'll send you a link to reset your password.
                </p>
            </div>

            <!-- Status message -->
            @if (session('status'))
                <div class="alert alert-success d-flex align-items-start" role="alert">
                    <i class="fas fa-check-circle fs-5 me-2 mt-1"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            <!-- Validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-start" role="alert">
                    <i class="fas fa-exclamation-circle fs-5 me-2 mt-1"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('forgot_passwordPost') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="email" name="email" value="{{ old('email') }}"
                            placeholder="your@email.com" autocomplete="email"
                            aria-describedby="emailHelp" required autofocus>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @else
                        <div id="emailHelp" class="form-text">
                            Use the email you registered with.
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold text-uppercase">
                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <span class="text-muted small">Remembered your password?</span>
                <a href="{{ route('login') }}" class="small text-primary text-decoration-none fw-semibold ms-1">
                    Sign In
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
