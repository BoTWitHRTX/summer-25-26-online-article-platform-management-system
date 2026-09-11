<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in &mdash; <?= esc(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
    <div class="auth-side">
        <div class="logo-big">&#128220;</div>
        <h1><?= esc(APP_NAME) ?></h1>
        <p>Where readers discover, authors publish, and admins keep the shelves in order.</p>
        <ul class="feature-list">
            <li>&#10003; <strong>Reader</strong> &mdash; read, search, like, comment, rate</li>
            <li>&#10003; <strong>Author</strong> &mdash; write, price and publish your work</li>
            <li>&#10003; <strong>Admin</strong> &mdash; oversee sales, limits and feedback</li>
        </ul>
    </div>

    <div class="auth-form-wrap">
        <div class="auth-card">
            <h2>Sign in</h2>
            <p class="muted">Welcome back</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <?php foreach (get_flash() as $flash): ?>
                <div class="alert alert-<?= esc($flash['type']) ?>"><?= esc($flash['message']) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="index.php?page=login" class="form"
                  novalidate onsubmit="return validateForm(this);">
                <?php csrf_field(); ?>

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" data-label="Username"
                           value="<?= esc($prefill) ?>" placeholder="Your username" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" data-label="Password"
                           placeholder="Your password" required>
                </div>

                <div class="field-row space-between">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" <?= $prefill ? 'checked' : '' ?>>
                        Remember my username
                    </label>
                    <a href="index.php?page=forgot" class="link-muted">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>

            <p class="auth-foot">
                New here? <a href="index.php?page=register">Create an account</a>
            </p>
            <p class="side-note center">Admin accounts are pre-provisioned, not self-signup.</p>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
