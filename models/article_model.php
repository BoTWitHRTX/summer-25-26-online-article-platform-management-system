<?php
// ================================================================
// MODEL: articles (books/articles written by authors)
// ================================================================

function create_article($conn, $authorId, $title, $category, $content, $price, $status) {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO articles (author_id, title, category, content, price, status) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isssds', $authorId, $title, $category, $content, $price, $status);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function update_article($conn, $id, $authorId, $title, $category, $content, $price, $status) {
    $stmt = mysqli_prepare($conn,
        "UPDATE articles SET title = ?, category = ?, content = ?, price = ?, status = ?, updated_at = NOW()
         WHERE id = ? AND author_id = ?");
    mysqli_stmt_bind_param($stmt, 'sssdsii', $title, $category, $content, $price, $status, $id, $authorId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

// FEATURE (author): set/update just the price of one of their own articles.
function set_article_price($conn, $id, $authorId, $price) {
    $stmt = mysqli_prepare($conn, "UPDATE articles SET price = ? WHERE id = ? AND author_id = ?");
    mysqli_stmt_bind_param($stmt, 'dii', $price, $id, $authorId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_article($conn, $id, $authorId) {
    $stmt = mysqli_prepare($conn, "DELETE FROM articles WHERE id = ? AND author_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $authorId);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

function get_article($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function get_own_article($conn, $id, $authorId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE id = ? AND author_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $authorId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

// All of one author's articles (drafts + published) - their CRUD table.
function get_author_articles($conn, $authorId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE author_id = ? ORDER BY updated_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $authorId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function search_author_articles($conn, $authorId, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM articles WHERE author_id = ? AND (title LIKE ? OR category LIKE ?) ORDER BY updated_at DESC");
    mysqli_stmt_bind_param($stmt, 'iss', $authorId, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/* ---------------- Reader-facing: published articles only ---------------- */

function get_published_articles($conn) {
    $stmt = mysqli_query($conn,
        "SELECT a.*, u.name AS author_name,
                (SELECT COUNT(*) FROM likes l WHERE l.article_id = a.id) AS like_count,
                (SELECT COUNT(*) FROM comments c WHERE c.article_id = a.id) AS comment_count,
                (SELECT ROUND(AVG(rating),1) FROM ratings r WHERE r.article_id = a.id) AS avg_rating
         FROM articles a JOIN users u ON u.id = a.author_id
         WHERE a.status = 'published' ORDER BY a.created_at DESC");
    return mysqli_fetch_all($stmt, MYSQLI_ASSOC);
}

function search_published_articles($conn, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT a.*, u.name AS author_name,
                (SELECT COUNT(*) FROM likes l WHERE l.article_id = a.id) AS like_count,
                (SELECT COUNT(*) FROM comments c WHERE c.article_id = a.id) AS comment_count,
                (SELECT ROUND(AVG(rating),1) FROM ratings r WHERE r.article_id = a.id) AS avg_rating
         FROM articles a JOIN users u ON u.id = a.author_id
         WHERE a.status = 'published' AND (a.title LIKE ? OR a.category LIKE ? OR u.name LIKE ?)
         ORDER BY a.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/* ---------------- Author feature: compare own books side by side ---------------- */

function get_articles_for_compare($conn, $authorId, array $ids) {
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = 'i' . str_repeat('i', count($ids) - 1) . '';
    $sql = "SELECT a.*,
                (SELECT COUNT(*) FROM likes l WHERE l.article_id = a.id) AS like_count,
                (SELECT COUNT(*) FROM orders o WHERE o.article_id = a.id) AS sales_count,
                (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.article_id = a.id) AS revenue,
                (SELECT ROUND(AVG(rating),1) FROM ratings r WHERE r.article_id = a.id) AS avg_rating
            FROM articles a WHERE a.author_id = ? AND a.id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    $params = array_merge([$authorId], $ids);
    $bindTypes = 'i' . str_repeat('i', count($ids));
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}
