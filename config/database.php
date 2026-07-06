<?php
// ============================================
// SAFETY ACADEMY - Database Configuration
// ============================================

// People Academy DB (external - for users & points)
define('PA_DB_HOST', 'localhost');
define('PA_DB_USER', 'u806400645_ppacademy');
define('PA_DB_PASS', 'Juandavid15$');
define('PA_DB_NAME', 'u806400645_ppacademy');

// Safety Academy DB (local - for content & quiz)
define('SA_DB_HOST', 'localhost');
define('SA_DB_USER', 'u806400645_safety'); // Update if different
define('SA_DB_PASS', 'Safety2107');          // Update if different
define('SA_DB_NAME', 'u806400645_safety');     // Update with your SA DB name

try {
    // Connect to People Academy (users/points)
    $pa_conn = mysqli_connect(PA_DB_HOST, PA_DB_USER, PA_DB_PASS, PA_DB_NAME);
    if (!$pa_conn) throw new Exception("PA DB Error: " . mysqli_connect_error());
    mysqli_set_charset($pa_conn, "utf8mb4");

    // Connect to Safety Academy (content/quiz)
    $sa_conn = mysqli_connect(SA_DB_HOST, SA_DB_USER, SA_DB_PASS, SA_DB_NAME);
    if (!$sa_conn) throw new Exception("SA DB Error: " . mysqli_connect_error());
    mysqli_set_charset($sa_conn, "utf8mb4");

} catch (Exception $e) {
    error_log($e->getMessage());
    die(json_encode(['error' => 'Database connection failed']));
}

function sanitize($conn, $data) {
    if (is_array($data)) return array_map(function($i) use ($conn) { return sanitize($conn, $i); }, $data);
    return mysqli_real_escape_string($conn, trim($data));
}

function query($conn, $sql, $types = '', ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { error_log("Query error: " . mysqli_error($conn)); return false; }
    if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    return $stmt;
}
?>
