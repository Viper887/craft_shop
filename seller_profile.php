<?php
require 'config.php';

$seller_id = $_GET['id'] ?? null;
if (!$seller_id) die("Продавця не знайдено");

// --- ЛОГІКА ВИДАЛЕННЯ ТОВАРУ ---
if (isset($_GET['delete_product']) && isset($_SESSION['user_id'])) {
    $product_id = $_GET['delete_product'];
    
    // Перевіряємо, чи цей товар дійсно належить поточному продавцю (захист)
    $stmt_check = $pdo->prepare("SELECT image_path FROM products WHERE id = ? AND seller_id = ?");
    $stmt_check->execute([$product_id, $_SESSION['user_id']]);
    $product_to_delete = $stmt_check->fetch();

    if ($product_to_delete) {
        // Видаляємо файл зображення з сервера
        if (file_exists($product_to_delete['image_path'])) {
            unlink($product_to_delete['image_path']);
        }
        
        // Видаляємо запис з бази даних
        $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->execute([$product_id]);
        
        // Перенаправляємо назад, щоб очистити GET-параметри
        header("Location: seller_profile.php?id=" . $_SESSION['user_id']);
        exit;
    }
}
// ------------------------------

// Отримуємо дані продавця
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'seller'");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

if (!$seller) die("Продавця не існує");

// Перевіряємо, чи це власник сторінки
$is_owner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $seller_id);

// Отримуємо товари цього продавця
$stmt_items = $pdo->prepare("SELECT * FROM products WHERE seller_id = ?");
$stmt_items->execute([$seller_id]);
$my_products = $stmt_items->fetchAll();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($seller['name']) ?> | Craft Box</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-layout { display: grid; grid-template-columns: 1.2fr 2.5fr; gap: 40px; max-width: 1400px; margin: 40px auto; padding: 0 40px; }
        .info-card { background: white; padding: 35px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); height: fit-content; font-size: 1.1rem; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        
        .product-item-card {
            background: white; border-radius: 15px; padding: 15px; text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column;
            justify-content: space-between; height: 100%; position: relative;
            /* Запобігаємо розширенню самої картки */
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .product-item-card img { width: 100%; height: 180px; object-fit: contain; margin-bottom: 15px; border-radius: 10px; background-color: #f9f9f9; }
        
        /* ВИПРАВЛЕННЯ ДЛЯ ПЕРЕНОСУ НАЗВИ */
        .product-item-card h4 { 
            font-size: 1.1rem; 
            margin: 5px 0; 
            font-weight: 600; 
            color: #333;
            /* Магія переносу тут: */
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all; /* Примусово розриває навіть довгі набори цифр */
            white-space: normal;
            max-width: 100%;
        }

        .price-text { color: #a11e1e; font-weight: bold; font-size: 1.1rem; margin: 5px 0; }
        
        .delete-btn {
            text-decoration: none;
            color: #ff4d4d;
            font-size: 13px;
            margin-top: 10px;
            padding: 5px;
            border: 1px solid #ff4d4d;
            border-radius: 8px;
            transition: 0.3s;
        }
        .delete-btn:hover { background: #ff4d4d; color: white; }

        .buy-link {
            display: block;
            background-color: #a11e1e;
            color: white !important;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            border: none;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(161, 30, 30, 0.2);
            text-align: center;
        }

        .buy-link:hover {
            background-color: #7e1717;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(161, 30, 30, 0.3);
        }

        .badge-seller { background: #a11e1e; color: white; padding: 4px 12px; border-radius: 8px; font-size: 14px; vertical-align: middle; margin-left: 10px; }
        .submit-btn { display: block; background-color: #a11e1e; color: white !important; padding: 10px; border-radius: 20px; text-align: center; text-decoration: none; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body style="background-color: #e6e2dd;">

    <div class="header-logo" style="background-color: #a11e1e; color: white; padding: 20px; text-align: center;">
        <h1>CRAFT BOX</h1>
        <a href="index.php" style="color: white; text-decoration: none;">← На головну</a>
    </div>

    <div class="profile-layout">
        <div class="info-card">
            <h2 style="margin-top: 0;"><?= htmlspecialchars($seller['name']) ?> <span class="badge-seller">Продавець</span></h2>
            
            <?php if ($is_owner): ?>
                <p style="margin-top: 20px;"><strong>Ваші дані:</strong></p>
                <p>Email: <?= htmlspecialchars($seller['email']) ?></p>
                <a href="add_product.php" class="submit-btn">+ Додати товар</a>
            <?php else: ?>
                <p style="margin-top: 20px;">Цей продавець пропонує унікальні вироби ручної роботи.</p>
            <?php endif; ?>
        </div>

        <div class="content-area">
            <h3>Товари продавця:</h3>
            <div class="product-grid">
                <?php if (empty($my_products)): ?>
                    <p>Товарів поки немає.</p>
                <?php else: ?>
                    <?php foreach ($my_products as $item): ?>
                        <div class="product-item-card">
                            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                            <h4><?= htmlspecialchars($item['title']) ?></h4>
                            <p class="price-text"><?= number_format($item['price'], 2, '.', '') ?> грн</p>
                            
                            <?php if ($is_owner): ?>
                                <a href="seller_profile.php?id=<?= $seller_id ?>&delete_product=<?= $item['id'] ?>" 
                                   class="delete-btn" 
                                   onclick="return confirm('Ви впевнені, що хочете видалити цей товар?')">
                                   Видалити товар
                                </a>
                            <?php else: ?>
                                <a href="index.php?add_to_cart=<?= $item['id'] ?>" class="buy-link">В кошик</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>