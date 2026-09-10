<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo->exec("ALTER TABLE reminders MODIFY COLUMN reminder_type ENUM('immunization','prenatal','postnatal','family_planning','general') NOT NULL");
    echo "SUCCESS: Updated reminders.reminder_type ENUM to include 'family_planning'\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
