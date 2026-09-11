<?php
// ================================================================
// MODEL: users (admin / author / reader)
// Every function here either returns rows or true/false - no HTML,
// no direct $_POST reads. That stays in the controllers.
// ================================================================

function find_user_by_username($conn, $username) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function get_user($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function username_exists($conn, $username, $excludeId = 0) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'si', $username, $excludeId);
    mysqli_stmt_execute($stmt);
    $found = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    return $found;
}

// Signup: role is 'author' or 'reader' only (admin can never sign up).
function create_user($conn, $name, $email, $contact, $username, $password, $role, $bio, $address, $question, $answer) {
    $hash    = password_hash($password, PASSWORD_DEFAULT);
    $ansHash = password_hash(strtolower(trim($answer)), PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (name, email, contact, username, password, role, bio, address, order_limit, security_question, security_answer)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $limit = DEFAULT_ORDER_LIMIT;
    mysqli_stmt_bind_param($stmt, 'sssssssdsss',
        $name, $email, $contact, $username, $hash, $role, $bio, $address, $limit, $question, $ansHash);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function update_password($conn, $id, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function verify_security_answer($conn, $userId, $answer) {
    $user = get_user($conn, $userId);
    if (!$user) return false;
    return password_verify(strtolower(trim($answer)), $user['security_answer']);
}

/* ---------------- Admin: manage author + reader accounts ---------------- */

function get_users($conn, $roleFilter = '') {
    if ($roleFilter !== '') {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, 's', $roleFilter);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
    }
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function search_users($conn, $term, $roleFilter = '') {
    $like = '%' . $term . '%';
    if ($roleFilter !== '') {
        $stmt = mysqli_prepare($conn,
            "SELECT * FROM users WHERE role = ? AND (name LIKE ? OR username LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, 'ssss', $roleFilter, $like, $like, $like);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT * FROM users WHERE role != 'admin' AND (name LIKE ? OR username LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    }
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function set_user_status($conn, $id, $status) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_user($conn, $id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role != 'admin'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

// FEATURE (admin): set how much a specific reader may spend on one order.
function set_order_limit($conn, $readerId, $limit) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET order_limit = ? WHERE id = ? AND role = 'reader'");
    mysqli_stmt_bind_param($stmt, 'di', $limit, $readerId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}
