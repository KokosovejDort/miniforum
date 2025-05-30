<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

$search_type = $_GET['type'] ?? 'threads';
$search_query = $_GET['q'] ?? '';
$results = [];

if (!empty($search_query)) {
    if ($search_type === 'threads') {
        $query = $db->prepare("
            SELECT t.*, u.username AS author_name, c.name AS category_name,
                   (SELECT COUNT(*) FROM forum_posts WHERE thread_id = t.thread_id) AS post_count
            FROM forum_threads t
            JOIN forum_users u ON t.author_id = u.user_id
            JOIN forum_categories c ON t.category_id = c.category_id
            WHERE t.title LIKE ?
            ORDER BY t.created_at DESC
        ");
        $search_term = "%{$search_query}%";
        $query->execute([$search_term]);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($search_type === 'users') {
        $query = $db->prepare("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM forum_threads WHERE author_id = u.user_id) AS thread_count,
                   (SELECT COUNT(*) FROM forum_posts WHERE author_id = u.user_id) AS post_count
            FROM forum_users u
            WHERE u.username LIKE ? OR u.email LIKE ?
            ORDER BY u.username ASC
        ");
        $search_term = "%{$search_query}%";
        $query->execute([$search_term, $search_term]);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="container mt-4">
    <?php if (!empty($search_query)): ?>
        <h3>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h3>
        
        <?php if (empty($results)): ?>
            <p>No results found.</p>
        <?php else: ?>
            <?php if ($search_type === 'threads'): ?>
                <div class="list-group">
                    <?php foreach ($results as $thread): ?>
                        <a href="thread.php?id=<?php echo $thread['thread_id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($thread['title']); ?></h5>
                                <small><?php echo date('M d, Y', strtotime($thread['created_at'])); ?></small>
                            </div>
                            <small>
                                Category: <?php echo htmlspecialchars($thread['category_name']); ?> |
                                Author: <?php echo htmlspecialchars($thread['author_name']); ?> |
                                Posts: <?php echo $thread['post_count']; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($search_type === 'users'): ?>
                <div class="list-group">
                    <?php foreach ($results as $user): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                            </div>
                            <small>
                                Threads: <?php echo $user['thread_count']; ?> |
                                Posts: <?php echo $user['post_count']; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__.'/include/footer.php'; ?>
    
