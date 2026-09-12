<?php
$pageTitle   = 'Reader Dashboard';
$pageHeading = 'Welcome back, ' . $me['name'];
$pageSub     = 'Read & search articles, like & comment, and rate what you read.';
require __DIR__ . '/../partials/header.php';
?>

<div class="stat-row">
    <div class="stat-card"><span class="stat-num"><?= count($articles) ?></span><span class="stat-label">Published articles</span></div>
    <div class="stat-card"><span class="stat-num"><?= count($myComments) ?></span><span class="stat-label">My comments</span></div>
    <div class="stat-card"><span class="stat-num"><?= count($myOrders) ?></span><span class="stat-label">My orders</span></div>
    <div class="stat-card"><span class="stat-num"><?= money($me['order_limit']) ?></span><span class="stat-label">My order limit</span></div>
</div>

<!-- FEATURE 1: Read & Search Articles -->
<section class="panel">
    <div class="panel-head">
        <h2>Read &amp; search articles</h2>
        <input type="text" id="articleSearch" class="search-box" placeholder="Search by title, category or author&hellip;">
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Title</th><th>Author</th><th>Category</th><th>Price</th><th>Likes</th><th>Rating</th><th></th></tr>
            </thead>
            <tbody id="articlesBody">
                <?php if (empty($articles)): ?>
                    <tr><td colspan="7" class="empty">No published articles yet.</td></tr>
                <?php else: foreach ($articles as $a): ?>
                    <tr>
                        <td><?= esc($a['title']) ?></td>
                        <td><?= esc($a['author_name']) ?></td>
                        <td><span class="badge"><?= esc($a['category']) ?></span></td>
                        <td><?= money($a['price']) ?></td>
                        <td><?= (int)$a['like_count'] ?></td>
                        <td><?= $a['avg_rating'] ? esc($a['avg_rating']) . ' / 5' : '&mdash;' ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="index.php?page=reader&action=view&id=<?= (int)$a['id'] ?>">Open</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- CRUD: My comments -->
<section class="panel">
    <div class="panel-head">
        <h2>My comments</h2>
        <input type="text" id="commentSearch" class="search-box" placeholder="Search your comments&hellip;">
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Article</th><th>Comment</th><th>Posted</th><th></th></tr></thead>
            <tbody id="commentsBody">
                <?php if (empty($myComments)): ?>
                    <tr><td colspan="4" class="empty">You haven't commented yet.</td></tr>
                <?php else: foreach ($myComments as $c): ?>
                    <tr>
                        <td><?= esc($c['article_title']) ?></td>
                        <td><?= esc($c['comment']) ?></td>
                        <td><?= nice_date($c['created_at']) ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="index.php?page=reader&action=view&id=<?= (int)$c['article_id'] ?>">View</a>
                            <a class="btn btn-small btn-danger"
                               href="<?= csrf_url('index.php?page=reader&action=comment_delete&id=' . (int)$c['id']) ?>"
                               onclick="return confirm('Delete this comment?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- My orders -->
<section class="panel">
    <h2>My orders</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Article</th><th>Amount</th><th>Ordered</th></tr></thead>
            <tbody>
                <?php if (empty($myOrders)): ?>
                    <tr><td colspan="3" class="empty">You haven't ordered anything yet.</td></tr>
                <?php else: foreach ($myOrders as $o): ?>
                    <tr>
                        <td><?= esc($o['title']) ?></td>
                        <td><?= money($o['amount']) ?></td>
                        <td><?= nice_date($o['created_at']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
liveSearch('articleSearch', function () {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_articles&q=' + encodeURIComponent(document.getElementById('articleSearch').value),
        tbody: 'articlesBody', columns: 7, word: 'articles',
        row: function (a) {
            return '<tr><td>' + esc(a.title) + '</td><td>' + esc(a.author_name) + '</td>' +
                   '<td><span class="badge">' + esc(a.category) + '</span></td>' +
                   '<td>$' + parseFloat(a.price).toFixed(2) + '</td><td>' + a.like_count + '</td>' +
                   '<td>' + (a.avg_rating ? a.avg_rating + ' / 5' : '&mdash;') + '</td>' +
                   '<td class="actions"><a class="btn btn-small" href="index.php?page=reader&action=view&id=' + a.id + '">Open</a></td></tr>';
        }
    });
});

liveSearch('commentSearch', function () {
    ajaxTable({
        url: 'index.php?page=ajax&action=search_comments&q=' + encodeURIComponent(document.getElementById('commentSearch').value),
        tbody: 'commentsBody', columns: 4, word: 'comments',
        row: function (c) {
            return '<tr><td>' + esc(c.article_title) + '</td><td>' + esc(c.comment) + '</td><td>' + esc(c.created_at) + '</td>' +
                   '<td class="actions"><a class="btn btn-small" href="index.php?page=reader&action=view&id=' + c.article_id + '">View</a></td></tr>';
        }
    });
});
</script>
