<?php
require 'config.php';

// --- 1. ЛОГІКА ОБРОБКИ AJAX ---
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
    }
}

// Функція виводу вмісту кошика (синхронізована з index.php)
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
                                <a href="product.php?id=<?= $item['id'] ?>&action=decrease" class="qty-btn ajax-action">-</a>
                                <span class="qty-num"><?= $qty ?></span>
                                <a href="product.php?id=<?= $item['id'] ?>&action=increase" class="qty-btn ajax-action">+</a>
                            </div>
                            <span class="cart-item-price"><?= number_format($subtotal, 0, '.', '') ?> грн</span>
                        </div>
                    </div>
                    <a href="product.php?remove=<?= $item['id'] ?>" class="cart-item-remove ajax-action">&times;</a>
                </li>
            <?php endwhile; ?>
        </ul>
        <div class="side-cart-footer">
            <div class="total-row">
                <span>Всього до сплати:</span>
                <span class="total-price"><?= number_format($total_sum, 0, '.', '') ?> грн</span>
            </div>
            <a href="checkout.php" class="checkout-btn">Оформити замовлення</a>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #7f8c8d; padding-top: 50px; font-weight: 500;">Кошик порожній.</p>
    <?php endif;
}

// ОТРИМАННЯ ДАНИХ ТОВАРУ
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { header("Location: index.php"); exit; }
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['title']) ?> | Craft Box</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap');

        :root {
            --primary: #a11e1e;
            --primary-hover: #851919;
            --bg-main: #fcfbf9;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --card-bg: #ffffff;
            --border-color: #f1ece4;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body { 
            background-color: var(--bg-main); 
            margin: 0; 
            font-family: 'Montserrat', sans-serif; 
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        /* --- ШАПКА З INDEX.PHP --- */
        .header-logo { 
            display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;
            background-color: var(--primary); color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            z-index: 99; position: relative; box-sizing: border-box;
        }
        .header-logo::before { content: ""; flex: 1; }
        .header-logo h1 { 
            flex: 2; text-align: center; margin: 0; font-size: 32px; font-weight: 900; 
            text-transform: uppercase; letter-spacing: 1px;
        }
        .header-actions { flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 20px; }
        .cart-icon-wrapper { margin-bottom: 0; }
        
        .cart-icon-img, .cart-icon-svg, .user-icon-img { 
            width: 30px !important; height: 30px !important; max-width: 30px !important; max-height: 30px !important;
            object-fit: contain !important; cursor: pointer; filter: brightness(0) invert(1); transition: var(--transition); display: block; margin: 0;
        }
        .cart-icon-img:hover, .cart-icon-svg:hover, .user-icon-img:hover { transform: scale(1.1); }
        
        /* МЕНЮ КОРИСТУВАЧА */
        .user-dropdown-container { position: relative; }
        .user-dropdown-content {
            display: none; position: absolute; right: 0; top: 100%; background-color: #fff;
            min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1); z-index: 1000;
            border-radius: 8px; padding: 10px 0; margin-top: 15px; border: 1px solid #eee;
        }
        .user-dropdown-content.active { display: block; }
        .user-dropdown-content a, .user-dropdown-content p {
            color: #333; padding: 10px 16px; text-decoration: none; display: block; margin: 0; font-size: 14px; text-align: center; 
        }
        .user-dropdown-content a:hover { background-color: #f9f9f9; color: #a11e1e; }
        .user-dropdown-content .welcome-text { font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 5px; }

        .cart-badge { 
            position: absolute !important; top: -6px; right: -8px; background: white; color: var(--primary); 
            font-size: 11px; font-weight: 900; border-radius: 50%; width: 18px; height: 18px; 
            display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.15); 
        }

        /* КОШИК БОКОВИЙ ЛОАДЕР */
        .side-cart-content { transition: opacity 0.2s; position: relative; }
        .side-cart-content.loading { opacity: 0.5; pointer-events: none; }
        .side-cart-content.loading::before {
            content: ""; position: absolute; top: 50px; left: 50%; transform: translateX(-50%); width: 32px; height: 32px; border: 3px solid #e2e8f0; border-top: 3px solid var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; z-index: 10;
        }
        @keyframes spin { 0% { transform: translateX(-50%) rotate(0deg); } 100% { transform: translateX(-50%) rotate(360deg); } }

        /* ЗАГОЛОВОК БОКОВОГО КОШИКА */
        .side-cart-header h3 {
            font-size: 20px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            margin: 0 !important;
            color: #fff !important;
        }

        /* Стилі для підпису кількості товарів у боковому кошику */
        .side-cart-count {
            margin: 4px 0 0 0 !important;
            font-size: 12px !important;
            font-weight: 600 !important;
        }

        .side-cart-header {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            padding: 20px !important;
            border-bottom: 1px solid var(--border-color);
        }

        .close-cart {
            font-size: 28px !important;
            cursor: pointer;
            line-height: 1 !important;
        }

        /* Стилі для підпису товару всередині списку кошика */
        .cart-item-desc {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
            margin-bottom: 5px;
            font-weight: 500;
            color: #777;
        }

        /* --- ДИЗАЙН СТОРІНКИ ТОВАРУ (ОПТИМІЗАЦІЯ ДЛЯ ПК) --- */
        .main-container { max-width: 1200px; margin: 30px auto; padding: 0 25px; box-sizing: border-box; }
        
        .back-link { 
            display: inline-flex; align-items: center; margin-bottom: 30px; color: var(--text-muted); 
            text-decoration: none; font-size: 14px; font-weight: 600; transition: var(--transition);
        }
        .back-link:hover { color: var(--primary); transform: translateX(-4px); }
        
        /* Головна сітка картки товару */
        .product-flex { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: start;
            background: var(--card-bg); padding: 50px; border-radius: 24px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f3ece2;
        }
        
        /* Ліва колонка: Фото товару */
        .image-card { 
            background: #faf8f5; border-radius: 20px; padding: 30px; display: flex; 
            align-items: center; justify-content: center; min-height: 500px; max-height: 550px;
            overflow: hidden; border: 1px solid var(--border-color); position: sticky; top: 20px;
        }
        .image-card img { max-width: 100%; max-height: 480px; object-fit: contain; transition: transform 0.4s ease; }
        .image-card:hover img { transform: scale(1.02); }

        /* Права колонка: Контент та деталі */
        .info-side { 
            display: flex; flex-direction: column; height: 100%; min-height: 500px; justify-content: space-between;
        }
        
        /* Заголовок всередині картки справа */
        .product-page-title {
            font-size: 36px; font-weight: 900; margin: 0 0 15px 0; color: var(--text-dark);
            line-height: 1.25; letter-spacing: -0.5px;
        }
        
        .seller-link { 
            display: inline-flex; font-size: 14px; color: var(--text-muted); text-decoration: none; 
            margin-bottom: 30px; transition: 0.2s; background: #f5f0e6; padding: 6px 14px; border-radius: 8px;
        }
        .seller-link:hover { color: var(--primary); background: #ebdcc5; }
        .seller-link b { color: var(--text-dark); margin-left: 4px; }
        
        .desc-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 10px; }
        .desc-text { font-size: 16px; color: #4a5568; line-height: 1.7; margin-bottom: 40px; font-weight: 400; }
        
        /* Блок мета-характеристик (Вага та Ціна) на одній лінії */
        .meta-row {
            display: flex; align-items: center; gap: 25px; margin-bottom: 35px; 
            border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7; padding: 20px 0;
        }
        .weight-tag { 
            font-size: 15px; font-weight: 700; color: #8e7f6e; background: #f5f0e6; 
            padding: 7px 18px; border-radius: 20px; flex-shrink: 0;
        }
        .price-tag { 
            display: inline-flex; align-items: baseline; gap: 5px;
            font-size: 38px; font-weight: 900; color: var(--primary); letter-spacing: -0.5px; line-height: 1;
        }
        .price-tag span { font-size: 16px; color: var(--text-muted); font-weight: 600; }

        /* Кнопка */
        .buy-btn { 
            background: var(--primary); color: white; border: none; padding: 18px 40px; 
            width: 100%; max-width: 320px; border-radius: 50px; font-size: 18px; font-weight: 700; 
            cursor: pointer; transition: var(--transition); text-align: center; text-decoration: none; 
            box-shadow: 0 8px 25px rgba(161, 30, 30, 0.15); display: block;
        }
        .buy-btn:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 12px 28px rgba(161, 30, 30, 0.25); }

        /* --- АДАПТИВНІСТЬ ДЛЯ МОБІЛЬНИХ --- */
        @media (max-width: 992px) {
            .product-flex { grid-template-columns: 1fr; gap: 35px; padding: 30px; }
            .image-card { min-height: auto; max-height: 400px; padding: 20px; position: static; }
            .image-card img { max-height: 350px; }
            .info-side { min-height: auto; }
            .buy-btn { max-width: 100%; }
        }

        @media (max-width: 768px) {
            .header-logo { 
                padding: 10px 15px !important; 
                flex-wrap: nowrap !important;
            }
            
            .header-logo::before { display: none !important; }
            
            .header-logo h1 {
                position: absolute !important; 
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                font-size: 1.6rem !important; 
                white-space: nowrap !important;
                overflow: hidden;
                text-overflow: ellipsis; 
                margin: 0 !important;
                z-index: 1 !important;
            }
            
            .header-actions { 
                position: relative !important;
                z-index: 2 !important;
                margin-left: auto !important;
                display: flex !important;
                align-items: center !important;
                gap: 0px !important;
            }
            
            .cart-icon-wrapper {
                position: relative !important; 
                display: flex !important;
                align-items: center !important;
                margin-bottom: 0 !important;
            }
            
            .cart-icon-img, .cart-icon-svg, .user-icon-img { 
                width: 26px !important; 
                height: 26px !important; 
                min-width: 26px !important; 
                min-height: 26px !important;
            }
            .cart-badge { 
                position: absolute !important;
                top: -6px !important; 
                right: -6px !important; 
                width: 16px !important;
                height: 16px !important;
                font-size: 10px !important;
            }
            
            .main-container { margin: 20px auto; padding: 0 15px; }
            .product-flex { padding: 20px; border-radius: 16px; }
            .product-page-title { font-size: 26px; }
            .meta-row { flex-direction: column; align-items: flex-start; gap: 15px; padding: 15px 0; }
            .price-tag { font-size: 32px; }
        }
    </style>
</head>
<body>

    <div id="cart-overlay" onclick="toggleCart()"></div>

    <div class="header-logo">
        <h1>Craft Box</h1>
        <div class="header-actions">
            <div class="cart-icon-wrapper" onclick="toggleCart()">
                <img src="uploads/cart.png" alt="Кошик" class="cart-icon-img cart-icon-svg">
                <span id="cart-badge-container">
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-badge"><?= array_sum($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="user-dropdown-container">
                <img src="uploads/user-icon.png" alt="Профіль" class="user-icon-img" onclick="toggleUserMenu(event)">
                <div id="userDropdown" class="user-dropdown-content">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <p class="welcome-text">Вітаємо, <?= htmlspecialchars($_SESSION['name']); ?>!</p>
                        <a href="<?= $_SESSION['role'] === 'seller' ? 'seller_profile.php' : 'customer_profile.php' ?>?id=<?= $_SESSION['user_id'] ?>">Мій кабінет</a>
                        <a href="logout.php" style="color: var(--primary); border-top: 1px solid #edf2f7;">Вийти</a>
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
            <div>
                <h3>Ваш кошик</h3>
                <p class="side-cart-count">Всього товарів: <span id="side-cart-total-qty"><?= !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span></p>
            </div>
            <span class="close-cart" onclick="toggleCart()">&times;</span>
        </div>
        <div class="side-cart-content" id="ajax-cart-container">
            <?php include_cart_content($pdo); ?>
        </div>
    </div>

    <div class="main-container">
        <a href="index.php" class="back-link">← Повернутися до каталогу</a>

        <div class="product-flex">
            <div class="image-card">
                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
            </div>

            <div class="info-side">
                <div class="info-top-content">
                    <h2 class="product-page-title"><?= htmlspecialchars($product['title']) ?></h2>
                    
                    <a href="seller_profile.php?id=<?= $product['seller_id'] ?>" class="seller-link">
                        Майстер: <b><?= htmlspecialchars($product['seller_name']) ?></b>
                    </a>

                    <div class="desc-label">Опис товару</div>
                    <div class="desc-text">
                        <?= nl2br(htmlspecialchars($product['description'] ?: 'Натуральний продукт ручної роботи від майстрів Полтавщини.')) ?>
                    </div>
                </div>

                <div class="info-bottom-content">
                    <div class="meta-row">
                        <div class="weight-tag"><?= (int)$product['weight'] ?> г</div>
                        <div class="price-tag">
                            <span>Ціна:</span> <?= number_format($product['price'], 0, '.', '') ?> грн
                        </div>
                    </div>

                    <a href="product.php?id=<?= $product['id'] ?>&add_to_cart=<?= $product['id'] ?>" class="buy-btn ajax-action">
                        Додати в кошик
                    </a>
                </div>
            </div>
        </div>
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
                if (dropdown && dropdown.classList.contains('active')) dropdown.classList.remove('active');
            }
        }

        // --- AJAX ОБРОБКА ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.ajax-action');
            if (!btn) return;

            e.preventDefault(); 
            
            const url = btn.getAttribute('href');
            const container = document.getElementById('ajax-cart-container');
            container.classList.add('loading');

            fetch(url + '&ajax=1')
                .then(res => res.json())
                .then(data => {
                    container.innerHTML = data.html;
                    container.classList.remove('loading');
                    
                    // Синхронізація верхнього баджа іконки кошика
                    const badgeContainer = document.getElementById('cart-badge-container');
                    if (data.total_count > 0) {
                        badgeContainer.innerHTML = `<span class="cart-badge">${data.total_count}</span>`;
                    } else {
                        badgeContainer.innerHTML = '';
                    }

                    // ДИНАМІЧНЕ ОНОВЛЕННЯ кількості товарів під написом "Ваш кошик"
                    const sideCartTotalQty = document.getElementById('side-cart-total-qty');
                    if (sideCartTotalQty) {
                        sideCartTotalQty.innerText = data.total_count;
                    }

                    if (btn.classList.contains('buy-btn')) {
                        if (!document.getElementById('side-cart').classList.contains('active')) {
                            toggleCart();
                        }
                    }
                })
                .catch(err => {
                    console.error('Помилка AJAX:', err);
                    container.classList.remove('loading');
                });
        });
    </script>
</body>
</html>