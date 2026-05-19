<?php
// СТАРТ СЕСІЇ МАЄ БУТИ НА САМОМУ ПОЧАТКУ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root"; // або ваш новий користувач
$password = "";
$dbname = "olhlar_craftbox";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Помилка підключення: " . $e->getMessage());
}
?>