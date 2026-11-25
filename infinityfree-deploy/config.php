<?php
// STEP 2: Database + app settings
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_NAME', 'if0_43531850_appointment_db');
define('DB_USER', 'if0_43531850');
define('DB_PASS', 'dvzlxyjGsfr0Wy');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'PL3jFTyLEovM');
const SLOTS = ['09:00','10:00','11:00','13:00','14:00','15:00','16:00'];
const SERVICES = ['General Consultation','Dental Checkup','Haircut','Counseling','Other'];

function db(): PDO {
  static $pdo = null;
  if (!$pdo) {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}
function json_out($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}
