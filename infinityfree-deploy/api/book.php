<?php
require __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'POST only'], 405);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$name  = trim($in['name'] ?? '');
$email = trim($in['email'] ?? '');
$phone = trim($in['phone'] ?? '');
$service = $in['service'] ?? '';
$date = $in['date'] ?? '';
$time = $in['time'] ?? '';
$notes = substr(trim($in['notes'] ?? ''), 0, 255);

if ($name === '' || strlen($name) > 100) json_out(['error' => 'Name is required'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Invalid email'], 422);
if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) json_out(['error' => 'Invalid phone number'], 422);
if (!in_array($service, SERVICES, true)) json_out(['error' => 'Invalid service'], 422);
if (!in_array($time, SLOTS, true)) json_out(['error' => 'Invalid time slot'], 422);
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) json_out(['error' => 'Invalid date'], 422);
if ($date < date('Y-m-d')) json_out(['error' => 'Date is in the past'], 422);
if ((int)$d->format('N') >= 6) json_out(['error' => 'Weekends are closed'], 422);

$pdo = db();
try {
  $pdo->beginTransaction();
  // Lock matching rows so two people can't grab the same slot
  $chk = $pdo->prepare("SELECT id FROM appointments WHERE appt_date=? AND appt_time=? AND status<>'cancelled' FOR UPDATE");
  $chk->execute([$date, $time]);
  if ($chk->fetch()) { $pdo->rollBack(); json_out(['error' => 'Sorry, that slot was just taken. Pick another.'], 409); }
  $ins = $pdo->prepare("INSERT INTO appointments (name,email,phone,service,appt_date,appt_time,notes) VALUES (?,?,?,?,?,?,?)");
  $ins->execute([$name, $email, $phone, $service, $date, $time, $notes]);
  $id = $pdo->lastInsertId();
  $pdo->commit();
  json_out(['success' => true, 'id' => $id]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => 'Server error'], 500);
}
