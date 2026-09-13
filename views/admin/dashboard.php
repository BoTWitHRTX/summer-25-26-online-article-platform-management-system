<?php
$pageTitle   = 'Admin Dashboard';
$pageHeading = 'Platform overview';
$pageSub     = 'Manage accounts, order limits, feedback and sales.';
require __DIR__ . '/../partials/header.php';
?>

<!-- FEATURE 3: live stats panel (AJAX-refreshed) -->
<div class="stat-row" id="statsRow">
    <div class="stat-card"><span class="stat-num"><?= $stats['total_orders'] ?></span><span class="stat-label">Orders</span></div>
    <div class="stat-card"><span class="stat-num"><?= money($stats['total_revenue']) ?></span><span class="stat-label">Revenue</span></div>
    <div class="stat-card"><span class="stat-num"><?= money($stats['total_cost']) ?></span><span class="stat-label">Est. cost</span></div>
    <div class="stat-card"><span class="stat-num"><?= money($stats['total_profit']) ?></span><span class="stat-label">Profit / loss</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['authors'] ?></span><span class="stat-label">Authors</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['readers'] ?></span><span class="stat-label">Readers</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['published'] ?></span><span class="stat-label">Published</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['drafts'] ?></span><span class="stat-label">Drafts</span></div>
</div>

<!-- CRUD: accounts + FEATURE 1 (set reader order limit) -->
<section class="panel">
    <div class="panel-head">
        <h2>Accounts</h2>
        <div class="panel-head-controls">
            <select id="roleFilter">
                <option value="">All roles</option>
                <option value="author" <?= $roleFilter === 'author' ? 'selected' : '' ?>>Authors</option>
                <option value="reader" <?= $roleFilter === 'reader' ? 'selected' : '' ?>>Readers</option>
            </select>
            <input type="text" id="userSearch" class="search-box" placeholder="Search accounts&hellip;">
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Order limit</th><th></th></tr></thead>
            <tbody id="usersBody">
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= esc($u['name']) ?></td>
                        <td><?= esc($u['username']) ?></td>
                        <td><span class="badge"><?= esc(role_label($u['role'])) ?></span></td>
                        <td><span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= esc($u['status']) ?></span></td>
                        <td>
                            <?php if ($u['role'] === 'reader'): ?>
                                <form method="POST" action="index.php?page=admin&action=order_limit&id=<?= (int)$u['id'] ?>" class="inline-form">
                                    <?php csrf_field(); ?>
                                    <input type="number" name="order_limit" min="0" step="0.01" value="<?= esc($u['order_limit']) ?>" class="price-input">
                                    <button type="submit" class="btn btn-small">Set</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php if ($u['status'] === 'active'): ?>
                                <a class="btn btn-small" href="<?= csrf_url('index.php?page=admin&action=status&id=' . (int)$u['id'] . '&to=suspended') ?>">Suspend</a>
                            <?php else: ?>
                                <a class="btn btn-small" href="<?= csrf_url('index.php?page=admin&action=status&id=' . (int)$u['id'] . '&to=active') ?>">Activate</a>
                            <?php endif; ?>
                            <a class="btn btn-small btn-danger"
                               href="<?= csrf_url('index.php?page=admin&action=delete_user&id=' . (int)$u['id']) ?>"
                               onclick="return confirm('Delete this account?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="empty">No accounts found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- FEATURE 2: feedback CRUD -->
<section class="panel">
    <h2><?= $editing ? 'Edit feedback' : 'Give feedback to an author' ?></h2>
    <form method="POST"
          action="index.php?page=admin&action=<?= $editing ? 'feedback_update&id=' . (int)$editing['id'] : 'feedback_add' ?>"
          class="form" novalidate onsubmit="return validateForm(this);">
        <?php csrf_field(); ?>
        <?php if (!$editing): ?>
            <div class="field">
                <label for="author_id">Author</label>
                <select id="author_id" name="author_id" data-label="Author" required>
                    <option value="">Choose an author</option>
                    <?php foreach ($authors as $a): ?>
                        <option value="<?= (int)$a['id'] ?>"><?= esc($a['name']) ?> (<?= esc($a['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="article_id">Article ID (optional)</label>
                <input type="number" id="article_id" name="article_id" min="0" placeholder="Leave blank for general feedback">
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="3" data-label="Message" required><?= esc($editing['message'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save changes' : 'Send feedback' ?></button>
        <?php if ($editing): ?>
            <a href="index.php?page=admin" class="btn btn-outline">Cancel</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Author</th><th>Article</th><th>Message</th><th>Sent</th><th></th></tr></thead>
            <tbody id="feedbackBody">
                <?php foreach ($feedbackList as $f): ?>
                    <tr>
                        <td><?= esc($f['author_name']) ?></td>
                        <td><?= $f['article_title'] ? esc($f['article_title']) : 'General' ?></td>
                        <td><?= esc($f['message']) ?></td>
                        <td><?= nice_date($f['created_at']) ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="index.php?page=admin&action=feedback_edit&id=<?= (int)$f['id'] ?>">Edit</a>
                            <a class="btn btn-small btn-danger"
                               href="<?= csrf_url('index.php?page=admin&action=feedback_delete&id=' . (int)$f['id']) ?>"
                               onclick="return confirm('Delete this feedback?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($feedbackList)): ?>
                    <tr><td colspan="5" class="empty">No feedback sent yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- FEATURE 3: sold books + profit/loss -->
<section class="panel">
    <div class="panel-head">
        <h2>Sold books &amp; profit/loss</h2>
        <input type="text" id="salesSearch" class="search-box" placeholder="Search sales&hellip;">
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Author</th><th>Reader</th><th>Amount</th><th>Sold</th></tr></thead>
            <tbody id="salesBody">
                <?php foreach ($soldBooks as $s): ?>
                    <tr>
                        <td><?= esc($s['title']) ?></td>
                        <td><?= esc($s['author_name']) ?></td>
                        <td><?= esc($s['reader_name']) ?></td>
                        <td><?= money($s['amount']) ?></td>
                        <td><?= nice_date($s['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($soldBooks)): ?>
                    <tr><td colspan="5" class="empty">No sales yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Activity log -->
<section class="panel">
    <div class="panel-head">
        <h2>Activity log</h2>
        <input type="text" id="logSearch" class="search-box" placeholder="Search log&hellip;">
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>User</th><th>Role</th><th>Action</th><th>When</th></tr></thead>
            <tbody id="logsBody">
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= esc($l['username']) ?></td>
                        <td><span class="badge"><?= esc($l['role']) ?></span></td>
                        <td><?= esc($l['action']) ?></td>
                        <td><?= nice_date($l['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="empty">Nothing logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
function reloadUsers() {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_users&role=' + encodeURIComponent(document.getElementById('roleFilter').value)
             + '&q=' + encodeURIComponent(document.getElementById('userSearch').value),
        tbody: 'usersBody', columns: 6, word: 'accounts',
        row: function (u) {
            var statusBadge = u.status === 'active' ? 'badge badge-success' : 'badge badge-danger';
            var toggle = u.status === 'active'
                ? '<a class="btn btn-small" href="index.php?page=admin&action=status&id=' + u.id + '&to=suspended">Suspend</a>'
                : '<a class="btn btn-small" href="index.php?page=admin&action=status&id=' + u.id + '&to=active">Activate</a>';
            var limitCell = u.role === 'reader' ? '$' + parseFloat(u.order_limit).toFixed(2) : '&mdash;';
            return '<tr><td>' + esc(u.name) + '</td><td>' + esc(u.username) + '</td>' +
                   '<td><span class="badge">' + esc(u.role) + '</span></td>' +
                   '<td><span class="' + statusBadge + '">' + esc(u.status) + '</span></td>' +
                   '<td>' + limitCell + '</td><td class="actions">' + toggle + '</td></tr>';
        }
    });
}
liveSearch('userSearch', reloadUsers);
document.getElementById('roleFilter').addEventListener('change', reloadUsers);

liveSearch('feedbackSearch', function () {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_feedback&q=' + encodeURIComponent(document.getElementById('feedbackSearch') ? document.getElementById('feedbackSearch').value : ''),
        tbody: 'feedbackBody', columns: 5, word: 'feedback',
        row: function (f) {
            return '<tr><td>' + esc(f.author_name) + '</td><td>' + (f.article_title ? esc(f.article_title) : 'General') +
                   '</td><td>' + esc(f.message) + '</td><td>' + esc(f.created_at) + '</td><td></td></tr>';
        }
    });
});

liveSearch('salesSearch', function () {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_sales&q=' + encodeURIComponent(document.getElementById('salesSearch').value),
        tbody: 'salesBody', columns: 5, word: 'sales',
        row: function (s) {
            return '<tr><td>' + esc(s.title) + '</td><td>' + esc(s.author_name) + '</td><td>' + esc(s.reader_name) +
                   '</td><td>$' + parseFloat(s.amount).toFixed(2) + '</td><td>' + esc(s.created_at) + '</td></tr>';
        }
    });
});

liveSearch('logSearch', function () {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_logs&q=' + encodeURIComponent(document.getElementById('logSearch').value),
        tbody: 'logsBody', columns: 4, word: 'log entries',
        row: function (l) {
            return '<tr><td>' + esc(l.username) + '</td><td><span class="badge">' + esc(l.role) + '</span></td>' +
                   '<td>' + esc(l.action) + '</td><td>' + esc(l.created_at) + '</td></tr>';
        }
    });
});

// Live stats refresh every 15s.
function refreshStats() {
    fetch('index.php?page=ajax&action=stats', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (s) {
            var cards = document.querySelectorAll('#statsRow .stat-num');
            var values = [s.total_orders, '$' + s.total_revenue.toFixed(2), '$' + s.total_cost.toFixed(2),
                           '$' + s.total_profit.toFixed(2), s.authors, s.readers, s.published, s.drafts];
            cards.forEach(function (el, i) { if (values[i] !== undefined) el.textContent = values[i]; });
        })
        .catch(function (err) { console.error('Stats refresh failed:', err); });
}
setInterval(refreshStats, 15000);
</script>
