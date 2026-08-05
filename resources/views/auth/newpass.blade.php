@extends('layout')

@section('title', 'Reset Password - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8 col-sm-11">
        <div class="glass-card">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                    style="width:64px; height:64px; background: var(--primary-light);">
                    <i class="fas fa-lock-open fs-3" style="color: var(--primary);"></i>
                </div>
                <h1 class="h3 fw-bold text-dark mb-2">Set a New Password</h1>
                <p class="text-muted mb-0">Choose a strong password you haven't used before.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success d-flex align-items-start" role="alert">
                    <i class="fas fa-check-circle fs-5 me-2 mt-1"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

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

            <form method="POST" action="{{ route('resetPasswordPost') }}" id="resetForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="email" name="email" value="{{ old('email', $email ?? '') }}"
                            placeholder="your@email.com" autocomplete="email" required
                            {{ isset($email) && $email ? 'readonly' : 'autofocus' }}>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" minlength="8"
                            placeholder="Minimum 8 characters" autocomplete="new-password"
                            aria-describedby="passwordHelp" required
                            {{ isset($email) && $email ? 'autofocus' : '' }}>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="togglePass('password', this)" aria-label="Show or hide password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @else
                        <div id="passwordHelp" class="form-text">At least 8 characters.</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-check-circle text-muted"></i></span>
                        <input type="password" class="form-control"
                            id="password_confirmation" name="password_confirmation" minlength="8"
                            placeholder="Re-type your new password" autocomplete="new-password"
                            aria-describedby="matchHelp" required>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="togglePass('password_confirmation', this)" aria-label="Show or hide password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="matchHelp" class="form-text" aria-live="polite"></div>
                </div>

                <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold text-uppercase">
                    <i class="fas fa-shield-alt me-2"></i> Reset Password
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <a href="{{ route('login') }}" class="small text-primary text-decoration-none fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Back to Sign In
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    // Inline match feedback so users learn about a mismatch before submitting.
    (function () {
        const pass = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        const help = document.getElementById('matchHelp');

        function check() {
            if (!confirm.value) {
                help.textContent = '';
                help.className = 'form-text';
                return;
            }
            if (pass.value === confirm.value) {
                help.textContent = 'Passwords match.';
                help.className = 'form-text text-success';
            } else {
                help.textContent = 'Passwords do not match.';
                help.className = 'form-text text-danger';
            }
        }

        pass.addEventListener('input', check);
        confirm.addEventListener('input', check);
    })();
</script>
@endsection
