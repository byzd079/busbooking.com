<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - JatraPoth</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --surface: #ffffff;
            --surface-raised: #f8fafc;
            --on-surface: #1e293b;
            --on-surface-muted: #64748b;
            --border: #e2e8f0;
            --error: #dc2626;
            --success: #16a34a;

            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;
            --radius-md: 8px;
            --radius-lg: 16px;
            --shadow-lg: 0 10px 15px rgba(0,0,0,.15);
            --touch-target-min: 48px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }

        .login-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }

        .login-header {
            background: var(--primary);
            color: white;
            padding: var(--space-xl);
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: var(--space-md) 0 8px 0;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
            margin: 0;
        }

        .login-body {
            padding: var(--space-xl);
        }

        .form-group {
            margin-bottom: var(--space-lg);
        }

        label {
            display: block;
            font-weight: 500;
            color: var(--on-surface);
            margin-bottom: 8px;
            font-size: 0.875rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.2s;
            min-height: var(--touch-target-min);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }

        .form-control.is-invalid {
            border-color: var(--error);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--on-surface-muted);
            cursor: pointer;
            padding: 8px;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .password-toggle:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        .password-wrapper .form-control {
            padding-right: 48px;
        }

        .invalid-feedback {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 6px;
            display: block;
        }

        .helper-text {
            color: var(--on-surface-muted);
            font-size: 0.8125rem;
            margin-top: 4px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: var(--space-lg);
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 4px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-input:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }

        .form-check-label {
            cursor: pointer;
            font-weight: 400;
            margin: 0;
        }

        .btn-login {
            width: 100%;
            padding: 14px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            min-height: var(--touch-target-min);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 3px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: var(--space-lg);
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--error);
        }

        .alert-success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: var(--space-md);
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .login-body {
                padding: var(--space-lg);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-bus fa-2x"></i>
            <h1>Admin Login</h1>
            <p>Welcome back! Please sign in to your account.</p>
        </div>

        <div class="login-body">
            @if(session('success'))
                <div class="alert alert-success" role="alert" aria-live="polite">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" role="alert" aria-live="assertive">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('admin_login.post') }}" method="POST" novalidate>
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        aria-describedby="emailError"
                    >
                    @error('email')
                        <span class="invalid-feedback" id="emailError" role="alert">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            aria-describedby="passwordError"
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Toggle password visibility"
                        >
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback" id="passwordError" role="alert">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="remember"
                        name="remember"
                        value="1"
                    >
                    <label class="form-check-label" for="remember">
                        Remember me on this device
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Sign In</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
