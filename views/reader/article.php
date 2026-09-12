<?php
$pageTitle   = $article['title'];
$pageHeading = $article['title'];
$articleAuthor = get_user($conn, $article['author_id']);
$pageSub     = 'by ' . $articleAuthor['name'] . ' - ' . $article['category'];
require __DIR__ . '/../partials/header.php';
?>

<section class="panel">
    <?php if ($canReadFull): ?>
        <div class="article-body"><?= nl2br(esc($article['content'])) ?></div>
    <?php else: ?>
        <div class="article-body article-body-locked">
            <?= nl2br(esc(substr($article['content'], 0, 220))) ?>&hellip;
        </div>
        <div class="lock-notice">
            &#128274; Order this article for <?= money($article['price']) ?> to read the rest.
        </div>
    <?php endif; ?>

    <div class="article-meta-row">
        <span class="price-tag"><?= money($article['price']) ?></span>

        <!-- FEATURE 2: Like -->
        <a class="btn <?= $iLiked ? 'btn-primary' : 'btn-outline' ?>"
           href="<?= csrf_url('index.php?page=reader&action=like&id=' . (int)$article['id']) ?>">
            <?= $iLiked ? '&#10084; Liked' : '&#9825; Like' ?>
        </a>

        <?php if ($iOrdered): ?>
            <span class="badge badge-success">Ordered</span>
        <?php else: ?>
            <a class="btn <?= $canReadFull ? 'btn-outline' : 'btn-primary' ?>"
               href="<?= csrf_url('index.php?page=reader&action=order&id=' . (int)$article['id']) ?>"
               onclick="return confirm('Order this for <?= esc(money($article['price'])) ?>?');">Order</a>
        <?php endif; ?>
    </div>

    <!-- FEATURE 3: Rating -->
    <form method="POST" action="index.php?page=reader&action=rate" class="rating-form">
        <?php csrf_field(); ?>
        <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
        <label>Your rating:</label>
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <label class="star-radio">
                <input type="radio" name="rating" value="<?= $i ?>" <?= $myRating === $i ? 'checked' : '' ?> onchange="this.form.submit()">
                <?= $i ?>&#9733;
            </label>
        <?php endfor; ?>
    </form>
</section>

<!-- FEATURE 2: Comment (CRUD) -->
<section class="panel">
    <h2>Comments (<?= count($comments) ?>)</h2>

    <form method="POST" action="index.php?page=reader&action=comment_add" class="form"
          novalidate onsubmit="return validateForm(this);">
        <?php csrf_field(); ?>
        <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
        <div class="field">
            <textarea name="comment" rows="3" data-label="Comment" placeholder="Share your thoughts&hellip;" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Post comment</button>
    </form>

    <ul class="comment-list">
        <?php foreach ($comments as $c): ?>
            <li class="comment-item">
                <?php if ($c['reader_id'] == $readerId && $editingComment && $editingComment['id'] == $c['id']): ?>
                    <form method="POST" action="index.php?page=reader&action=comment_update&id=<?= (int)$c['id'] ?>" class="form">
                        <?php csrf_field(); ?>
                        <textarea name="comment" rows="2" required><?= esc($editingComment['comment']) ?></textarea>
                        <button type="submit" class="btn btn-small btn-primary">Save</button>
                    </form>
                <?php else: ?>
                    <div class="comment-head">
                        <strong><?= esc($c['reader_name']) ?></strong>
                        <span class="muted"><?= nice_date($c['created_at']) ?></span>
                    </div>
                    <p><?= esc($c['comment']) ?></p>
                    <?php if ($c['reader_id'] == $readerId): ?>
                        <div class="comment-actions">
                            <a class="link-muted" href="index.php?page=reader&action=view&id=<?= (int)$article['id'] ?>&edit=<?= (int)$c['id'] ?>">Edit</a>
                            <a class="link-danger"
                               href="<?= csrf_url('index.php?page=reader&action=comment_delete&id=' . (int)$c['id']) ?>"
                               onclick="return confirm('Delete this comment?');">Delete</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if (empty($comments)): ?>
            <li class="empty">No comments yet. Be the first!</li>
        <?php endif; ?>
    </ul>
</section>

<p><a href="index.php?page=reader" class="link-muted">&larr; Back to all articles</a></p>

<?php require __DIR__ . '/../partials/footer.php'; ?>
