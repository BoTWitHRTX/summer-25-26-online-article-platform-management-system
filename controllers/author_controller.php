<?php
// ================================================================
// CONTROLLER: AUTHOR dashboard
// CRUD  : my articles (create / edit / delete, draft or published)
// Extras: 1) compare 2+ of my own books side by side
//         2) set / update price on any of my articles
//         3) save drafts (publish only when ready)
// ================================================================

function author_controller($conn) {
    $action   = $_GET['action'] ?? 'list';
    $me       = current_user();
    $authorId = (int)$me['id'];

    $error   = '';
    $editing = null;
    $categories = ['Fiction', 'Non-Fiction', 'Technology', 'Science', 'Poetry', 'Business', 'Other'];

    /* ---------------- CREATE ---------------- */
    if ($action === 'add' && is_post()) {
        csrf_check();

        $title    = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $price    = $_POST['price'] ?? '';
        $status   = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft'; // FEATURE 3: save as draft

        if (is_blank($title) || is_blank($category) || is_blank($content) || $price === '') {
            $error = 'Fill in every field.';
        } elseif (strlen($title) < 3) {
            $error = 'Title must be at least 3 characters.';
        } elseif (!in_array($category, $categories, true)) {
            $error = 'Choose a valid category.';
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $error = 'Price must be a number that is not negative.';
        } elseif (strlen($content) < 20) {
            $error = 'Content should be at least 20 characters.';
        } else {
            if (create_article($conn, $authorId, $title, $category, $content, (float)$price, $status)) {
                log_activity($conn, ucfirst($status) . ' "' . $title . '"');
                set_flash('success', $status === 'draft' ? 'Draft saved.' : 'Article published.');
                redirect('index.php?page=author');
            }
            $error = 'Could not save the article.';
        }
    }

    /* ---------------- UPDATE ---------------- */
    if ($action === 'update' && is_post()) {
        csrf_check();

        $id       = (int)($_GET['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $price    = $_POST['price'] ?? '';
        $status   = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $original = get_own_article($conn, $id, $authorId);

        $editing = ['id' => $id, 'title' => $title, 'category' => $category,
                    'content' => $content, 'price' => $price, 'status' => $status];

        // ===== NULL / EMPTY VALIDATION ON UPDATE =====
        if (!$original) {
            set_flash('error', 'That article does not belong to you.');
            redirect('index.php?page=author');
        } elseif (is_blank($title) || is_blank($category) || is_blank($content) || $price === '') {
            $error = 'No field can be left empty (NULL). All fields are required.';
        } elseif (!in_array($category, $categories, true)) {
            $error = 'Choose a valid category.';
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $error = 'Price must be a number that is not negative.';
        } else {
            if (update_article($conn, $id, $authorId, $title, $category, $content, (float)$price, $status)) {
                log_activity($conn, 'Updated "' . $title . '"');
                set_flash('success', 'Article updated.');
                redirect('index.php?page=author');
            }
            $error = 'Update failed.';
        }
    }

    /* ---------------- FEATURE 2: quick set-price ---------------- */
    if ($action === 'price' && is_post()) {
        csrf_check();
        $id    = (int)($_GET['id'] ?? 0);
        $price = $_POST['price'] ?? '';

        if (!is_numeric($price) || (float)$price < 0) {
            set_flash('error', 'Price must be a number that is not negative.');
        } elseif (set_article_price($conn, $id, $authorId, (float)$price)) {
            log_activity($conn, 'Set a new price (' . money($price) . ') on article #' . $id);
            set_flash('success', 'Price updated.');
        } else {
            set_flash('error', 'Could not update the price.');
        }
        redirect('index.php?page=author');
    }

    /* ---------------- READ one row into the form ---------------- */
    if ($action === 'edit' && !$editing) {
        $editing = get_own_article($conn, (int)($_GET['id'] ?? 0), $authorId);
        if (!$editing) {
            set_flash('error', 'That article does not belong to you.');
            redirect('index.php?page=author');
        }
    }

    /* ---------------- DELETE ---------------- */
    if ($action === 'delete') {
        csrf_check();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0 && delete_article($conn, $id, $authorId)) {
            log_activity($conn, 'Deleted article #' . $id);
            set_flash('success', 'Article deleted.');
        } else {
            set_flash('error', 'Could not delete that article.');
        }
        redirect('index.php?page=author');
    }

    /* ---------------- FEATURE 1: compare my own books ---------------- */
    $compareRows = [];
    if ($action === 'compare' && is_post()) {
        csrf_check();
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $ids = array_values(array_unique(array_filter($ids)));
        if (count($ids) < 2) {
            set_flash('error', 'Pick at least two of your books to compare.');
            redirect('index.php?page=author');
        }
        $compareRows = get_articles_for_compare($conn, $authorId, $ids);
    }

    /* ---------------- Data for the view ---------------- */
    $articles = get_author_articles($conn, $authorId);
    $stats = [
        'total'     => count($articles),
        'published' => count(array_filter($articles, fn($a) => $a['status'] === 'published')),
        'drafts'    => count(array_filter($articles, fn($a) => $a['status'] === 'draft')),
    ];
    $myFeedback = get_feedback_for_author($conn, $authorId);

    require __DIR__ . '/../views/author/dashboard.php';
}
