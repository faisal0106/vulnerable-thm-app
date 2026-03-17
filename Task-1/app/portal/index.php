<?php
require_once 'config.php';
if (is_logged_in()) {
    header('Location: ' . ($_SESSION['portal_is_admin'] ? 'admin.php' : 'dashboard.php'));
} else {
    header('Location: login.php');
}
exit;
