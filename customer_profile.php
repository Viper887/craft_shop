<?php
// Переконайся, що сесія стартує у config.php, інакше розкоментуй рядок нижче:
// session_start();

require 'config.php';

// Перевірка, чи користувач увійшов в аккаунт
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримуємо дані поточного користувача
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Отримуємо історію замовлень покупця за його user_id
$stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мій профіль — Craft Box</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <style>
        body {
            background-color: #e3dcd2;
            color: #1a1a1a;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header-logo {
            width: 100%;
            background-color: #a11e1e;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header-logo h1 {
            color: #fff;
            font-weight: 900;
            text-transform: uppercase;
            font-style: italic;
            letter-spacing: 2px;
            font-size: 32px;
            margin: 0;
        }

        .header-logo .sub-link {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            text-decoration: none;
            margin-top: 5px;
            font-weight: 500;
        }

        .header-logo .sub-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        /* Фірмовий Grid-лейаут */
        .profile-layout { 
            display: grid; 
            grid-template-columns: 1.2fr 2.5fr; 
            gap: 40px; 
            max-width: 1400px; 
            margin: 40px auto; 
            padding: 0 40px; 
            box-sizing: border-box;
        }

        /* Картка інформації про користувача */
        .info-card { 
            background: white; 
            padding: 35px; 
            border-radius: 25px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            height: fit-content; 
            font-size: 1.1rem; 
            box-sizing: border-box;
        }

        .info-card h2 {
            font-size: 24px;
            font-weight: 900;
            color: #333;
            margin: 0;
            display: inline-block;
        }

        .profile-meta-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 25px;
            margin-bottom: 5px;
        }

        .profile-meta-value {
            font-size: 15px;
            color: #000;
            word-break: break-all;
            margin: 0;
        }

        .badge-seller { 
            background: #a11e1e; 
            color: white; 
            padding: 4px 12px; 
            border-radius: 8px; 
            font-size: 14px; 
            vertical-align: middle;
            margin-left: 10px;
            font-weight: bold;
        }

        .submit-btn {
            display: block;
            background-color: #a11e1e;
            color: white !important;
            padding: 12px;
            border-radius: 20px;
            text-align: center;
            text-decoration: none;
            margin-top: 30px;
            font-weight: bold;
        }

        /* Заголовки секцій */
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 0;
            margin-bottom: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ccc;
        }

        /* Зовнішня сітка замовлень */
        .product-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
            gap: 20px; 
            margin-top: 20px;
        }

        /* Велика картка замовлення */
.product-item-card {
    background: white;
    border-radius: 25px; 
    padding: 25px; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between; 
    box-sizing: border-box;
    /* Захист від розширення картки */
    overflow: hidden; 
}

        .order-number {
            font-size: 1.2rem;
            margin: 0 0 4px 0;
            font-weight: bold;
            color: #333;
            text-align: center;
        }

        .order-number span {
            color: #a11e1e;
        }

        .order-date {
            font-size: 12px;
            color: #888;
            margin: 0 0 12px 0;
            text-align: center;
        }

        /* Статус замовлення */
        .status-badge {
            background: #fff3cd;
            color: #856404;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 12px;
            border-radius: 12px;
            align-self: center;
            margin-bottom: 20px;
        }
        
        .status-new { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }

        /* Контейнер для списку товарів */
        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 15px;
            flex-grow: 1; 
        }

        /* Компактний горизонтальний стиль */
        .order-item-row {
            display: flex;
            flex-direction: row; 
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
            background: #fdfcfb;
            padding: 10px 15px;
            border-radius: 15px;
            border: 1px solid #f0ede9;
            box-sizing: border-box;
            width: 100%;
        }

        /* Обгортка для фото зліва */
        .order-item-img-wrapper {
            width: 70px; 
            height: 50px;
            flex-shrink: 0;
            background-color: #f5f3f0;
            border: 1px solid #e0dbd5;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .order-item-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Блок з текстом праворуч від фото */
        .order-item-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            flex-grow: 1;
        }

.order-item-title {
    font-size: 14px;
    font-weight: 600;
    color: #222;
    margin: 0;
    line-height: 1.2;
    text-align: left;
    
    /* Додаємо ці рядки для переносу довгих слів/цифр */
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-all; /* Примусовий розрив, якщо назва — суцільний набір символів */
    white-space: normal;
    max-width: 100%;
}

        .order-item-qty {
            font-size: 11px;
            color: #666;
            background: #f0ebe4;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: bold;
        }

        .price-text {
            color: #a11e1e;
            font-weight: 900;
            font-size: 1.3rem;
            margin: 10px 0 0 0;
            padding-top: 12px;
            border-top: 1px dashed #eee;
            text-align: center;
        }

        .price-label {
            font-size: 13px;
            color: #666;
            font-weight: normal;
            display: block;
            margin-bottom: 3px;
        }

        @media (max-width: 850px) {
            .profile-layout {
                grid-template-columns: 1fr;
                padding: 0 20px;
                gap: 20px;
            }
            .info-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="header-logo">
        <h1>Craft Box</h1>
        <a href="index.php" class="sub-link">← На головну</a>
    </div>

    <div class="profile-layout">
        
        <div class="info-card">
            <div style="display: flex; align-items: center; justify-content: flex-start;">
                <h2><?php echo htmlspecialchars($user['name'] ?? 'Sayma'); ?></h2>
                <span class="badge-seller">Покупець</span>
            </div>
            
            <p class="profile-meta-title">Ваші дані (бачите тільки ви):</p>
            <p class="profile-meta-title" style="margin-top: 15px; color: #000;">Email:</p>
            <p class="profile-meta-value"><?php echo htmlspecialchars($user['email'] ?? 'example@gmail.com'); ?></p>
            
            <a href="index.php" class="submit-btn">+ До покупок</a>
        </div>

        <div>
            <h3 class="section-title">Ваші замовлення:</h3>

            <?php if (!empty($orders)): ?>
                <div class="product-grid">
                    <?php foreach ($orders as $order): 
                        $items = [];
                        if (!empty($order['items_json'])) {
                            $items = json_decode($order['items_json'], true);
                        }

                        // Ініціалізуємо змінну для точного розрахунку вартості замовлення
                        $calculated_total_price = 0;
                    ?>
                        <div class="product-item-card">
                            
                            <p class="order-number">Замовлення <span>#<?php echo htmlspecialchars($order['id']); ?></span></p>
                            <p class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                            
                            <span class="status-badge status-<?php echo mb_strtolower($order['status']); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>

                            <div class="order-items-list">
                                <?php if (!empty($items) && is_array($items)): ?>
                                    <?php foreach ($items as $item): 
                                        $p_id = $item['product_id'] ?? 0;
                                        $qty = intval($item['quantity'] ?? 1);
                                        $price = floatval($item['price'] ?? 0);

                                        // Динамічно додаємо вартість поточного товару (ціна * кількість) до загальної суми
                                        $calculated_total_price += ($price * $qty);
                                        
                                        $stmt_img = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                                        $stmt_img->execute([$p_id]);
                                        $prod_data = $stmt_img->fetch();
                                        
                                        if (!empty($prod_data['image_path'])) {
                                            $img_src = 'uploads/' . basename($prod_data['image_path']);
                                        } else {
                                            $img_src = 'uploads/default.jpg';
                                        }
                                    ?>
                                        <div class="order-item-row">
                                            <div class="order-item-img-wrapper">
                                                <img src="<?php echo htmlspecialchars($img_src); ?>" class="order-item-img" alt="Product">
                                            </div>
                                            <div class="order-item-info">
                                                <p class="order-item-title"><?php echo htmlspecialchars($item['title'] ?? 'Товар'); ?></p>
                                                <span class="order-item-qty">x<?php echo $qty; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="font-size: 12px; color: #999; text-align: center;">Деталі відсутні.</p>
                                <?php endif; ?>
                            </div>

                            <p class="price-text">
                                <span class="price-label">Разом до сплати:</span>
                                <?php echo number_format($calculated_total_price, 2, '.', ''); ?> грн
                            </p>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <p style="color: #666; font-size: 1.1rem; margin: 0;">У вас поки немає замовлень.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>