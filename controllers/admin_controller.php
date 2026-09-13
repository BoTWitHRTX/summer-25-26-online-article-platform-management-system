<?php
// ================================================================
// CONTROLLER: ADMIN dashboard
// CRUD  : feedback to authors (create / edit / delete)
//         + account status management (activate / suspend / delete)
// Extras: 1) set how much a reader may spend on one order
//         2) give feedback to authors
//         3) see sold books and profit/loss (AJAX-refreshed stats)
// ================================================================

function admin_controller($conn) {
    $action     = $_GET['action'] ?? 'list';
    $roleFilter = $_GET['role'] ?? '';
    $me         = current_user();
    $adminId    = (int)$me['id'];

    $error   = '';
    $editing = null;
    $roles    = ['author', 'reader'];
    $statuses = ['active', 'suspended'];

    /* ---------------- Account status / delete ---------------- */
    if ($action === 'status') {
        csrf_check();
        $id     = (int)($_GET['id'] ?? 0);
        $status = $_GET['to'] ?? '';
        if (in_array($status, $statuses, true) && set_user_status($conn, $id, $status)) {
            log_activity($conn, 'Set account #' . $id . ' to ' . $status);
            set_flash('success', 'Account is now ' . $status . '.');
        } else {
            set_flash('error', 'Could not change that account.');
        }
        redirect('index.php?page=admin');
    }

    if ($action === 'delete_user') {
        csrf_check();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0 && delete_user($conn, $id)) {
            log_activity($conn, 'Deleted account #' . $id);
            set_flash('success', 'Account deleted.');
        } else {
            set_flash('error', 'Could not delete that account.');
        }
        redirect('index.php?page=admin');
    }

    /* ---------------- FEATURE 1: set a reader's order limit ---------------- */
    if ($action === 'order_limit' && is_post()) {
        csrf_check();
        $id    = (int)($_GET['id'] ?? 0);
        $limit = $_POST['order_limit'] ?? '';

        if (!is_numeric($limit) || (float)$limit < 0) {
            set_flash('error', 'Order limit must be a number that is not negative.');
        } elseif (set_order_limit($conn, $id, (float)$limit)) {
            log_activity($conn, 'Set order limit for reader #' . $id . ' to ' . money($limit));
            set_flash('success', 'Order limit updated.');
        } else {
            set_flash('error', 'Could not update the order limit.');
        }
        redirect('index.php?page=admin');
    }

    /* ---------------- FEATURE 2: feedback CRUD ---------------- */
    if ($action === 'feedback_add' && is_post()) {
        csrf_check();
        $authorId  = (int)($_POST['author_id'] ?? 0);
        $articleId = (int)($_POST['article_id'] ?? 0);
        $message   = trim($_POST['message'] ?? '');
        $author    = $authorId > 0 ? get_user($conn, $authorId) : null;

        if (!$author || $author['role'] !== 'author') {
            $error = 'Choose a valid author.';
        } elseif (is_blank($message)) {
            $error = 'Write a feedback message.';
        } elseif (strlen($message) > 500) {
            $error = 'Feedback must be shorter than 500 characters.';
        } elseif (add_feedback($conn, $adminId, $authorId, $articleId, $message)) {
            log_activity($conn, 'Gave feedback to ' . $author['username']);
            set_flash('success', 'Feedback sent.');
            redirect('index.php?page=admin');
        } else {
            $error = 'Could not send feedback.';
        }
    }

    if ($action === 'feedback_edit' && !$editing) {
        $editing = get_feedback($conn, (int)($_GET['id'] ?? 0), $adminId);
        if (!$editing) {
            set_flash('error', 'That feedback does not belong to you.');
            redirect('index.php?page=admin');
        }
    }

    if ($action === 'feedback_update' && is_post()) {
        csrf_check();
        $id      = (int)($_GET['id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $original = get_feedback($conn, $id, $adminId);

        if (!$original) {
            set_flash('error', 'That feedback does not belong to you.');
            redirect('index.php?page=admin');
        } elseif (is_blank($message)) {
            $error   = 'Feedback cannot be empty.';
            $editing = array_merge($original, ['message' => $message]);
        } elseif (update_feedback($conn, $id, $adminId, $message)) {
            log_activity($conn, 'Updated feedback #' . $id);
            set_flash('success', 'Feedback updated.');
            redirect('index.php?page=admin');
        } else {
            $error   = 'Update failed.';
            $editing = $original;
        }
    }

    if ($action === 'feedback_delete') {
        csrf_check();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0 && delete_feedback($conn, $id, $adminId)) {
            log_activity($conn, 'Deleted feedback #' . $id);
            set_flash('success', 'Feedback deleted.');
        } else {
            set_flash('error', 'Could not delete that feedback.');
        }
        redirect('index.php?page=admin');
    }

    /* ---------------- Data for the view ---------------- */
    if (!in_array($roleFilter, $roles, true)) {
        $roleFilter = '';
    }
    $users        = get_users($conn, $roleFilter);
    $authors      = get_users($conn, 'author');
    $stats        = get_sales_stats($conn);           // FEATURE 3
    $soldBooks    = get_sold_books($conn);             // FEATURE 3
    $feedbackList = get_all_feedback($conn);
    $logs         = get_logs($conn, 12);

    require __DIR__ . '/../views/admin/dashboard.php';
}
