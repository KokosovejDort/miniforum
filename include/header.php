<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?php echo (!empty($pageTitle)?$pageTitle.' - ':'')?>Forum</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous">
  </head>
  <body>
    <header class="bg-dark">
      <div class="container">
        <h1 class="text-white py-4 px-2">Forum</h1>
      </div>
    </header>
    <div class="container">
        <nav class="mb-3">
            <a href="/~dudt05/semestralka/index.php">Home</a> |
            <a href="/~dudt05/semestralka/create-thread.php">New Thread</a> |
            <a href="/~dudt05/semestralka/profile.php">My Profile</a> |
			    <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/~dudt05/semestralka/actions/user-management/logout.php">Logout</a>
				    <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                | <a href="/~dudt05/semestralka/admin.php">Admin</a>
            <?php endif; ?>
			    <?php else: ?>
              <a href="/~dudt05/semestralka/login.php">Login</a> | <a href="/~dudt05/semestralka/register.php">Register</a>
			    <?php endif; ?>
        </nav>
        
        <form method="GET" action="/~dudt05/semestralka/search.php" class="form-inline mb-3">
            <input type="text" name="q" class="form-control mr-2" placeholder="Search...">
            <select name="type" class="form-control mr-2">
                <option value="threads">Threads</option>
                <option value="users">Users</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
