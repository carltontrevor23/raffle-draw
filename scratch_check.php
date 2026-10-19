<?php
require_once __DIR__ . '/config/db.php';
if (!$pdo) { die("No DB connection: $db_error\n"); }
$stmt = $pdo->query('DESCRIBE prizes');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "=== PRIZES TABLE COLUMNS ===\n";
foreach ($cols as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
echo "\n=== PRIZES DATA ===\n";
$stmt2 = $pdo->query('SELECT id, name, icon FROM prizes ORDER BY id');
$rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo $row['id'] . ": " . $row['name'] . " | icon=" . $row['icon'] . "\n";
}
