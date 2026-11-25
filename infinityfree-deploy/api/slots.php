<?php
// Returns all slots for a date, marking which are taken
require __DIR__ . '/../config.php';
$date = $_GET['date'] ?? '';
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) json_out(['error' => 'Invalid date'], 400);

$st = db()->prepare("SELECT TIME_FORMAT(appt_time,'%H:%i') t FROM appointments WHERE appt_date=? AND status<>'cancelled'");
$st->execute([$date]);
$taken = array_column($st->fetchAll(), 't');
$out = [];
foreach (SLOTS as $s) $out[] = ['time' => $s, 'available' => !in_array($s, $taken)];
json_out($out);
