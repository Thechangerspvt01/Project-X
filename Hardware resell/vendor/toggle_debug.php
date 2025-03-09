<?php
require_once '../includes/config.php';

if (!isVendor()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$_SESSION['debug'] = !isset($_SESSION['debug']) || !$_SESSION['debug'];
header('Location: ' . BASE_URL . '/vendor/index.php');
exit;
?>
