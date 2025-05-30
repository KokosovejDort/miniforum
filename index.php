<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';


$query = $db->query("SELECT t.*, u.username, c.name AS category_name
                     FROM forum_threads t
                     JOIN forum_users u ON t.author_id = u.user_id
                     JOIN forum_categories c ON t.category_id = c.category_id");

$threads = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Discussion Threads</h1>

<ul>
	<?php foreach ($threads as $thread): ?>
		<li>
			<a href="thread.php?id=<?= $thread['thread_id'] ?>">
				<?= htmlspecialchars($thread['title']) ?>
			</a>
			— <?= htmlspecialchars($thread['username']) ?> in <?= htmlspecialchars($thread['category_name']) ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php require_once __DIR__.'/include/footer.php'; ?>
