<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (getenv('PGHOST')) {
    require_once 'config/init_db.php';
    initializeDatabase();
}

if (isLoggedIn()) {
    header('Location: home.php');
} else {
    header('Location: login.php');
}
exit();
?>
