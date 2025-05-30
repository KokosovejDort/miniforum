<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

if (!isset($_SESSION["user_id"]) || !$_SESSION['admin'] || !isset($_SESSION["admin"])) {
    header("Location: index.php");
    exit();
}

$query = $db -> query("
    SELECT user_id, username, email, admin
    FROM forum_users
    ORDER BY user_id
");
$users = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->query("
    SELECT c.*, COUNT(t.thread_id) AS thread_count
    FROM forum_categories c
    LEFT JOIN forum_threads t ON c.category_id = t.category_id
    GROUP BY c.category_id
    ORDER BY c.category_id
");
$categories = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Admin Panel</h1>

<h2>Thread Management</h2>
<table border="1" cellpadding="5" style="width: 100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $query = $db->query("
        SELECT t.*, u.username AS author_name, c.name AS category_name 
        FROM forum_threads t
        JOIN forum_users u ON t.author_id = u.user_id
        JOIN forum_categories c ON t.category_id = c.category_id
        ORDER BY t.thread_id
    ");
    $threads = $query->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php foreach ($threads as $thread): ?>
            <tr>
                <td><?= $thread['thread_id'] ?></td>
                <td>
                    <a href="thread.php?id=<?= $thread['thread_id'] ?>">
                        <?= htmlspecialchars($thread['title']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($thread['author_name']) ?></td>
                <td><?= htmlspecialchars($thread['category_name']) ?></td>
                <td><?= $thread['is_closed'] ? 'Closed' : 'Open' ?></td>
                <td>
                    <form method="post" action="actions/thread/move-thread.php" style="display:inline;">
                        <input type="hidden" name="thread_id" value="<?= $thread['thread_id'] ?>">
                        <select name="new_category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>"
                                    <?= $category['category_id'] == $thread['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-info">Move</button>
                    </form>
                    <form method="post" action="actions/thread/delete-thread.php" style="display:inline;">
                        <input type="hidden" name="thread_id" value="<?= $thread['thread_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this thread? This will delete all posts in the thread and cannot be undone.')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<h2>User Management</h2>
<table border="1" cellpadding="5" style="width: 100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Admin</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['user_id'] ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['admin'] ? 'Yes' : 'No' ?></td>
                <td>
                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                        <form method="post" action="actions/user-management/toggle-admin.php">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to <?= $user['admin'] ? 'remove' : 'grant' ?> admin privileges?')">
                                <?= $user['admin'] ? 'Remove Admin' : 'Make Admin' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        (current user)
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Category Management</h2>
<form method="post" action="actions/category/add-category.php" style="margin-bottom: 20px;">
    <label for="categoryName">New Category Name:</label>
    <input type="text" id="categoryName" name="name" required>
    <button type="submit">Add Category</button>
</form>

<table border="1" cellpadding="5" style="width: 100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Threads</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= $category['category_id'] ?></td>
                <td><?= htmlspecialchars($category['name']) ?></td>
                <td><?= $category['thread_count'] ?></td>
                <td>
                    <form method="post" action="actions/category/edit-category.php" style="display: inline;">
                        <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                        <button type="submit">Update</button>
                    </form>
                    <?php if ($category['thread_count'] == 0): ?>
                        <form method="post" action="actions/category/delete-category.php" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this category?')">
                                Delete
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__.'/include/footer.php'; ?>
