<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot password &mdash; <?= esc(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
    <div class="auth-side">
        <div class="logo-big">&#128273;</div>
        <h1>Reset your password</h1>
        <p>Answer your security question to choose a new password. No email needed.</p>
    </div>

    <div class="auth-form-wrap">
        <div class="auth-card">
            <?php if ($step === 1): ?>
                <h2>Find your account</h2>
                <p class="muted">Step 1 of 2</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= esc($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=forgot" class="form"
                      novalidate onsubmit="return validateForm(this);">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="stage" value="find">

                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" data-label="Username"
                               placeholder="Your username" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Continue</button>
                </form>

            <?php else: ?>
                <h2>Answer &amp; reset</h2>
                <p class="muted">Step 2 of 2 &mdash; <?= esc($foundUsername) ?></p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= esc($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=forgot" class="form"
                      novalidate onsubmit="return validateForm(this);">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="stage" value="reset">

                    <div class="field">
                        <label><?= esc($question) ?></label>
                        <input type="text" name="security_answer" data-label="Answer" placeholder="Your answer" required>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="password">New password</label>
                            <input type="password" id="password" name="password" data-label="New password"
                                   data-min="6" placeholder="At least 6 characters" required>
                        </div>
                        <div class="field">
                            <label for="confirm_password">Repeat password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   data-label="Repeat password" data-match="password" placeholder="Type it again" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Reset password</button>
                </form>
            <?php endif; ?>

            <p class="auth-foot">
                <a href="index.php?page=login">Back to sign in</a>
            </p>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
