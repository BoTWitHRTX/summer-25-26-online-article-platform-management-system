<?php
// ================================================================
// CONTROLLER: login / register / logout / forgot password
// ================================================================

/* ===================== LOGIN (all roles, incl. admin) ===================== */
function login_controller($conn) {
    if (is_logged_in()) {
        redirect('index.php?page=' . current_role());
    }

    $error = '';
    // COOKIE: "remember me" only refills the username, never the password.
    $prefill = $_COOKIE['remember_user'] ?? '';

    if (is_post()) {
        csrf_check();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (is_blank($username) || is_blank($password)) {
            $error = 'Enter both your username and password.';
        } else {
            $user = find_user_by_username($conn, $username);

            // Same message for a wrong username and a wrong password, so
            // nobody can find out which usernames exist.
            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Wrong username or password.';
            } elseif ($user['status'] === 'suspended') {
                $error = 'This account is suspended. Please contact the administrator.';
            } else {
                // SECURITY: a fresh session id blocks session-fixation attacks.
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'          => (int)$user['id'],
                    'name'        => $user['name'],
                    'username'    => $user['username'],
                    'email'       => $user['email'],
                    'contact'     => $user['contact'],
                    'role'        => $user['role'],
                    'order_limit' => (float)$user['order_limit'],
                    'created_at'  => $user['created_at'],
                ];
                $_SESSION['last_active'] = time();

                if ($remember) {
                    setcookie('remember_user', $username, [
                        'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/',
                        'httponly' => true, 'samesite' => 'Lax',
                    ]);
                } else {
                    setcookie('remember_user', '', time() - 3600, '/');
                }

                log_activity($conn, 'Signed in');
                redirect('index.php?page=' . $user['role']);
            }
        }
    }

    require __DIR__ . '/../views/auth/login.php';
}

/* ============ REGISTER (author / reader only - never admin) ============ */
function register_controller($conn) {
    if (is_logged_in()) {
        redirect('index.php?page=' . current_role());
    }

    $error = '';
    $old = ['name' => '', 'email' => '', 'contact' => '', 'username' => '', 'role' => 'reader',
            'bio' => '', 'address' => '', 'security_question' => ''];

    if (is_post()) {
        csrf_check();

        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $contact   = trim($_POST['contact'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $role      = $_POST['role'] ?? '';
        $bio       = trim($_POST['bio'] ?? '');           // author only
        $address   = trim($_POST['address'] ?? '');        // reader only
        $question  = trim($_POST['security_question'] ?? '');
        $answer    = trim($_POST['security_answer'] ?? '');

        $old = compact('name', 'email', 'contact', 'username', 'role', 'bio', 'address');
        $old['security_question'] = $question;

        $allowedRoles = ['author', 'reader'];

        if (is_blank($name) || is_blank($email) || is_blank($contact) || is_blank($username)
            || is_blank($password) || is_blank($question) || is_blank($answer)) {
            $error = 'Fill in every required field.';
        } elseif (!in_array($role, $allowedRoles, true)) {
            $error = 'Choose an account type.';
        } elseif (strlen($name) < 3) {
            $error = 'Name must be at least 3 characters.';
        } elseif (!valid_email($email)) {
            $error = 'Enter a valid email address.';
        } elseif (!valid_contact($contact)) {
            $error = 'Enter a valid contact number (6-20 digits).';
        } elseif (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $username)) {
            $error = 'Username must be 4-20 letters, numbers or underscores.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'The two passwords do not match.';
        } elseif ($role === 'author' && is_blank($bio)) {
            $error = 'Authors must add a short bio / pen name.';
        } elseif ($role === 'reader' && is_blank($address)) {
            $error = 'Readers must add a delivery/contact address.';
        } elseif (strlen($answer) < 2) {
            $error = 'Your security answer is too short.';
        } elseif (username_exists($conn, $username)) {
            $error = 'That username is already taken.';
        } else {
            $bio     = $role === 'author' ? $bio : '';
            $address = $role === 'reader' ? $address : '';
            if (create_user($conn, $name, $email, $contact, $username, $password, $role, $bio, $address, $question, $answer)) {
                log_activity($conn, 'New ' . $role . ' account registered: ' . $username);
                set_flash('success', 'Account created. You can sign in now.');
                redirect('index.php?page=login');
            }
            $error = 'Could not create the account. Please try again.';
        }
    }

    require __DIR__ . '/../views/auth/register.php';
}

/* ===================== FORGOT PASSWORD (2 steps) ===================== */
function forgot_password_controller($conn) {
    if (is_logged_in()) {
        redirect('index.php?page=' . current_role());
    }

    $error = '';
    $step  = 1;
    $question = '';
    $foundUsername = '';

    // Step 1 -> 2: look up the username, reveal its security question.
    if (is_post() && ($_POST['stage'] ?? '') === 'find') {
        csrf_check();
        $username = trim($_POST['username'] ?? '');
        $user = is_blank($username) ? null : find_user_by_username($conn, $username);

        if (!$user || $user['role'] === 'admin') {
            $error = 'No account matches that username.';
        } else {
            $_SESSION['reset_user_id'] = (int)$user['id'];
            $step = 2;
            $question = $user['security_question'];
            $foundUsername = $user['username'];
        }
    }

    // Step 2: check the answer, set the new password.
    if (is_post() && ($_POST['stage'] ?? '') === 'reset') {
        csrf_check();
        $userId   = (int)($_SESSION['reset_user_id'] ?? 0);
        $answer   = trim($_POST['security_answer'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $user     = $userId > 0 ? get_user($conn, $userId) : null;

        if (!$user) {
            $error = 'Your session expired. Please start again.';
            unset($_SESSION['reset_user_id']);
        } else {
            $step = 2;
            $question = $user['security_question'];
            $foundUsername = $user['username'];

            if (is_blank($answer) || is_blank($password)) {
                $error = 'Answer the security question and choose a new password.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'The two passwords do not match.';
            } elseif (!verify_security_answer($conn, $userId, $answer)) {
                $error = 'That answer does not match our records.';
            } else {
                update_password($conn, $userId, $password);
                unset($_SESSION['reset_user_id']);
                log_activity($conn, 'Reset password via security question');
                set_flash('success', 'Password reset. You can sign in with your new password now.');
                redirect('index.php?page=login');
            }
        }
    }

    require __DIR__ . '/../views/auth/forgot_password.php';
}

/* ===================== LOGOUT ===================== */
function logout_controller($conn) {
    if (is_logged_in()) {
        log_activity($conn, 'Signed out');
    }
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
    setcookie('remember_user', '', time() - 3600, '/');
    session_start();
    set_flash('success', 'You have been signed out.');
    redirect('index.php?page=login');
}
