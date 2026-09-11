<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create an account &mdash; <?= esc(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
    <div class="auth-side">
        <div class="logo-big">&#128220;</div>
        <h1>Create your account</h1>
        <p>Pick the account type that matches what you want to do.</p>
        <ul class="feature-list">
            <li>&#10003; <strong>Reader</strong> &mdash; read & search articles, like & comment, rate</li>
            <li>&#10003; <strong>Author</strong> &mdash; compare books, set price, save drafts</li>
        </ul>
        <p class="side-note">Administrator accounts are created separately by the platform.</p>
    </div>

    <div class="auth-form-wrap">
        <div class="auth-card">
            <h2>Sign up</h2>
            <p class="muted">Takes less than a minute</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=register" class="form"
                  novalidate onsubmit="return validateForm(this);">
                <?php csrf_field(); ?>

                <div class="field">
                    <label for="role">Account type</label>
                    <select id="role" name="role" data-label="Account type" required>
                        <option value="reader" <?= $old['role'] === 'reader' ? 'selected' : '' ?>>Reader</option>
                        <option value="author" <?= $old['role'] === 'author' ? 'selected' : '' ?>>Author</option>
                    </select>
                </div>

                <div class="field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" data-label="Full name" data-min="3"
                           value="<?= esc($old['name']) ?>" placeholder="e.g. Farhana Islam" required>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" data-label="Email"
                               value="<?= esc($old['email']) ?>" placeholder="you@example.com" required>
                    </div>
                    <div class="field">
                        <label for="contact">Contact number</label>
                        <input type="text" id="contact" name="contact" data-label="Contact number" data-phone="1"
                               value="<?= esc($old['contact']) ?>" placeholder="+880 1XXXXXXXXX" required>
                    </div>
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" data-label="Username" data-min="4"
                           value="<?= esc($old['username']) ?>" placeholder="4-20 letters or numbers" required>
                    <!-- AJAX: filled in live by the script below -->
                    <span id="usernameNote" class="field-note"></span>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" data-label="Password" data-min="6"
                               placeholder="At least 6 characters" required>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Repeat password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               data-label="Repeat password" data-match="password"
                               placeholder="Type it again" required>
                    </div>
                </div>

                <!-- Role-specific fields: shown/hidden by the DOM script below -->
                <div class="field" id="authorFields">
                    <label for="bio">Author bio / pen name</label>
                    <input type="text" id="bio" name="bio" data-label="Author bio"
                           value="<?= esc($old['bio']) ?>" placeholder="A line about what you write">
                </div>

                <div class="field" id="readerFields">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" data-label="Address"
                           value="<?= esc($old['address']) ?>" placeholder="Delivery / contact address">
                </div>

                <div class="field">
                    <label for="security_question">Security question</label>
                    <input type="text" id="security_question" name="security_question"
                           data-label="Security question" value="<?= esc($old['security_question']) ?>"
                           placeholder="e.g. What was your first school?" required>
                </div>
                <div class="field">
                    <label for="security_answer">Answer</label>
                    <input type="text" id="security_answer" name="security_answer" data-label="Answer"
                           placeholder="Used to reset your password later" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create account</button>
            </form>

            <p class="auth-foot">
                Already have an account? <a href="index.php?page=login">Sign in</a>
            </p>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
// ---- DOM MANIPULATION: swap which extra field is required by role ----
(function () {
    var roleSelect    = document.getElementById('role');
    var authorFields   = document.getElementById('authorFields');
    var readerFields   = document.getElementById('readerFields');
    var bioInput       = document.getElementById('bio');
    var addressInput   = document.getElementById('address');

    function syncRoleFields() {
        var isAuthor = roleSelect.value === 'author';
        authorFields.style.display = isAuthor ? '' : 'none';
        readerFields.style.display = isAuthor ? 'none' : '';
        bioInput.required     = isAuthor;
        addressInput.required = !isAuthor;
    }

    roleSelect.addEventListener('change', syncRoleFields);
    syncRoleFields(); // run once on load
})();

// AJAX: ask the server whether the typed username is still free.
(function () {
    var input = document.getElementById('username');
    var note  = document.getElementById('usernameNote');
    var timer;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var value = input.value.trim();
        if (value === '') { note.textContent = ''; note.className = 'field-note'; return; }

        timer = setTimeout(function () {
            fetch('index.php?page=ajax&action=check_username&username=' + encodeURIComponent(value))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    note.textContent = data.message;
                    note.className = 'field-note ' + (data.ok ? 'note-ok' : 'note-bad');
                })
                .catch(function () { note.textContent = ''; });
        }, 300);
    });
})();
</script>
</body>
</html>
