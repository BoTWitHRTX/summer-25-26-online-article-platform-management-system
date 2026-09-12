<?php
// ================================================================
// MODEL: likes, comments, ratings - the Reader's "Like & Comment" and
// "Rating" features. Comments are full CRUD (create/read/update/delete);
// likes and ratings are toggle/upsert since only one per reader makes sense.
// ================================================================

/* ---------------- Likes ---------------- */

function has_liked($conn, $articleId, $readerId) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM likes WHERE article_id = ? AND reader_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $articleId, $readerId);
    mysqli_stmt_execute($stmt);
    $found = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    return $found;
}

// Returns the new state: true = now liked, false = now unliked.
function toggle_like($conn, $articleId, $readerId) {
    if (has_liked($conn, $articleId, $readerId)) {
        $stmt = mysqli_prepare($conn, "DELETE FROM likes WHERE article_id = ? AND reader_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $articleId, $readerId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return false;
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO likes (article_id, reader_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ii', $articleId, $readerId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return true;
}

/* ---------------- Comments (reader CRUD) ---------------- */

function add_comment($conn, $articleId, $readerId, $text) {
    $stmt = mysqli_prepare($conn, "INSERT INTO comments (article_id, reader_id, comment) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iis', $articleId, $readerId, $text);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function update_comment($conn, $id, $readerId, $text) {
    $stmt = mysqli_prepare($conn, "UPDATE comments SET comment = ?, updated_at = NOW() WHERE id = ? AND reader_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $text, $id, $readerId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_comment($conn, $id, $readerId) {
    $stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE id = ? AND reader_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $readerId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function get_comment($conn, $id, $readerId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM comments WHERE id = ? AND reader_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $readerId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function get_article_comments($conn, $articleId) {
    $stmt = mysqli_prepare($conn,
        "SELECT c.*, u.name AS reader_name FROM comments c JOIN users u ON u.id = c.reader_id
         WHERE c.article_id = ? ORDER BY c.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $articleId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// A reader's own comments across every article - the searchable CRUD table.
function get_reader_comments($conn, $readerId) {
    $stmt = mysqli_prepare($conn,
        "SELECT c.*, a.title AS article_title FROM comments c JOIN articles a ON a.id = c.article_id
         WHERE c.reader_id = ? ORDER BY c.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $readerId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function search_reader_comments($conn, $readerId, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT c.*, a.title AS article_title FROM comments c JOIN articles a ON a.id = c.article_id
         WHERE c.reader_id = ? AND (c.comment LIKE ? OR a.title LIKE ?) ORDER BY c.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'iss', $readerId, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/* ---------------- Ratings ---------------- */

function get_reader_rating($conn, $articleId, $readerId) {
    $stmt = mysqli_prepare($conn, "SELECT rating FROM ratings WHERE article_id = ? AND reader_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $articleId, $readerId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['rating'] : 0;
}

// Create or update - a reader can only ever have one rating per article.
function rate_article($conn, $articleId, $readerId, $rating) {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO ratings (article_id, reader_id, rating) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
    mysqli_stmt_bind_param($stmt, 'iii', $articleId, $readerId, $rating);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
