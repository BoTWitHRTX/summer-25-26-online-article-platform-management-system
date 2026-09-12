<?php
// ================================================================
// MODEL: orders - a reader buying a published article.
// Feeds the admin's "sold books and loss/profit" feature.
// ================================================================

define('PLATFORM_COST_RATE', 0.30); // assumed production/hosting cost as a share of price, for profit/loss

function create_order($conn, $readerId, $articleId, $amount) {
    $stmt = mysqli_prepare($conn, "INSERT INTO orders (reader_id, article_id, amount) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iid', $readerId, $articleId, $amount);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function has_ordered($conn, $readerId, $articleId) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE reader_id = ? AND article_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $readerId, $articleId);
    mysqli_stmt_execute($stmt);
    $found = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    return $found;
}

function get_reader_orders($conn, $readerId) {
    $stmt = mysqli_prepare($conn,
        "SELECT o.*, a.title FROM orders o JOIN articles a ON a.id = o.article_id
         WHERE o.reader_id = ? ORDER BY o.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $readerId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// FEATURE (admin): every sale with revenue, assumed cost and profit/loss.
function get_sold_books($conn) {
    $stmt = mysqli_query($conn,
        "SELECT o.*, a.title, a.category, u.name AS author_name, r.name AS reader_name
         FROM orders o
         JOIN articles a ON a.id = o.article_id
         JOIN users u ON u.id = a.author_id
         JOIN users r ON r.id = o.reader_id
         ORDER BY o.created_at DESC");
    return mysqli_fetch_all($stmt, MYSQLI_ASSOC);
}

function search_sold_books($conn, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT o.*, a.title, a.category, u.name AS author_name, r.name AS reader_name
         FROM orders o
         JOIN articles a ON a.id = o.article_id
         JOIN users u ON u.id = a.author_id
         JOIN users r ON r.id = o.reader_id
         WHERE a.title LIKE ? OR u.name LIKE ? OR r.name LIKE ?
         ORDER BY o.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// Live stats panel for the admin dashboard (AJAX-refreshed).
function get_sales_stats($conn) {
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS total_orders, COALESCE(SUM(amount),0) AS total_revenue FROM orders"));
    $revenue = (float)$row['total_revenue'];
    $cost    = $revenue * PLATFORM_COST_RATE;
    $profit  = $revenue - $cost;

    $userCounts = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT
            SUM(role = 'author') AS authors,
            SUM(role = 'reader') AS readers
         FROM users"));

    $articleCounts = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(status = 'published') AS published, SUM(status = 'draft') AS drafts FROM articles"));

    return [
        'total_orders'   => (int)$row['total_orders'],
        'total_revenue'  => round($revenue, 2),
        'total_cost'     => round($cost, 2),
        'total_profit'   => round($profit, 2),
        'authors'        => (int)($userCounts['authors'] ?? 0),
        'readers'        => (int)($userCounts['readers'] ?? 0),
        'published'      => (int)($articleCounts['published'] ?? 0),
        'drafts'         => (int)($articleCounts['drafts'] ?? 0),
    ];
}
