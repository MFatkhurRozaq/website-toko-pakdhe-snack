<?php
session_start();

// Perbaiki path - sesuaikan dengan struktur folder Anda
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Regenerasi session ID untuk keamanan
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Cek sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Limit percobaan login (brute force protection)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

$error = '';
$lockout_time = 0;

// Cek lockout (5 percobaan dalam 15 menit)
if ($_SESSION['login_attempts'] >= 5) {
    $time_diff = time() - $_SESSION['last_attempt_time'];
    if ($time_diff < 900) { // 15 menit
        $lockout_time = 900 - $time_diff;
        $error = "Terlalu banyak percobaan login. Coba lagi dalam " . ceil($lockout_time / 60) . " menit.";
    } else {
        // Reset percobaan
        $_SESSION['login_attempts'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$lockout_time) {
    // CSRF Protection sederhana
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token tidak valid. Silakan refresh halaman.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Username dan password harus diisi!";
        } else {
            // Cek login dengan prepared statement
            $result = login($pdo, $username, $password);
            
            if ($result === true) {
                // Reset percobaan login setelah berhasil
                $_SESSION['login_attempts'] = 0;
                
                // Regenerasi session ID
                session_regenerate_id(true);
                
                header("Location: dashboard.php");
                exit();
            } else {
                // Increment percobaan gagal
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                
                // Log percobaan gagal (opsional)
                error_log("Login failed for username: $username from IP: " . $_SERVER['REMOTE_ADDR']);
                
                $error = $result === false ? "Username atau password salah!" : $result;
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pakdhe Snack</title>
    <link rel="stylesheet" href="../assets/css/style1.css">
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
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-icon {
            text-align: center;
            font-size: 64px;
            margin-bottom: 20px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .login-card > p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-login-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 30px;
        }

        /* ========== TOMBOL LOGIN DENGAN LOADING STATE ========== */
        .btn-login {
            width: auto !important;
            min-width: 200px;
            padding: 14px 32px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        /* Style untuk tombol saat loading/disabled */
        .btn-login:disabled {
            opacity: 0.8;
            cursor: not-allowed;
            transform: none;
        }

        /* Spinner animasi untuk tombol */
        .btn-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Ripple effect saat klik */
        .btn-login .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.4);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            width: 100%;
            padding-right: 50px !important;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #94a3b8;
            transition: all 0.3s ease;
            background: transparent;
            border: none;
            padding: 0;
            width: auto;
            z-index: 2;
        }
        
        .toggle-password:hover {
            color: #667eea;
        }
        
        .toggle-password:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .input-group {
            position: relative;
        }

        .lockout-timer {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #dc2626;
        }

        /* Disabled input style */
        input:disabled {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 24px;
            }
            
            .btn-login {
                min-width: 150px;
                padding: 12px 24px !important;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-icon">🏪</div>
            <h2>Toko Pakdhe Snack</h2>
            <p>Silakan login untuk mengakses sistem</p>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if($lockout_time): ?>
                <div class="lockout-timer">
                    ⏱️ Akun terkunci. Coba lagi dalam <?= ceil($lockout_time / 60) ?> menit
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username" 
                           placeholder="Masukkan username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required autofocus
                           <?= $lockout_time ? 'disabled' : '' ?>>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" 
                               placeholder="Masukkan password" 
                               required
                               <?= $lockout_time ? 'disabled' : '' ?>>
                        <button type="button" class="toggle-password" onclick="togglePassword()" 
                                <?= $lockout_time ? 'disabled' : '' ?>>
                            👁️
                        </button>
                    </div>
                </div>
                
                <div class="btn-login-wrapper">
                    <button type="submit" class="btn btn-primary btn-login" id="loginBtn" <?= $lockout_time ? 'disabled' : '' ?>>
                        🔓 Login Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Fungsi toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput && !passwordInput.disabled) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.innerHTML = '🙈';
                    toggleBtn.style.color = '#667eea';
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.innerHTML = '👁️';
                    toggleBtn.style.color = '#94a3b8';
                }
            }
        }
        
        // Ripple effect function
        function createRipple(event, element) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            const rect = element.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            // Remove existing ripples
            const oldRipples = element.querySelectorAll('.ripple');
            oldRipples.forEach(oldRipple => oldRipple.remove());
            
            element.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
        
        // Handle form submission dengan loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Cek apakah form sudah dalam proses submit
            if (this.submitting) {
                e.preventDefault();
                return false;
            }
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const originalText = loginBtn.innerHTML;
            
            // Validasi client-side
            if (!username) {
                e.preventDefault();
                showTemporaryError('Username tidak boleh kosong!');
                return false;
            }
            
            if (!password) {
                e.preventDefault();
                showTemporaryError('Password tidak boleh kosong!');
                return false;
            }
            
            // Cek apakah tombol sedang dalam proses loading
            if (loginBtn.disabled) {
                e.preventDefault();
                return false;
            }
            
            // Set flag bahwa form sedang diproses
            this.submitting = true;
            
            // Ubah tampilan tombol menjadi loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="btn-spinner"></span> Memproses...';
            
            // Tambahkan class disabled style
            loginBtn.style.opacity = '0.8';
            
            // Form akan tetap tersubmit
            return true;
        });
        
        // Fungsi untuk menampilkan error temporary tanpa reload
        function showTemporaryError(message) {
            // Hapus alert yang sudah ada
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // Buat alert baru
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = message;
            
            // Insert di atas form
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Auto remove setelah 3 detik
            setTimeout(function() {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transition = 'opacity 0.5s';
                    setTimeout(function() {
                        if (alertDiv && alertDiv.parentNode) {
                            alertDiv.remove();
                        }
                    }, 500);
                }
            }, 3000);
        }
        
        // Ripple effect pada tombol login (opsional)
        const loginBtn = document.getElementById('loginBtn');
        if (loginBtn) {
            loginBtn.addEventListener('click', function(e) {
                if (!this.disabled) {
                    createRipple(e, this);
                }
            });
        }
        
        // Enter key submit (sudah default, tapi tambahkan loading state)
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = document.getElementById('loginForm');
                const loginBtn = document.getElementById('loginBtn');
                
                // Cek apakah tombol disabled
                if (!loginBtn.disabled) {
                    // Trigger submit dengan loading state
                    if (!form.submitting) {
                        form.submitting = true;
                        loginBtn.disabled = true;
                        loginBtn.innerHTML = '<span class="btn-spinner"></span> Memproses...';
                        loginBtn.style.opacity = '0.8';
                        form.submit();
                    }
                }
            }
        });
        
        // Auto clear error after 5 seconds (jika ada error dari server)
        <?php if($error): ?>
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }
        }, 5000);
        <?php endif; ?>
        
        // Jika halaman dimuat ulang karena error, reset tombol
        window.addEventListener('load', function() {
            const loginBtn = document.getElementById('loginBtn');
            const form = document.getElementById('loginForm');
            
            if (loginBtn && form) {
                loginBtn.disabled = false;
                loginBtn.innerHTML = '🔓 Login Sekarang';
                loginBtn.style.opacity = '1';
                form.submitting = false;
            }
        });
    </script>
</body>
</html>