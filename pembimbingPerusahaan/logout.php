<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    require_once 'log_helper.php';
    logAktivitas(getDB(), $_SESSION['user_id'], 'Logout dari panel Wakasek', 'wakasek');
}

session_destroy();
header('Location: ../login.php');
exit;
