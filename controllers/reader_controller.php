<?php
// ================================================================
// CONTROLLER: READER dashboard
// CRUD  : my comments (create / edit / delete own comments)
// Extras: 1) read & search published articles
//         2) like & comment on an article
//         3) rate an article (1-5 stars)
// (Ordering an article lives here too - it is how "Read" turns into
//  owning a copy, and it is capped by the admin's order_limit feature.)
// ================================================================

function reader_controller($conn) {
    $action   = $_GET['action'] ?? 'browse';
    $me       = current_user();
    $readerId = (int)$me['id'];
    // Order limit can change any time an admin edits it - always show/use the
    // live value instead of the one cached in the session at login.
    $me['order_limit'] = (float)get_user($conn, $readerId)['order_limit'];

    $error   = '';
    $editing = null;

    /* ---------------- Like (toggle) ---------------- */
    if ($action === 'like') {
        csrf_check();
        $articleId = (int)($_GET['id'] ?? 0);
        $article   = get_article($conn, $articleId);
        if ($article && $article['status'] === 'published') {
            $liked = toggle_like($conn, $articleId, $readerId);
            log_activity($conn, ($liked ? 'Liked' : 'Unliked') . ' "' . $article['title'] . '"');
        }
        redirect('index.php?page=reader&action=view&id=' . $articleId);
    }

    /* ---------------- Rate ---------------- */
    if ($action === 'rate' && is_post()) {
        csrf_check();
        $articleId = (int)($_POST['article_id'] ?? 0);
        $rating    = (int)($_POST['rating'] ?? 0);
        $article   = get_article($conn, $articleId);

        if (!$article || $article['status'] !== 'published') {
            set_flash('error', 'That article is not available.');
        } elseif ($rating < 1 || $rating > 5) {
            set_flash('error', 'Choose a rating between 1 and 5.');
        } else {
            rate_article($conn, $articleId, $readerId, $rating);
            log_activity($conn, 'Rated "' . $article['title'] . '" ' . $rating . ' stars');
            set_flash('success', 'Thanks for rating!');
        }
        redirect('index.php?page=reader&action=view&id=' . $articleId);
    }

    /* ---------------- Order (buy a published article) ---------------- */
    if ($action === 'order') {
        csrf_check();
        $articleId = (int)($_GET['id'] ?? 0);
        $article   = get_article($conn, $articleId);

        if (!$article || $article['status'] !== 'published') {
            set_flash('error', 'That article is not available.');
        } elseif (has_ordered($conn, $readerId, $articleId)) {
            set_flash('error', 'You already ordered this article.');
        } elseif ((float)$article['price'] > (float)$me['order_limit']) {
            set_flash('error', 'This exceeds your order limit of ' . money($me['order_limit'])
                . '. Ask the administrator to raise it.');
        } else {
            create_order($conn, $readerId, $articleId, $article['price']);
            log_activity($conn, 'Ordered "' . $article['title'] . '" for ' . money($article['price']));
            set_flash('success', 'Order placed for "' . $article['title'] . '".');
        }
        redirect('index.php?page=reader&action=view&id=' . $articleId);
    }

    /* ---------------- CREATE a comment ---------------- */
    if ($action === 'comment_add' && is_post()) {
        csrf_check();
        $articleId = (int)($_POST['article_id'] ?? 0);
        $text      = trim($_POST['comment'] ?? '');
        $article   = get_article($conn, $articleId);

        if (!$article || $article['status'] !== 'published') {
            set_flash('error', 'That article is not available.');
        } elseif (is_blank($text)) {
            set_flash('error', 'Write something before posting.');
        } elseif (strlen($text) > 500) {
            set_flash('error', 'Comments must be shorter than 500 characters.');
        } elseif (add_comment($conn, $articleId, $readerId, $text)) {
            log_activity($conn, 'Commented on "' . $article['title'] . '"');
            set_flash('success', 'Comment posted.');
        } else {
            set_flash('error', 'Could not post the comment.');
        }
        redirect('index.php?page=reader&action=view&id=' . $articleId);
    }

    /* ---------------- UPDATE a comment ---------------- */
    if ($action === 'comment_update' && is_post()) {
        csrf_check();
        $id       = (int)($_GET['id'] ?? 0);
        $text     = trim($_POST['comment'] ?? '');
        $original = get_comment($conn, $id, $readerId);

        if (!$original) {
            set_flash('error', 'That comment does not belong to you.');
            redirect('index.php?page=reader&action=comments');
        }

        $backTo = 'index.php?page=reader&action=view&id=' . $original['article_id'];

        if (is_blank($text)) {
            set_flash('error', 'Comments cannot be empty.');
            redirect($backTo . '&edit=' . $id);
        } elseif (strlen($text) > 500) {
            set_flash('error', 'Comments must be shorter than 500 characters.');
            redirect($backTo . '&edit=' . $id);
        } elseif (update_comment($conn, $id, $readerId, $text)) {
            log_activity($conn, 'Updated a comment');
            set_flash('success', 'Comment updated.');
            redirect($backTo);
        } else {
            set_flash('error', 'Update failed.');
            redirect($backTo);
        }
    }

    /* ---------------- DELETE a comment ---------------- */
    if ($action === 'comment_delete') {
        csrf_check();
        $id       = (int)($_GET['id'] ?? 0);
        $original = get_comment($conn, $id, $readerId);
        $backTo   = $original ? 'index.php?page=reader&action=view&id=' . $original['article_id']
                               : 'index.php?page=reader&action=comments';

        if ($original && delete_comment($conn, $id, $readerId)) {
            log_activity($conn, 'Deleted a comment');
            set_flash('success', 'Comment deleted.');
        } else {
            set_flash('error', 'Could not delete that comment.');
        }
        redirect($backTo);
    }

    /* ---------------- READ one article + its comments ---------------- */
    if ($action === 'view') {
        $articleId = (int)($_GET['id'] ?? 0);
        $article   = get_article($conn, $articleId);
        if (!$article || $article['status'] !== 'published') {
            set_flash('error', 'That article is not available.');
            redirect('index.php?page=reader');
        }
        $comments   = get_article_comments($conn, $articleId);
        $myRating   = get_reader_rating($conn, $articleId, $readerId);
        $iLiked     = has_liked($conn, $articleId, $readerId);
        $iOrdered   = has_ordered($conn, $readerId, $articleId);
        // Free articles are always readable; priced ones need a purchase first.
        $canReadFull = (float)$article['price'] <= 0 || $iOrdered;

        // Only used to show one comment in edit mode inline (must be mine).
        $editingCommentId = (int)($_GET['edit'] ?? 0);
        $editingComment   = $editingCommentId > 0 ? get_comment($conn, $editingCommentId, $readerId) : null;

        require __DIR__ . '/../views/reader/article.php';
        return;
    }

    /* ---------------- Data for the main dashboard ---------------- */
    $articles     = get_published_articles($conn);
    $myComments   = get_reader_comments($conn, $readerId);
    $myOrders     = get_reader_orders($conn, $readerId);

    require __DIR__ . '/../views/reader/dashboard.php';
}
