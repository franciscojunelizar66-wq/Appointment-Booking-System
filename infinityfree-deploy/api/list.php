<?php
// Public list of all bookings. Personal details are masked for privacy.
require __DIR__ . '/../config.php';
function mask_name(string $n): string {
  $parts = preg_split('/\s+/', trim($n));
  return implode(' ', array_map(fn($p) => mb_substr($p, 0, 1) . str_repeat('*', max(mb_strlen($p) - 1, 0)), $parts));
}
$rows = db()->query("SELECT id, name, service, appt_date, TIME_FORMAT(appt_time,'%H:%i') AS appt_time, status
                     FROM appointments ORDER BY appt_date DESC, appt_time DESC")->fetchAll();
foreach ($rows as &$r) $r['name'] = mask_name($r['name']);
json_out($rows);
