<?php
require_once __DIR__.'/include/db.php';
require_once __DIR__.'/include/header.php';

$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);

$input_data = $_SESSION['register_input_data'] ?? [];
unset($_SESSION['register_input_data']);
?>

<h2>Register</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="actions/user-management/register.php">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" class="form-control" id="username" name="username" 
               value="<?= htmlspecialchars($input_data['username'] ?? '') ?>" required>
    </div>
    
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" class="form-control" id="email" name="email" 
               value="<?= htmlspecialchars($input_data['email'] ?? '') ?>" required>
    </div>
    
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    
    <div class="form-group">
        <label for="password_confirm">Confirm Password:</label>
        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
    </div>
    
    <button type="submit" class="btn btn-primary">Register</button>
</form>

<p class="mt-3">Already have an account? <a href="login.php">Login here</a></p>

<?php require_once __DIR__.'/include/footer.php'; ?>