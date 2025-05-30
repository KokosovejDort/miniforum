<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id < 1) {
    die("Invalid post ID.");
}

$post_id = (int)$_GET['id'];


$query = $db->prepare("
    SELECT p.*, t.is_closed, t.thread_id
    FROM forum_posts p
    JOIN forum_threads t ON p.thread_id = t.thread_id
    WHERE p.post_id = ?
");
$query->execute([$post_id]);
$post = $query->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found");
}

if ($post['author_id'] != $_SESSION['user_id'] && !$_SESSION['admin']) {
    die("You don't have permission to edit this post");
}

if ($post['is_closed'] && !$_SESSION['admin']) {
    die("Cannot edit posts in closed threads");
}

$image_query = $db->prepare("SELECT image_id, image_path FROM post_images WHERE post_id = ?");
$image_query->execute([$post_id]);
$images = $image_query->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Edit Post</h2>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<form method="post" action="actions/post/edit-post.php" enctype="multipart/form-data">
    <div class="form-group">
        <label for="content">Content:</label>
        <textarea class="form-control" id="content" name="content" rows="5" required><?= htmlspecialchars($post['content']) ?></textarea>
    </div>
    <?php if (!empty($images)): ?>
        <div class="form-group">
            <label>Current Images:</label>
            <div class="row">
                <?php foreach ($images as $image): ?>
                    <div class="col-md-4 mb-2">
                        <div class="card">
                            <img src="<?= htmlspecialchars($image['image_path']) ?>" class="card-img-top" alt="Post image">
                            <div class="card-body">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="delete_images[]" value="<?= $image['image_id'] ?>">
                                    <label class="form-check-label">Delete this image</label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="form-group">
        <label for="image">New Image (optional):</label>
        <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
        <small class="form-text text-muted">Maximum file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
    </div>
    <input type="hidden" name="post_id" value="<?= $post_id ?>">
    <input type="hidden" name="thread_id" value="<?= $post['thread_id'] ?>">
    <input type="hidden" name="last_updated" value="<?= $post['updated'] ?>">
    <?php if ($_SESSION['admin']): ?>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="force_save" name="force_save">
            <label class="form-check-label" for="force_save">Force save changes (admin only)</label>
        </div>
    <?php endif; ?>
    <?php 
        $thread_id = $post['thread_id'];
    ?>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="thread.php?id=<?= $thread_id ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once __DIR__.'/include/footer.php'; ?>