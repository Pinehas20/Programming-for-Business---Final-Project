<?php
require_once 'config/init_db.php';
require_once 'includes/session.php';

initializeDatabase();

if (isLoggedIn()) {
    header('Location: home.php');
} else {
    header('Location: login.php');
}
exit();
?>
