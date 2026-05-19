<?php
session_start();

if (isset($_GET['id']) && isset($_GET['action'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if (isset($_SESSION['cart'][$product_id])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$product_id]++;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$product_id]--;
            if ($_SESSION['cart'][$product_id] <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
}

// Повертаємо користувача назад на головну
header("Location: index.php");
exit;
?>