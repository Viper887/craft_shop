<?php
session_start();

/**
 * Додає товар у кошик
 */
function addToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Видаляє товар з кошика
 */
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
}

/**
 * Оновлює кількість товару
 */
function updateQuantity($productId, $quantity) {
    if ($quantity <= 0) {
        removeFromCart($productId);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Рахує загальну суму
 */
function getCartTotal($pdo) {
    if (empty($_SESSION['cart'])) return 0;

    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    $total = 0;
    foreach ($products as $product) {
        $total += $product['price'] * $_SESSION['cart'][$product['id']];
    }
    return $total;
}
?>