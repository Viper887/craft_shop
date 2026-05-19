<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    die("Доступ лише для продавців");
}

$seller_id = $_SESSION['user_id'];

// 1. Отримуємо всі замовлення
$stmt = $pdo->prepare("
    SELECT o.*, u.name as buyer_name 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$all_orders = $stmt->fetchAll();

$seller_orders = [];

foreach ($all_orders as $order) {
    $items = json_decode($order['items_json'], true);
    $seller_items = [];
    $seller_subtotal = 0;

    if (is_array($items)) {
        foreach ($items as $item) {
            if (isset($item['product_id'])) {
                $stmt_prod = $pdo->prepare("SELECT seller_id FROM products WHERE id = ?");
                $stmt_prod->execute([$item['product_id']]);
                $product = $stmt_prod->fetch();

                if ($product && $product['seller_id'] == $seller_id) {
                    $seller_items[] = $item;
                    $seller_subtotal += $item['price'] * $item['quantity'];
                }
            }
        }
    }

    if (!empty($seller_items)) {
        $order['my_items'] = $seller_items;
        $order['my_total'] = $seller_subtotal;
        $seller_orders[] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Панель продавця - Замовлення</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

    <div class="header-admin">
        <h1>Керування замовленнями</h1>
        <a href="index.php" class="back-link">← На головну</a>
    </div>

    <div class="orders-grid">
        <?php if (empty($seller_orders)): ?>
            <p style="text-align: center; font-size: 1.2em; color: #777;">Замовлень поки немає.</p>
        <?php else: ?>
            <?php foreach ($seller_orders as $order): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <h3>Замовлення №<?php echo $order['id']; ?></h3>
                        <span class="order-status-badge"><?php echo htmlspecialchars($order['status']); ?></span>
                    </div>

                    <div class="order-info">
                        <strong>Клієнт:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <strong>Телефон:</strong> <?php echo htmlspecialchars($order['phone']); ?><br>
                        <strong>Адреса:</strong> <?php echo htmlspecialchars($order['address']); ?>
                    </div>

                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Ціна</th>
                                <th>К-сть</th>
                                <th>Разом</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['my_items'] as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo $item['price']; ?> грн</td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo $item['price'] * $item['quantity']; ?> грн</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="order-total-footer">
                        Ваша сума: <?php echo $order['my_total']; ?> грн
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>