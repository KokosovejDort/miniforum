<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$query = $db->prepare("
    SELECT username, email 
    FROM forum_users 
    WHERE user_id = ?
");
$query->execute([$user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

$query = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM forum_threads WHERE author_id = ?) AS thread_count,
        (SELECT COUNT(*) FROM forum_posts WHERE author_id = ?) AS post_count,
        (SELECT COALESCE(SUM(vote_type), 0) FROM forum_posts_votes 
         JOIN forum_posts ON forum_posts_votes.post_id = forum_posts.post_id
         WHERE forum_posts.author_id = ?) AS total_votes
");
$query->execute([$user_id, $user_id, $user_id]);
$stats = $query->fetch(PDO::FETCH_ASSOC);

$query = $db->prepare("
    SELECT t.*, c.name AS category_name, 
           (SELECT COUNT(*) FROM forum_posts WHERE thread_id = t.thread_id) AS reply_count
    FROM forum_threads t
    JOIN forum_categories c ON t.category_id = c.category_id
    WHERE t.author_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$query->execute([$user_id]);
$threads = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("
    SELECT p.*, t.title AS thread_title
    FROM forum_posts p 
    JOIN forum_threads t ON p.thread_id = t.thread_id
    WHERE p.author_id = ? AND (t.author_id != ? OR p.post_id NOT IN 
        (SELECT MIN(post_id) FROM forum_posts WHERE thread_id = p.thread_id))
    ORDER BY p.updated DESC
    LIMIT 5
");
$query->execute([$user_id, $user_id]);
$posts = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Profile Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Statistics</h3>
            </div>
            <div class="card-body">
                <p><strong>Threads Created:</strong> <?= (int)$stats['thread_count'] ?></p>
                <p><strong>Posts Written:</strong> <?= (int)$stats['post_count'] ?></p>
                <p><strong>Total Upvotes Received:</strong> <?= (int)$stats['total_votes'] ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Your Threads</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($threads)): ?>
                    <ul class="list-group">
                        <?php foreach ($threads as $thread): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="thread.php?id=<?= $thread['thread_id'] ?>">
                                            <?= htmlspecialchars($thread['title']) ?>
                                        </a>
                                        <small class="text-muted"> in <?= htmlspecialchars($thread['category_name']) ?></small>
                                    </div>
                                    <span class="badge badge-primary badge-pill"><?= $thread['reply_count'] ?> replies</span>
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted">Created: <?= htmlspecialchars(date('M j, Y', strtotime($thread['created_at']))) ?></small>
                                    <?php if ($thread['is_closed']): ?>
                                        <span class="badge badge-secondary">Closed</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($stats['thread_count'] > 5): ?>
                        <div class="mt-2">
                            <a href="profile-threads.php">View all threads</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>You haven't created any threads yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Your Recent Posts</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($posts)): ?>
                    <ul class="list-group">
                        <?php foreach ($posts as $post): ?>
                            <li class="list-group-item">
                                <a href="thread.php?id=<?= $post['thread_id'] ?>">
                                    <?= htmlspecialchars($post['thread_title']) ?>
                                </a>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($post['content'], 0, 100)) ?>
                                        <?= (strlen($post['content']) > 100) ? '...' : '' ?>
                                    </small>
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted">Posted: <?= htmlspecialchars(date('M j, Y', strtotime($post['updated']))) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($stats['post_count'] > 5): ?>
                        <div class="mt-2">
                            <a href="profile-posts.php">View all posts</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>You haven't replied to any threads yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__.'/include/footer.php'; ?>