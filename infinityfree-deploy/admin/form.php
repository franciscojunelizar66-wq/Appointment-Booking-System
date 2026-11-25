<?php
// Admin: add a new booking or edit an existing one (?id=123)
require __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = ['name'=>'','email'=>'','phone'=>'','service'=>SERVICES[0],'appt_date'=>date('Y-m-d'),'appt_time'=>'09:00','notes'=>'','status'=>'confirmed'];

if ($id) {
  $st = db()->prepare("SELECT *, TIME_FORMAT(appt_time,'%H:%i') AS appt_time FROM appointments WHERE id=?");
  $st->execute([$id]);
  $found = $st->fetch();
  if (!$found) die('Booking not found');
  $row = $found;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Bad token');
  $row = [
    'name' => trim($_POST['name'] ?? ''), 'email' => trim($_POST['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''), 'service' => $_POST['service'] ?? '',
    'appt_date' => $_POST['appt_date'] ?? '', 'appt_time' => $_POST['appt_time'] ?? '',
    'notes' => substr(trim($_POST['notes'] ?? ''), 0, 255), 'status' => $_POST['status'] ?? 'pending',
  ];
  $d = DateTime::createFromFormat('Y-m-d', $row['appt_date']);
  if ($row['name'] === '' || strlen($row['name']) > 100) $err = 'Name is required.';
  elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $err = 'Invalid email.';
  elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $row['phone'])) $err = 'Invalid phone.';
  elseif (!in_array($row['service'], SERVICES, true)) $err = 'Invalid service.';
  elseif (!$d || $d->format('Y-m-d') !== $row['appt_date']) $err = 'Invalid date.';
  elseif (!in_array($row['appt_time'], SLOTS, true)) $err = 'Invalid time slot.';
  elseif (!in_array($row['status'], ['pending','confirmed','cancelled'], true)) $err = 'Invalid status.';

  if (!$err) {
    $pdo = db();
    try {
      $pdo->beginTransaction();
      if ($row['status'] !== 'cancelled') {
        // Double-booking check (ignore this booking itself when editing)
        $chk = $pdo->prepare("SELECT id FROM appointments WHERE appt_date=? AND appt_time=? AND status<>'cancelled' AND id<>? FOR UPDATE");
        $chk->execute([$row['appt_date'], $row['appt_time'], $id]);
        if ($chk->fetch()) throw new RuntimeException('That date and time is already booked.');
      }
      $vals = [$row['name'],$row['email'],$row['phone'],$row['service'],$row['appt_date'],$row['appt_time'],$row['notes'],$row['status']];
      if ($id) {
        $pdo->prepare("UPDATE appointments SET name=?,email=?,phone=?,service=?,appt_date=?,appt_time=?,notes=?,status=? WHERE id=?")
            ->execute([...$vals, $id]);
      } else {
        $pdo->prepare("INSERT INTO appointments (name,email,phone,service,appt_date,appt_time,notes,status) VALUES (?,?,?,?,?,?,?,?)")
            ->execute($vals);
      }
      $pdo->commit();
      header('Location: index.php'); exit;
    } catch (RuntimeException $e) {
      $pdo->rollBack(); $err = $e->getMessage();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack(); $err = 'Server error.';
    }
  }
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id ? 'Edit' : 'Add' ?> Booking</title><link rel="stylesheet" href="../style.css"></head><body>
<main class="card">
  <div class="top"><h1><?= $id ? "Edit Booking #$id" : 'Add Booking' ?></h1><a href="index.php">&larr; Back</a></div>
  <?php if ($err): ?><div class="msg err"><?= $h($err) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <label>Full Name<input name="name" required maxlength="100" value="<?= $h($row['name']) ?>"></label>
    <label>Email<input type="email" name="email" required value="<?= $h($row['email']) ?>"></label>
    <label>Phone<input name="phone" required value="<?= $h($row['phone']) ?>"></label>
    <label>Service<select name="service">
      <?php foreach (SERVICES as $s): ?><option <?= $row['service'] === $s ? 'selected' : '' ?>><?= $h($s) ?></option><?php endforeach; ?>
    </select></label>
    <label>Date<input type="date" name="appt_date" required value="<?= $h($row['appt_date']) ?>"></label>
    <label>Time<select name="appt_time">
      <?php foreach (SLOTS as $t): ?><option value="<?= $t ?>" <?= $row['appt_time'] === $t ? 'selected' : '' ?>><?= date('g:i A', strtotime($t)) ?></option><?php endforeach; ?>
    </select></label>
    <label>Status<select name="status">
      <?php foreach (['pending','confirmed','cancelled'] as $s): ?><option <?= $row['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
    </select></label>
    <label>Notes<textarea name="notes" rows="3" maxlength="255"><?= $h($row['notes']) ?></textarea></label>
    <button type="submit"><?= $id ? 'Save Changes' : 'Add Booking' ?></button>
  </form>
</main></body></html>
