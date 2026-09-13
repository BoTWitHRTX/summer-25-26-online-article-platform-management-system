<?php
// ================================================================
// MODEL: activity_log - a simple audit trail the admin can read/search.
// ================================================================

function log_activity($conn, $action) {
    $user = current_user();
    $stmt = mysqli_prepare($conn,
        "INSERT INTO activity_log (user_id, username, role, action, ip) VALUES (?, ?, ?, ?, ?)");
    $userId = $user['id'] ?? null;
    $username = $user['username'] ?? 'guest';
    $role = $user['role'] ?? '-';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $username, $role, $action, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function get_logs($conn, $limit = 20) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function search_logs($conn, $term, $limit = 50) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM activity_log WHERE username LIKE ? OR action LIKE ? ORDER BY created_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $like, $like, $limit);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}
