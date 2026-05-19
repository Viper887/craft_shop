<?php 
require 'config.php'; 

// --- 1. ЛОГІКА ОБРОБКИ (AJAX ТА ЗВИЧАЙНА) ---
if (isset($_GET['ajax']) || isset($_GET['add_to_cart']) || isset($_GET['remove']) || isset($_GET['action'])) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (isset($_GET['add_to_cart'])) {
        $id = (int)$_GET['add_to_cart'];
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    }

    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($_GET['action'] == 'increase') {
            $_SESSION['cart'][$id]++;
        } elseif ($_GET['action'] == 'decrease') {
            if ($_SESSION['cart'][$id] > 1) $_SESSION['cart'][$id]--;
            else unset($_SESSION['cart'][$id]);
        }
    }

    if (isset($_GET['remove'])) {
        unset($_SESSION['cart'][(int)$_GET['remove']]);
    }

    if (isset($_GET['ajax'])) {
        ob_start();
        include_cart_content($pdo);
        $html = ob_get_clean();
        
        header('Content-Type: application/json');
        echo json_encode([
            'html' => $html,
            'total_count' => !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0
        ]);
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

function include_cart_content($pdo) {
    if (!empty($_SESSION['cart'])): ?>
        <ul class="cart-list">
            <?php 
            $total_sum = 0;
            $keys = array_keys($_SESSION['cart']);
            $ids = implode(',', array_map('intval', $keys));
            $stmt_cart = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
            while($item = $stmt_cart->fetch()): 
                $qty = $_SESSION['cart'][$item['id']];
                $subtotal = $item['price'] * $qty;
                $total_sum += $subtotal;
            ?>
                <li class="cart-item">
                    <div class="cart-item-image">
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
                    </div>
                    <div class="cart-item-details">
                        <span class="cart-item-title"><?= htmlspecialchars($item['title']) ?></span>
                        <span class="cart-item-desc">Крафтовий товар</span> 
                        <div class="cart-item-controls">
                            <div class="quantity-control">
                                <a href="index.php?id=<?= $item['id'] ?>&action=decrease" class="qty-btn">-</a>
                                <span class="qty-num"><?= $qty ?></span>
                                <a href="index.php?id=<?= $item['id'] ?>&action=increase" class="qty-btn">+</a>
                            </div>
                            <span class="cart-item-price"><?= number_format($subtotal, 2, '.', '') ?> грн</span>
                        </div>
                    </div>
                    <a href="index.php?remove=<?= $item['id'] ?>" class="cart-item-remove">
                        <span>&times;</span>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
        <div class="side-cart-footer">
            <div class="total-row">
                <span>Всього до сплати:</span>
                <span class="total-price"><?= number_format($total_sum, 2, '.', '') ?> грн</span>
            </div>
            <a href="checkout.php" class="checkout-btn">Оформити замовлення</a>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #666; padding-top: 50px;">Кошик порожній.</p>
    <?php endif;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Крафт Бокс — Крафтові товари Полтави</title>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="style.css">
    <style>
        .seller-link { display: block; font-size: 13px; color: #666; text-decoration: none; margin-bottom: 10px; transition: 0.3s; }
        .seller-link:hover { color: #a11e1e; text-decoration: underline; }
        .seller-link b { color: #333; }
        
        .side-cart-content { transition: opacity 0.2s; }
        .loading { opacity: 0.5; pointer-events: none; }

        .header-logo { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px 20px;
            position: relative;
        }

        .header-logo::before { content: ""; flex: 1; }
        .header-logo h1 { flex: 2; text-align: center; margin: 0; }
        .header-actions { flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 20px; }
        
        .user-dropdown-container { position: relative; }
        .user-icon-img { width: 30px; height: 30px; cursor: pointer; display: block; filter: brightness(0) invert(1); margin: 0; }
        
        .user-dropdown-content {
            display: none; position: absolute; right: 0; top: 100%; background-color: #fff;
            min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1); z-index: 1000;
            border-radius: 8px; padding: 10px 0; margin-top: 15px; border: 1px solid #eee;
        }
        .user-dropdown-content.active { display: block; }
        .user-dropdown-content a, .user-dropdown-content p {
            color: #333; padding: 10px 16px; text-decoration: none; display: block;
            margin: 0; font-size: 14px; text-align: center; 
        }
        .user-dropdown-content a:hover { background-color: #f9f9f9; color: #a11e1e; }
        .user-dropdown-content .welcome-text { font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 5px; }

        /* СТИЛІ ДЛЯ ПОСИЛАНЬ НА ТОВАР */
        .product-link { text-decoration: none; color: inherit; display: block; }
        .card img { transition: transform 0.3s ease; }
        .card:hover img { transform: scale(1.02); }
        
        .card h3 {
            font-size: 1.1rem; margin: 10px 0; color: #333;
            word-wrap: break-word; overflow-wrap: break-word;
            display: block; max-width: 100%;
        }
        .card h3:hover { color: #a11e1e; }

        .cart-item-title {
            font-weight: bold; color: #333; font-size: 14px; display: block;
            word-wrap: break-word; overflow-wrap: break-word; max-width: 180px;
        }

        .card {
            background: white; padding: 20px; border-radius: 15px; text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex;
            flex-direction: column; justify-content: space-between; overflow: hidden;
        }
        .cart-item-details { flex: 1; min-width: 0; padding-right: 10px; }
        
/* Фінальне коригування шапки для мобільних (до 768px) */
@media (max-width: 768px) {
    .header-logo {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 15px !important;
        box-sizing: border-box !important;
    }

    /* Ховаємо розпірку комп'ютерної версії */
    .header-logo::before {
        display: none !important;
    }

    /* Ставимо логотип по центру */
    .header-logo h1 {
        position: absolute !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        margin: 0 !important;
        font-size: 1.5rem !important;
        white-space: nowrap !important;
        width: auto !important;
    }

    /* Контейнер для обох іконок: зсуваємо вправо та підсуваємо їх ближче */
    .header-actions {
        margin-left: auto !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 10px !important; /* Корзина стала ближче до чоловічка */
        width: auto !important;
        position: relative !important;
        z-index: 10 !important;
    }

    /* Стабільна поведінка для іконок всередині */
    .cart-icon-wrapper, 
    .user-dropdown-container {
        position: relative !important;
        display: inline-block !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        left: auto !important;
        right: auto !important;
        transform: none !important;
    }

    /* Фіксуємо розміри самих картинок-іконок */
    .cart-icon-svg, 
    .user-icon-img {
        width: 28px !important;
        height: 28px !important;
        display: block !important;
    }

    /* Щоб бейдж (цифра) кошика не розпирав іконку */
    .cart-badge {
        position: absolute !important;
        top: -8px !important;
        right: -8px !important;
    }

    /* Випадаюче меню користувача */
    .user-dropdown-content {
        right: 0 !important;
        left: auto !important;
        top: 100% !important;
        margin-top: 10px !important;
    }
}
    </style>
</head>
<body>

    <div id="cart-overlay" onclick="toggleCart()"></div>

    <div class="header-logo">
        <h1>Craft Box</h1>
        <div class="header-actions">
            <div class="cart-icon-wrapper" onclick="toggleCart()">
                <img src="uploads/cart.png" alt="Кошик" class="cart-icon-svg">
                <span id="cart-badge-container">
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-badge"><?php echo array_sum($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="user-dropdown-container">
                <img src="uploads/user-icon.png" alt="Профіль" class="user-icon-img" onclick="toggleUserMenu(event)">
                <div id="userDropdown" class="user-dropdown-content">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <p class="welcome-text">Вітаємо, <?= htmlspecialchars($_SESSION['name']); ?>!</p>
                        <?php 
                        $profile_page = ($_SESSION['role'] == 'seller') ? 'seller_profile.php' : 'customer_profile.php';
                        ?>
                        <a href="<?= $profile_page; ?>?id=<?= $_SESSION['user_id']; ?>">Мій кабінет</a>
                        <?php if ($_SESSION['role'] == 'seller'): ?>
                            <a href="admin_orders.php">Переглянути замовлення</a>
                            <a href="add_product.php">Додати товар</a>
                        <?php endif; ?>
                        <a href="logout.php" style="color: #a11e1e; border-top: 1px solid #eee;">Вийти</a>
                    <?php else: ?>
                        <a href="login.php">Увійти</a>
                        <a href="register.php">Реєстрація</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="side-cart" class="side-cart">
        <div class="side-cart-header">
            <div class="side-cart-title-block">
                <h3>Ваш кошик</h3>
                <p class="side-cart-count">Всього товарів: <span id="side-cart-total-qty"><?= !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span></p>
            </div>
            <span class="close-cart" onclick="toggleCart()">&times;</span>
        </div>
        <div class="side-cart-content" id="ajax-cart-container">
            <?php include_cart_content($pdo); ?>
        </div>
    </div>

    <div class="hero-banner">
        <img src="uploads/own.png" alt="Craft Box Banner">
    </div>

    <h2 class="catalog-title">Каталог</h2>

    <div class="products-container">
        <?php
        $stmt = $pdo->query("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id");
        while($row = $stmt->fetch()): ?>
            <div class='card'>
                <a href="product.php?id=<?= $row['id'] ?>" class="product-link">
                    <img src='<?php echo htmlspecialchars($row['image_path']); ?>' alt=''>
                </a>

                <a href="product.php?id=<?= $row['id'] ?>" class="product-link">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                </a>

                <a href="seller_profile.php?id=<?php echo $row['seller_id']; ?>" class="seller-link">
                    Продавець: <b><?php echo htmlspecialchars($row['seller_name']); ?></b>
                </a>
                
                <p class='price'><?php echo number_format($row['price'], 2, '.', ''); ?> грн</p>
                <a href="index.php?add_to_cart=<?= $row['id'] ?>" class="buy-btn ajax-action">Додати в кошик</a>
            </div>
        <?php endwhile; ?>
    </div>

    <script>
        function toggleCart() {
            document.getElementById('side-cart').classList.toggle('active');
            document.getElementById('cart-overlay').classList.toggle('active');
        }

        function toggleUserMenu(e) {
            e.stopPropagation();
            document.getElementById('userDropdown').classList.toggle('active');
        }

        window.onclick = function(event) {
            if (!event.target.matches('.user-icon-img')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown && dropdown.classList.contains('active')) {
                    dropdown.classList.remove('active');
                }
            }
        }

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.ajax-action, .qty-btn, .cart-item-remove, .buy-btn');
            if (!btn) return;

            const url = btn.getAttribute('href');
            if (!url || url === 'javascript:void(0)') return;

            e.preventDefault();
            const container = document.getElementById('ajax-cart-container');
            container.classList.add('loading');

            fetch(url + '&ajax=1')
                .then(res => res.json())
                .then(data => {
                    container.innerHTML = data.html;
                    container.classList.remove('loading');
                    
                    document.getElementById('side-cart-total-qty').innerText = data.total_count;
                    const badgeContainer = document.getElementById('cart-badge-container');
                    if (data.total_count > 0) {
                        badgeContainer.innerHTML = `<span class="cart-badge">${data.total_count}</span>`;
                    } else {
                        badgeContainer.innerHTML = '';
                    }

                    if (btn.classList.contains('buy-btn') && !document.getElementById('side-cart').classList.contains('active')) {
                        toggleCart();
                    }
                });
        });
    </script>
</body>
</html>