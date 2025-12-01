<?php
$pageTitle = 'Verifikasi OTP - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/otp_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pending_otp_user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['pending_otp_email']) || empty($_SESSION['pending_otp_email'])) {
    header('Location: set_email.php');
    exit();
}

$userId = $_SESSION['pending_otp_user_id'];
$userEmail = $_SESSION['pending_otp_email'];
$username = $_SESSION['pending_otp_username'] ?? '';
$fullName = $_SESSION['pending_otp_fullname'] ?? '';

$error = '';
$success = $_SESSION['otp_success'] ?? '';
$warning = $_SESSION['otp_warning'] ?? '';
$demoOtp = $_SESSION['demo_otp'] ?? '';
$waitSeconds = $_SESSION['otp_wait_seconds'] ?? 0;

unset($_SESSION['otp_success'], $_SESSION['otp_warning'], $_SESSION['otp_wait_seconds']);

$otpHandler = new OTPHandler();
$expiresAt = $otpHandler->getActiveOTPExpiry($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputOtp = '';
    for ($i = 1; $i <= 6; $i++) {
        $inputOtp .= $_POST["otp$i"] ?? '';
    }
    
    if (strlen($inputOtp) !== 6 || !ctype_digit($inputOtp)) {
        $error = 'Masukkan 6 digit kode OTP!';
    } else {
        $result = $otpHandler->verifyOTP($userId, $inputOtp);
        
        if ($result['success']) {
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['otp_verified'] = true;
            $_SESSION['login_time'] = time();
            
            unset($_SESSION['pending_otp_user_id'], $_SESSION['pending_otp_email'], $_SESSION['pending_otp_username'], $_SESSION['pending_otp_fullname'], $_SESSION['demo_otp']);
            
            header('Location: home.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    
    $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 0));
    return $maskedName . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .otp-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 480px;
        }
        .otp-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .otp-icon i {
            font-size: 36px;
            color: white;
        }
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 30px 0;
        }
        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #dee2e6;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .otp-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }
        .otp-input.filled {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .timer {
            font-size: 14px;
            color: #6c757d;
        }
        .timer.warning {
            color: #dc3545;
        }
        .demo-otp {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .demo-otp code {
            font-size: 24px;
            font-weight: bold;
            color: #856404;
            letter-spacing: 8px;
        }
    </style>
</head>
<body>
    <div class="otp-card">
        <div class="text-center">
            <div class="otp-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h4 class="mb-2">Verifikasi 2 Langkah</h4>
            <p class="text-muted mb-0">
                Masukkan kode OTP yang dikirim ke
            </p>
            <p class="fw-bold text-primary mb-0"><?php echo maskEmail($userEmail); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($warning || $demoOtp): ?>
            <div class="demo-otp text-center mt-3">
                <small class="d-block mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Mode Demo - SMTP belum dikonfigurasi</small>
                <div>Kode OTP: <code><?php echo $demoOtp; ?></code></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="otpForm">
            <div class="otp-inputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" class="otp-input" name="otp<?php echo $i; ?>" 
                           maxlength="1" pattern="[0-9]" inputmode="numeric"
                           autocomplete="off" required>
                <?php endfor; ?>
            </div>


            <button type="submit" class="btn btn-primary w-100 btn-lg" id="verifyBtn">
                <i class="bi bi-check-circle me-2"></i>Verifikasi
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted mb-2">Tidak menerima kode?</p>
            <a href="send_otp.php?force=1" class="btn btn-outline-secondary btn-sm" id="resendBtn">
                <i class="bi bi-arrow-repeat me-1"></i>Kirim Ulang OTP
            </a>
        </div>

        <div class="text-center mt-3">
            <a href="change_email.php" class="text-decoration-none text-muted small">
                <i class="bi bi-pencil me-1"></i>Ganti email verifikasi
            </a>
        </div>

        <div class="text-center mt-2">
            <a href="logout.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Batalkan dan kembali ke login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.otp-input');
            
            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    if (this.value.length === 1) {
                        this.classList.add('filled');
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    } else {
                        this.classList.remove('filled');
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        inputs[index - 1].focus();
                        inputs[index - 1].value = '';
                        inputs[index - 1].classList.remove('filled');
                    }
                });
                
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                    
                    pastedData.split('').forEach((digit, i) => {
                        if (inputs[i]) {
                            inputs[i].value = digit;
                            inputs[i].classList.add('filled');
                        }
                    });
                    
                    if (pastedData.length > 0) {
                        const lastIndex = Math.min(pastedData.length - 1, 5);
                        inputs[lastIndex].focus();
                    }
                });
            });
            
            inputs[0].focus();
            
            const timerEl = document.getElementById('timer');
            if (timerEl) {
                const expiresAt = new Date(timerEl.dataset.expires).getTime();
                
                function updateTimer() {
                    const now = new Date().getTime();
                    const diff = expiresAt - now;
                    
                    if (diff <= 0) {
                        timerEl.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>OTP sudah kedaluwarsa';
                        timerEl.classList.add('warning');
                        return;
                    }
                    
                    const minutes = Math.floor(diff / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    
                    timerEl.innerHTML = `<i class="bi bi-clock me-1"></i>Berlaku ${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (minutes < 5) {
                        timerEl.classList.add('warning');
                    }
                    
                    setTimeout(updateTimer, 1000);
                }
                
                updateTimer();
            }
        });
    </script>
</body>
</html>
