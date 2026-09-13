<?php
// ================================================================
// MODEL: feedback - admin -> author messages. Full CRUD by the admin.
// ================================================================

function add_feedback($conn, $adminId, $authorId, $articleId, $message) {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO feedback (admin_id, author_id, article_id, message) VALUES (?, ?, ?, ?)");
    $articleIdParam = $articleId > 0 ? $articleId : null;
    mysqli_stmt_bind_param($stmt, 'iiis', $adminId, $authorId, $articleIdParam, $message);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function update_feedback($conn, $id, $adminId, $message) {
    $stmt = mysqli_prepare($conn, "UPDATE feedback SET message = ? WHERE id = ? AND admin_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $message, $id, $adminId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_feedback($conn, $id, $adminId) {
    $stmt = mysqli_prepare($conn, "DELETE FROM feedback WHERE id = ? AND admin_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $adminId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function get_feedback($conn, $id, $adminId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM feedback WHERE id = ? AND admin_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $adminId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function get_all_feedback($conn) {
    $stmt = mysqli_query($conn,
        "SELECT f.*, u.name AS author_name, a.title AS article_title
         FROM feedback f
         JOIN users u ON u.id = f.author_id
         LEFT JOIN articles a ON a.id = f.article_id
         ORDER BY f.created_at DESC");
    return mysqli_fetch_all($stmt, MYSQLI_ASSOC);
}

function search_feedback($conn, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT f.*, u.name AS author_name, a.title AS article_title
         FROM feedback f
         JOIN users u ON u.id = f.author_id
         LEFT JOIN articles a ON a.id = f.article_id
         WHERE u.name LIKE ? OR f.message LIKE ?
         ORDER BY f.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// Feedback aimed at one author - shown read-only on their own dashboard.
function get_feedback_for_author($conn, $authorId) {
    $stmt = mysqli_prepare($conn,
        "SELECT f.*, a.title AS article_title FROM feedback f
         LEFT JOIN articles a ON a.id = f.article_id
         WHERE f.author_id = ? ORDER BY f.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $authorId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}
