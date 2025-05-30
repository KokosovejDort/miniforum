<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$username = $_SESSION['login_username'] ?? '';
unset($_SESSION['login_username']);

?>

<h2>Login</h2>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="actions/user-management/login.php">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" class="form-control" id="username" name="username" 
               value="<?= htmlspecialchars($username) ?>" required>
    </div>
    
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    
    <button type="submit" class="btn btn-primary">Login</button>
</form>

<p class="mt-3">Don't have an account? <a href="register.php">Register here</a></p>

<p class="mt-3"> <a href="actions/user-management/reset/request-reset.php">Forgot your password?</a></p>

<?php require_once __DIR__.'/include/footer.php'; ?>