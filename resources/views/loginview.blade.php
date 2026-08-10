@extends('layout')

@section('title', 'Sign In or Register - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8 col-sm-11">
        <!-- Auth Card Wrapper -->
        <div class="glass-card">
            <!-- Nav Tabs Toggle (48px Touch Targets) -->
            <ul class="nav nav-pills nav-justified mb-4" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-bold" id="login-tab" data-bs-toggle="pill"
                        data-bs-target="#login-panel" type="button" role="tab" aria-selected="true">
                        <i class="fas fa-sign-in-alt me-1"></i> Sign In
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold" id="register-tab" data-bs-toggle="pill"
                        data-bs-target="#register-panel" type="button" role="tab" aria-selected="false">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="authTabsContent">
                <!-- Sign In Form Panel -->
                <div class="tab-pane fade show active" id="login-panel" role="tabpanel" aria-labelledby="login-tab">
                    <form action="{{ url('log_in') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="login_identifier" class="form-label">Email or Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control" id="login_identifier" name="login"
                                    placeholder="your@email.com or 01712345678" value="{{ old('login', $_COOKIE['email'] ?? old('email')) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="login_password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="login_password" name="password"
                                    placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('login_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1"
                                    {{ isset($_COOKIE['email']) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                            <a href="{{ route('forgot_password.view') }}" class="small text-primary text-decoration-none">
                                Forgot password?
                            </a>
                        </div>

                        <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold text-uppercase">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign In
                        </button>
                    </form>
                </div>

                <!-- Registration Form Panel -->
                <div class="tab-pane fade" id="register-panel" role="tabpanel" aria-labelledby="register-tab">
                    <form action="{{ url('register') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="reg_name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control" id="reg_name" name="name"
                                    placeholder="e.g. Tanvir Ahmed" value="{{ old('name') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reg_email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control" id="reg_email" name="email"
                                    placeholder="your@email.com" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reg_phone" class="form-label">Mobile Number (11 digits)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                                <input type="tel" class="form-control" id="reg_phone" name="mobile_no"
                                    placeholder="01712345678" pattern="[0-9]{11}" value="{{ old('mobile_no') }}" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="reg_password" class="form-label">Create Password (Min 8 chars)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="reg_password" name="password"
                                    placeholder="Minimum 8 characters" minlength="8" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('reg_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold text-uppercase">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </button>
                    </form>
                </div>
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
</script>
@endsection