<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	die("Invalid thread ID");
}
$thread_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? null;

$query = $db->prepare("
    SELECT t.*, u.username AS author_name, c.name AS category_name
    FROM forum_threads t
    JOIN forum_users u ON t.author_id = u.user_id
    JOIN forum_categories c ON t.category_id = c.category_id
    WHERE t.thread_id = ?
");
$query->execute([$thread_id]);
$thread = $query->fetch(PDO::FETCH_ASSOC);
if (!$thread) {
	die("Thread not found");
}

$query_posts = $db->prepare("
    SELECT 
        p.*, 
        u.username AS author_name
    FROM forum_posts p
    LEFT JOIN forum_users u ON p.author_id = u.user_id
    WHERE p.thread_id = :thread_id
    ORDER BY p.updated ASC
");

$query_posts->execute([
	'thread_id' => $thread_id
]);
$posts = $query_posts->fetchAll(PDO::FETCH_ASSOC);

$post_ids = array_column($posts, 'post_id');
if (!empty($post_ids)) {
	$placeholders = implode(',', array_fill(0, count($post_ids), '?'));
	$query_votes = $db->prepare("
		SELECT post_id, SUM(vote_type) AS votes
		FROM forum_posts_votes
		WHERE post_id IN ($placeholders)
		GROUP BY post_id
	");
	$query_votes->execute($post_ids);
	$votes = $query_votes->fetchAll(PDO::FETCH_KEY_PAIR);

	if(isset($_SESSION['user_id'])) {
		$query_user_votes = $db->prepare("
			SELECT post_id, vote_type
			FROM forum_posts_votes
			WHERE post_id IN ($placeholders) AND author_id = ?
		");
		$user_vote_params = array_merge($post_ids, [$_SESSION['user_id']]);
		$query_user_votes->execute($user_vote_params);
		$user_votes = $query_user_votes->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	$query_images = $db->prepare("
		SELECT post_id, GROUP_CONCAT(image_path) as images
		FROM post_images
		WHERE post_id IN ($placeholders)
		GROUP BY post_id
	");
	$query_images->execute($post_ids);
	$images = $query_images->fetchAll(PDO::FETCH_KEY_PAIR);
}

$votes = $votes ?? [];
$user_votes = $user_votes ?? [];
$images = $images ?? [];

foreach ($posts as &$post) {
	$post['votes'] = $votes[$post['post_id']] ?? 0;
	$post['user_vote'] = $user_votes[$post['post_id']] ?? 0;
	$post['images'] = $images[$post['post_id']] ?? '';
}
unset($post);

function comparePosts($post1, $post2) {
	if ($post1['votes'] > $post2['votes']) {
		return -1; 
	}
	if ($post1['votes'] < $post2['votes']) {
		return 1;  
	}
	$time1 = strtotime($post1['updated']);
	$time2 = strtotime($post2['updated']);
	if ($time1 < $time2) {
		return -1; 
	}
	if ($time1 > $time2) {
		return 1;  
	}
	return 0; 
}

usort($posts, 'comparePosts');
?>

<h2><?= htmlspecialchars($thread['title']) ?></h2>
<?php if (isset($_SESSION['user_id']) && $thread['author_id'] == $_SESSION['user_id'] && !$thread['is_closed']): ?>
    <form method="post" action="actions/thread/close-thread.php" style="margin-bottom: 10px;">
        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to close this thread? No further replies will be allowed.')">
            Close Thread
        </button>
    </form>
<?php endif; ?>
<p><strong>Author:</strong> <?= htmlspecialchars($thread['author_name']) ?>,
	<strong>Category:</strong> <?= htmlspecialchars($thread['category_name']) ?></p>
<hr>
<h3>Posts:</h3>

<?php foreach ($posts as $post): ?>
	<?php
	$upvoted = $post['user_vote'] == 1;
	$downvoted = $post['user_vote'] == -1;
	?>
	<div class="border-bottom mb-3 pb-2">
		<p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
		<?php if (!empty($post['images'])): ?>
			<div class="post-images mt-2">
				<?php foreach (explode(',', $post['images']) as $image): ?>
					<a href="<?= htmlspecialchars($image) ?>" target="_blank">
						<img src="<?= htmlspecialchars($image) ?>" alt="Post image" class="img-thumbnail" style="max-width: 200px; max-height: 200px; margin: 5px;">
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<small>
			Posted by <?= htmlspecialchars($post['author_name'] ?: "Deleted user") ?>,
			Updated: <?= htmlspecialchars($post['updated']) ?>,
			Votes: <?= $post['votes'] ?>
		</small>

		<?php if (isset($_SESSION['user_id'])): ?>
			<form method="post" action="actions/post/vote.php" style="display:inline;">
				<input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
				<input type="hidden" name="vote_type" value="1">
				<button type="submit" class="btn btn-sm <?= $upvoted ? 'btn-success' : 'btn-outline-success' ?>">👍</button>
			</form>
			<form method="post" action="actions/post/vote.php" style="display:inline;">
				<input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
				<input type="hidden" name="vote_type" value="-1">
				<button type="submit" class="btn btn-sm <?= $downvoted ? 'btn-danger' : 'btn-outline-danger' ?>">👎</button>
			</form>
			<?php if ($post['author_id'] == $_SESSION['user_id'] || $_SESSION['admin']): ?>
				<div class="mt-2">
                    <a href="edit-post.php?id=<?= $post['post_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form method="post" action="actions/post/delete-post.php" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this post?')">Delete</button>
                    </form>
                </div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endforeach; ?>

<?php if (isset($_SESSION['user_id']) && !$thread['is_closed']): ?>
	<h3>Add your reply</h3>
	<form action="actions/post/create-post.php" method="post" enctype="multipart/form-data">
		<div class="form-group">
			<textarea name="content" rows="5" required></textarea>
			<input type="hidden" name="thread_id" value="<?= $thread_id ?>">
			<div class="mt-2">
			<label for="images">Attach Images (optional):</label>
				<input type="file" class="form-control-file" id="images" name="images[]" multiple accept="image/*">
				<small class="form-text text-muted">Maximum file size: 5MB per image. Allowed formats: JPG, PNG, GIF. You can select multiple images.</small>
			</div>
		</div>
		<button type="submit" class="btn btn-primary">Submit</button>
	</form>
<?php elseif($thread['is_closed']): ?>
	<p><strong>This thread is closed.</strong></p>
<?php else: ?>
	<p>Please <a href="login.php">login</a> to reply.</p>
<?php endif; ?>

<?php require_once __DIR__.'/include/footer.php'; ?>
