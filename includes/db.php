<?php
// Database configuration
define('DB_HOST', 'trolley.proxy.rlwy.net');
define('DB_USER', 'root');
define('DB_PASS', 'XfxjvZoSeKkScRHOJbHgcyFylZibsERD');
define('DB_NAME', 'railway');
define('DB_PORT', 53246);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');
?>
