<?php
require __DIR__ . '/../config.php';
session_start();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (hash_equals(ADMIN_USER, $_POST['username'] ?? '') && hash_equals(ADMIN_PASS, $_POST['password'] ?? '')) {
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    header('Location: index.php'); exit;
  }
  $err = 'Invalid username or password';
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title><link rel="stylesheet" href="../style.css"></head><body>
<main class="card"><h1>Admin Login</h1><br>
<?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<form method="post">
  <label>Username<input name="username" required></label>
  <label>Password<input type="password" name="password" required></label>
  <button type="submit">Login</button>
</form></main></body></html>
