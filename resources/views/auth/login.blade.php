<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flettons Chat Panel - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            position: relative;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            font-weight: bold;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .app-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .app-subtitle {
            color: #666;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .password-toggle {
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 30px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 20px;
            color: #999;
            font-size: 14px;
        }

        .signup-link {
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .app-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo">F</div>
            <h1 class="app-title">Flettons Chat</h1>
            <p class="app-subtitle">Connect, Chat, Collaborate</p>
        </div>

        <form id="loginForm" action="{{ route('admin.login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <span class="input-icon">📧</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <span class="input-icon password-toggle" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            {{-- <div class="forgot-password">
                <a href="#" onclick="showAlert('Password reset functionality would be implemented here.')">Forgot Password?</a>
            </div> --}}

            <button type="submit" class="login-btn">Sign In to Chat Panel</button>
        </form>

    </div>

    <script>
        let passwordVisible = false;

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');

            if (passwordVisible) {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
                passwordVisible = false;
            } else {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
                passwordVisible = true;
            }
        }

        function showAlert(message) {
            alert(message);
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // keep this so we can validate first

            const form = this;
            const email = form.querySelector('#email').value.trim();
            const password = form.querySelector('#password').value.trim();
            const btn = form.querySelector('.login-btn');

            if (!email || !password) {
                alert('Please enter both email and password.');
                return;
            }

            // Optional: UI feedback + prevent double clicks
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Signing In...';
            btn.style.opacity = '0.7';

            // Submit the form normally (will navigate to action URL)
            form.submit();
        });

        // Add some interactive effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>

</html>
