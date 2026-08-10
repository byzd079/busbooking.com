@extends('layout')

@section('title', 'Set Your Password - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8 col-sm-11">
        <div class="glass-card">
            <div class="text-center mb-4">
                <div class="display-5 text-primary mb-2">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="h4 fw-bold text-dark mb-1">Claim Your Account</h1>
                <p class="text-muted small mb-0">
                    Set a password so you can log back in any time with your
                    <strong>mobile number or email</strong> and see your bookings.
                </p>
            </div>

            <form action="{{ route('claim_account.post') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="claim_password" class="form-label">New Password (Min 8 chars)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="claim_password" name="password"
                            placeholder="Minimum 8 characters" minlength="8" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('claim_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="claim_password_confirmation" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="claim_password_confirmation"
                            name="password_confirmation" placeholder="Re-enter your password" minlength="8" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold text-uppercase">
                    <i class="fas fa-check me-2"></i> Save Password
                </button>
            </form>
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
</script>
@endsection
