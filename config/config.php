<?php

/* ---------- 1. Database settings ---------- */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'booknest_db');


/* ---------- 2. App settings ---------- */

define('APP_NAME', 'BookNest');

define('SESSION_TIMEOUT', 1800); // 30 minutes


/* ---------- 3. Start session ---------- */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/* ---------- 4. Connect to MySQL ---------- */

$conn = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if (!$conn) {
    die(
        'Database connection failed. '. 'Did you import database.sql? '. 'Details: ' mysqli_connect_err());
}


/* ---------- 5. Set database character set ---------- */

mysqli_set_charset($conn, 'utf8mb4');


/* ---------- 6. Create default admin ---------- */

// The admin account is created automatically the first time the application runs.

$check = mysqli_query(
    $conn,
    "SELECT id FROM users WHERE role = 'admin' LIMIT 1"
);

if ($check && mysqli_num_rows($check) === 0) {

    $adminPassword = password_hash(
        'admin123',
        PASSWORD_DEFAULT
    );

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users
        (name, email, username, password, role)
        VALUES (?, ?, ?, ?, 'admin')"
    );

    $adminName = 'Administrator';
    $adminEmail = 'admin@gmail.test';
    $adminUsername = 'admin';

    mysqli_stmt_bind_param($stmt,'ssss', $adminName, $adminEmail, $adminUsername, $adminPassword);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}