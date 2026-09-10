<?php

// SECURITY: always print user data through esc() to stop XSS.
function esc($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// Redirect to another page
function redirect($url)
{
    header("Location: $url");
    exit;
}


// Check if request method is POST
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}


// Check whether user is logged in
function is_logged_in()
{
    return isset($_SESSION['user']);
}


// Get currently logged-in user
function current_user()
{
    return $_SESSION['user'] ?? null;
}


// Get currently logged-in user's role
function current_role()
{
    return $_SESSION['user']['role'] ?? null;
}


// Check session timeout
function check_session_timeout()
{
    if (!is_logged_in()) {
        return;
    }

    $timeout = SESSION_TIMEOUT;

    if (isset($_SESSION['last_active'])) {

        if (time() - $_SESSION['last_active'] > $timeout) {

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"] ?? '',
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();

            session_start();

            set_flash('error', 'Your session has expired. Please login again.');

            redirect('index.php?page=login');
        }
    }

    $_SESSION['last_active'] = time();
}


// Require user to be logged in
function require_login()
{
    if (!is_logged_in()) {
        set_flash('error', 'Please login first.');
        redirect('index.php?page=login');
    }
}


// Require a specific role
function require_role($role)
{
    require_login();

    if (current_role() !== $role) {

        set_flash('error', 'You do not have permission to access this page.');

        redirect('index.php?page=' . current_role());
    }
}


// Flash message
function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


// Get and remove flash message
function get_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];

    unset($_SESSION['flash']);

    return $flash;
}


// Validate email
function valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


// Check blank value
function is_blank($value)
{
    return trim($value) === '';
}


// Validate username
function valid_username($username)
{
    return preg_match('/^[A-Za-z0-9_]{4,20}$/', $username);
}


// Generate CSRF token
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


// Create CSRF hidden field
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' .
        esc(csrf_token()) .
        '">';
}


// Check CSRF token
function csrf_check()
{
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('Invalid request.');
    }
}


// Return JSON response
function json_out($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}