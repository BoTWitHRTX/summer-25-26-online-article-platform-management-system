<?php
$pageTitle   = 'Author Dashboard';
$pageHeading = 'Welcome back, ' . $me['name'];
$pageSub     = 'Write, price and publish your work.';
require __DIR__ . '/../partials/header.php';
?>

<div class="stat-row">
    <div class="stat-card"><span class="stat-num"><?= $stats['total'] ?></span><span class="stat-label">Total articles</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['published'] ?></span><span class="stat-label">Published</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['drafts'] ?></span><span class="stat-label">Drafts</span></div>
</div>

<!-- CRUD: create / edit article, with FEATURE 3 (save as draft vs publish) -->
<section class="panel">
    <h2><?= $editing ? 'Edit article' : 'Write a new article' ?></h2>
    <form method="POST"
          action="index.php?page=author&action=<?= $editing ? 'update&id=' . (int)$editing['id'] : 'add' ?>"
          class="form" novalidate onsubmit="return validateForm(this);">
        <?php csrf_field(); ?>

        <div class="field-row">
            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" data-label="Title" data-min="3"
                       value="<?= esc($editing['title'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" data-label="Category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= esc($cat) ?>" <?= ($editing['category'] ?? '') === $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="6" data-label="Content" data-min="20" required><?= esc($editing['content'] ?? '') ?></textarea>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" data-label="Price" min="0" step="0.01"
                       value="<?= esc($editing['price'] ?? '0') ?>" required>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" data-label="Status">
                    <option value="draft"     <?= ($editing['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Save as draft</option>
                    <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publish now</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save changes' : 'Save article' ?></button>
        <?php if ($editing): ?>
            <a href="index.php?page=author" class="btn btn-outline">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<!-- FEATURE 1: Compare articles -->
<section class="panel">
    <h2>Compare my articles</h2>
    <form method="POST" action="index.php?page=author&action=compare" class="form">
        <?php csrf_field(); ?>
        <p class="muted">Pick two or more of your own articles to compare price, sales, likes and rating.</p>
        <div class="checkbox-grid">
            <?php foreach ($articles as $a): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="ids[]" value="<?= (int)$a['id'] ?>"
                        <?= in_array($a['id'], array_column($compareRows, 'id')) ? 'checked' : '' ?>>
                    <?= esc($a['title']) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-outline">Compare selected</button>
    </form>

    <?php if (!empty($compareRows)): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Status</th><th>Price</th><th>Likes</th><th>Sales</th><th>Revenue</th><th>Avg rating</th></tr></thead>
                <tbody>
                    <?php foreach ($compareRows as $r): ?>
                        <tr>
                            <td><?= esc($r['title']) ?></td>
                            <td><span class="badge"><?= esc($r['status']) ?></span></td>
                            <td><?= money($r['price']) ?></td>
                            <td><?= (int)$r['like_count'] ?></td>
                            <td><?= (int)$r['sales_count'] ?></td>
                            <td><?= money($r['revenue']) ?></td>
                            <td><?= $r['avg_rating'] ? esc($r['avg_rating']) . ' / 5' : '&mdash;' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- CRUD table: my articles, with FEATURE 2 (quick set-price) -->
<section class="panel">
    <div class="panel-head">
        <h2>My articles</h2>
        <input type="text" id="articleSearch" class="search-box" placeholder="Search my articles&hellip;">
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Updated</th><th></th></tr></thead>
            <tbody id="articlesBody">
                <?php if (empty($articles)): ?>
                    <tr><td colspan="6" class="empty">You haven't written anything yet.</td></tr>
                <?php else: foreach ($articles as $a): ?>
                    <tr>
                        <td><?= esc($a['title']) ?></td>
                        <td><span class="badge"><?= esc($a['category']) ?></span></td>
                        <td><span class="badge <?= $a['status'] === 'published' ? 'badge-success' : '' ?>"><?= esc($a['status']) ?></span></td>
                        <td>
                            <form method="POST" action="index.php?page=author&action=price&id=<?= (int)$a['id'] ?>" class="inline-form">
                                <?php csrf_field(); ?>
                                <input type="number" name="price" min="0" step="0.01" value="<?= esc($a['price']) ?>" class="price-input">
                                <button type="submit" class="btn btn-small">Set</button>
                            </form>
                        </td>
                        <td><?= nice_date($a['updated_at']) ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="index.php?page=author&action=edit&id=<?= (int)$a['id'] ?>">Edit</a>
                            <a class="btn btn-small btn-danger"
                               href="<?= csrf_url('index.php?page=author&action=delete&id=' . (int)$a['id']) ?>"
                               onclick="return confirm('Delete this article?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Feedback received from admin (read-only) -->
<section class="panel">
    <h2>Feedback from admin</h2>
    <ul class="comment-list">
        <?php foreach ($myFeedback as $f): ?>
            <li class="comment-item">
                <div class="comment-head">
                    <strong><?= $f['article_title'] ? esc($f['article_title']) : 'General' ?></strong>
                    <span class="muted"><?= nice_date($f['created_at']) ?></span>
                </div>
                <p><?= esc($f['message']) ?></p>
            </li>
        <?php endforeach; ?>
        <?php if (empty($myFeedback)): ?>
            <li class="empty">No feedback yet.</li>
        <?php endif; ?>
    </ul>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
liveSearch('articleSearch', function () {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_my_articles&q=' + encodeURIComponent(document.getElementById('articleSearch').value),
        tbody: 'articlesBody', columns: 6, word: 'articles',
        row: function (a) {
            var badge = a.status === 'published' ? 'badge badge-success' : 'badge';
            return '<tr><td>' + esc(a.title) + '</td><td><span class="badge">' + esc(a.category) + '</span></td>' +
                   '<td><span class="' + badge + '">' + esc(a.status) + '</span></td>' +
                   '<td>$' + parseFloat(a.price).toFixed(2) + '</td><td>' + esc(a.updated_at) + '</td>' +
                   '<td class="actions"><a class="btn btn-small" href="index.php?page=author&action=edit&id=' + a.id + '">Edit</a></td></tr>';
        }
    });
});
</script>
