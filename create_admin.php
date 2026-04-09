<?php
require_once 'config/db.php';

$name = "Admin";
$email = "admin@clinic.com";
$password = password_hash("admin123", PASSWORD_BCRYPT);
$role = "admin";

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $password, $role);

$stmt->execute();

echo "Admin user created successfully!";