<?php
// ================================================================
// CONTROLLER: AJAX / JSON endpoints
// The dashboards call these with fetch() and redraw a table without
// reloading the page. Every endpoint (except username-check) checks
// the role first.
// ================================================================

function ajax_controller($conn) {
    $action = $_GET['action'] ?? '';
    $term   = trim($_GET['q'] ?? '');

    /* ---- Public endpoint: live "is this username free?" on the signup page ---- */
    if ($action === 'check_username') {
        $username = trim($_GET['username'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $username)) {
            json_out(['ok' => false, 'message' => 'Use 4-20 letters, numbers or underscores.']);
        }
        json_out(username_exists($conn, $username)
            ? ['ok' => false, 'message' => 'That username is taken.']
            : ['ok' => true,  'message' => 'That username is free.']);
    }

    /* ---- Everything below needs a logged-in user ---- */
    if (!is_logged_in()) {
        json_out(['error' => 'Please sign in first.'], 401);
    }

    $user = current_user();
    $role = $user['role'];
    $id   = (int)$user['id'];

    switch ($action) {

        /* ----------- Reader: live search of published articles ----------- */
        case 'search_articles':
            if ($role !== 'reader') break;
            json_out($term === '' ? get_published_articles($conn) : search_published_articles($conn, $term));

        /* ----------- Reader: search my own comments ----------- */
        case 'search_comments':
            if ($role !== 'reader') break;
            json_out($term === '' ? get_reader_comments($conn, $id) : search_reader_comments($conn, $id, $term));

        /* ----------- Author: search my own articles ----------- */
        case 'search_my_articles':
            if ($role !== 'author') break;
            json_out($term === '' ? get_author_articles($conn, $id) : search_author_articles($conn, $id, $term));

        /* ----------- Admin: search accounts ----------- */
        case 'search_users':
            if ($role !== 'admin') break;
            $roleFilter = $_GET['role'] ?? '';
            if (!in_array($roleFilter, ['author', 'reader'], true)) {
                $roleFilter = '';
            }
            json_out($term === '' ? get_users($conn, $roleFilter) : search_users($conn, $term, $roleFilter));

        /* ----------- Admin: live sales/profit stats panel ----------- */
        case 'stats':
            if ($role !== 'admin') break;
            json_out(get_sales_stats($conn));

        /* ----------- Admin: search sold books ----------- */
        case 'search_sales':
            if ($role !== 'admin') break;
            json_out($term === '' ? get_sold_books($conn) : search_sold_books($conn, $term));

        /* ----------- Admin: search feedback ----------- */
        case 'search_feedback':
            if ($role !== 'admin') break;
            json_out($term === '' ? get_all_feedback($conn) : search_feedback($conn, $term));

        /* ----------- Admin: search activity log ----------- */
        case 'search_logs':
            if ($role !== 'admin') break;
            json_out($term === '' ? get_logs($conn, 12) : search_logs($conn, $term, 50));
    }

    json_out(['error' => 'You are not allowed to use this endpoint.'], 403);
}
