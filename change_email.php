<?php
require_once 'includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pending_otp_user_id'])) {
    header('Location: login.php');
    exit();
}

unset($_SESSION['pending_otp_email']);

header('Location: set_email.php');
exit();
