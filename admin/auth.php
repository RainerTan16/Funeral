<?php
session_start();
if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
