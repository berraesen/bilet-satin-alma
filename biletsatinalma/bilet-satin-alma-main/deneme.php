<?php
$db = new PDO("sqlite:bilet_sistemi.db");
$hash = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO User (full_name, email, password, role) VALUES (?, ?, ?, 'admin')");
$stmt->execute(["Sistem Admini", "admin@example.com", $hash]);

echo "✅ Admin eklendi.";