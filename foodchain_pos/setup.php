<?php

require 'config.php';

$count = (int)$pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

if ($count > 0) {
    exit("An administrator account already exists.");
}

$password = password_hash(
    'ChangeMe123!',
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare(
    "INSERT INTO users
    (username, password_hash)
    VALUES (?, ?)"
);

$stmt->execute(['admin', $password]);

echo "Administrator account created successfully.";
echo "<br>Username: admin";
echo "<br>Password: ChangeMe123!";
echo "<br>Delete setup.php after installation.";

?>
