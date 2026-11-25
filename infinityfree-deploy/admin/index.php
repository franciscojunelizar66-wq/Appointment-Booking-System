<?php
require __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Bad token');
  $id = (int)($_POST['id'] ?? 0);
  $action = $_POST['action'] ?? '';
  if ($action === 'delete') {
    db()->prepare('DELETE FROM appointments WHERE id=?')->execute([$id]);
  } elseif (in_array($action, ['confirmed', 'cancelled'], true)) {
    db()->prepare('UPDATE appointments SET status=? WHERE id=?')->execute([$action, $id]);
  }
  header('Location: index.php'); exit;
}
$filter = $_GET['status'] ?? '';
$sql = 'SELECT * FROM appointments';
$params = [];
if (in_array($filter, ['pending','confirmed','cancelled'], true)) { $sql .= ' WHERE status=?'; $params[] = $filter; }
$sql .= ' ORDER BY appt_date, appt_time';
$st = db()->prepare($sql); $st->execute($params);
$rows = $st->fetchAll();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin - Appointments</title><link rel="stylesheet" href="../style.css"></head><body>
<main class="card wide">
  <div class="top"><h1>Appointments</h1> <a href="form.php" style="font-weight:700">+ Add Booking</a>
    <div><a href="?">All</a> | <a href="?status=pending">Pending</a> | <a href="?status=confirmed">Confirmed</a> | <a href="?status=cancelled">Cancelled</a> | <a href="../bookings.html">Public list</a> | <a href="logout.php">Logout</a></div>
  </div>
  <div class="tablewrap"><table>
    <tr><th>#</th><th>Client</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><strong><?= $h($r['name']) ?></strong><br><?= $h($r['email']) ?><br><?= $h($r['phone']) ?>
        <?php if ($r['notes']): ?><br><small><?= $h($r['notes']) ?></small><?php endif; ?></td>
      <td><?= $h($r['service']) ?></td>
      <td><?= $h($r['appt_date']) ?></td>
      <td><?= date('g:i A', strtotime($r['appt_time'])) ?></td>
      <td><span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span></td>
      <td class="act">
        <form method="post" style="display:inline">
          <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <a href="form.php?id=<?= $r['id'] ?>" style="margin-right:4px">Edit</a>
          <button class="c" name="action" value="confirmed">Confirm</button>
          <button class="x" name="action" value="cancelled">Cancel</button>
          <button class="d" name="action" value="delete" onclick="return confirm('Delete permanently?')">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; if (!$rows) echo '<tr><td colspan="7">No appointments found.</td></tr>'; ?>
  </table></div>
</main></body></html>
