<?php
// Database configuration
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_USER', 'if0_41314439');
define('DB_PASS', 'sqxXHhmjqcLO0Rb');
define('DB_NAME', 'if0_41314439_y2j_funeral');
define('DB_PORT', 3306);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');
?>
