-- STEP 1: Import this in phpMyAdmin (http://localhost/phpmyadmin) > Import
CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  service VARCHAR(60) NOT NULL,
  appt_date DATE NOT NULL,
  appt_time TIME NOT NULL,
  notes VARCHAR(255) DEFAULT '',
  status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (appt_date, appt_time)
);
