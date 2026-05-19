<?php
// Увімкнення відображення помилок для розробки (можна прибрати на продакшені)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

// Перевірка кошика
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $delivery_type = $_POST['delivery_type'] ?? 'np';
    $payment_method = $_POST['payment_method'] ?? 'cod';

    // Серверна валідація базових даних
    if (empty($name) || !preg_match('/^\+380\d{9}$/', $phone)) {
        $error = "Некоректні дані. Перевірте ім'я та номер телефону.";
    } else {
        $is_address_valid = true;
        
        // Формування та валідація адреси
        if ($delivery_type === 'np') {
            $city = htmlspecialchars(trim($_POST['np_city_name'] ?? ''));
            $office = htmlspecialchars(trim($_POST['np_office'] ?? ''));
            
            if (empty($city) || empty($office)) {
                $error = "Будь ласка, оберіть місто та відділення Нової Пошти.";
                $is_address_valid = false;
            } else {
                $full_address = "Нова Пошта: м. $city, $office";
            }
        } else {
            $city = htmlspecialchars(trim($_POST['home_city'] ?? ''));
            $street = htmlspecialchars(trim($_POST['home_street'] ?? ''));
            $house = htmlspecialchars(trim($_POST['home_house'] ?? ''));
            $flat = htmlspecialchars(trim($_POST['home_flat'] ?? ''));

            // Перевірка обов'язкових полів для кур'єра (місто, вулиця, будинок)
            if (empty($city) || empty($street) || empty($house)) {
                $error = "Для кур'єрської доставки необхідно вказати місто, вулицю та будинок.";
                $is_address_valid = false;
            } else {
                $flat_info = !empty($flat) ? ", кв. $flat" : "";
                $full_address = "Кур'єр: м. $city, вул. $street, буд. $house" . $flat_info;
            }
        }

        // Якщо адреса пройшла валідацію — оформлюємо замовлення
        if ($is_address_valid) {
            try {
                $ids = array_keys($_SESSION['cart']);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $products = $stmt->fetchAll();

                $total_price = 0;
                $items_for_json = [];
                foreach ($products as $product) {
                    $qty = $_SESSION['cart'][$product['id']];
                    $total_price += $product['price'] * $qty;
                    $items_for_json[] = [
                        'product_id' => $product['id'], 
                        'title' => $product['title'], 
                        'price' => $product['price'], 
                        'quantity' => $qty
                    ];
                }

                $payment_info = ($payment_method === 'card') ? "Карта" : "При отриманні";
                $sql = "INSERT INTO orders (user_id, customer_name, phone, address, total_price, items_json, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'new')";
                
                $stmt_insert = $pdo->prepare($sql);
                $json_data = json_encode($items_for_json, JSON_UNESCAPED_UNICODE);
                $final_details = $full_address . " | Оплата: " . $payment_info;

                if ($stmt_insert->execute([$_SESSION['user_id'] ?? null, $name, $phone, $final_details, $total_price, $json_data])) {
                    unset($_SESSION['cart']);
                    $success = true;
                }
            } catch (Exception $e) { 
                $error = "Помилка бази даних: " . $e->getMessage(); 
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформлення замовлення</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fcfaf8;
            margin: 0;
            padding: 0;
            color: #333;
        }

        * { box-sizing: border-box; }

        .checkout-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 30px;
            align-items: start;
        }

        .section-title {
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            border-bottom: 2px solid #eeeae6;
            padding-bottom: 8px;
            color: #222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title:first-of-type { margin-top: 0; }

        .form-group { 
            position: relative; 
            margin-bottom: 15px; 
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 14px;
            margin-bottom: 6px;
            color: #666;
            font-weight: 500;
        }
        .form-group input, select#np_office {
            width: 100%; 
            padding: 12px; 
            border: 1px solid #dcd6d0; 
            border-radius: 6px; 
            background: white;
            font-size: 15px;
            outline: none;
            transition: all 0.25s ease;
        }
        .form-group input:focus, select#np_office:focus {
            border-color: #a11e1e;
            box-shadow: 0 0 0 3px rgba(161, 30, 30, 0.1);
        }

        .form-row { 
            display: flex; 
            gap: 15px; 
        }
        .form-row .form-group { 
            flex: 1; 
            min-width: 0; 
        }

        /* КНОПКИ-ТАБИ (Вибір доставки / Оплати) */
        .selector-box { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px; 
        }
        .selector-box .opt {
            flex: 1;
            padding: 14px;
            border: 1px solid #dcd6d0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            background: white;
            color: #333;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            position: relative;
            overflow: hidden;
        }
        .selector-box .opt:hover:not(.active) {
            background: #fff8f5;
            border-color: #a11e1e;
            color: #a11e1e;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(161, 30, 30, 0.05);
        }
        .selector-box .opt.active {
            background: #a11e1e;
            color: white;
            border-color: #a11e1e;
            box-shadow: 0 4px 12px rgba(161, 30, 30, 0.25);
            transform: scale(1.02);
        }
        .selector-box .opt:active {
            transform: scale(0.98) translateY(0);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #ddd;
            width: 100%;
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .result-item {
            padding: 12px 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            transition: background 0.15s, color 0.15s;
        }
        .result-item:hover { background: #f9f9f9; color: #a11e1e; }

        .error-msg { 
            color: #a11e1e; 
            background: #ffe6e6; 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            border: 1px solid #ffb3b3; 
            display: none; 
            font-size: 14px;
        }

        .hidden-block { display: none; }
        .hidden-block.active { display: block; }

        /* ШАПКА ТА ЛОГОТИП */
        .header-logo {
            background: #a11e1e;
            color: white;
            padding: 20px;
            transition: padding 0.3s ease;
        }
        .header-flex {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-logo h1 {
            color: #fff;
            font-weight: 900;
            text-transform: uppercase;
            font-style: italic;
            letter-spacing: 2px;
            margin: 0;
            font-size: 26px;
            transition: font-size 0.3s ease;
        }

/* Стилі при успішному замовленні */
.header-success {
    padding: 15px 0;
}
.header-success .header-flex {
    justify-content: center; /* Центруємо вміст всередині флексу */
    width: 100%;
}
        .header-success h1 {
            font-size: 42px; /* Збільшений логотип */
            letter-spacing: 4px;
        }

        /* СТИЛІ ДЛЯ УСПІШНОГО БЛОКУ */
        .success-card {
            text-align: center; 
            padding: 60px 40px; 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 700px;
            margin: 40px auto;
            border: 1px solid #eeeae6;
        }
        .success-card h2 {
            color: #28a745; 
            font-size: 36px; /* Значно збільшений шрифт */
            font-weight: 800;
            letter-spacing: 1px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .success-card p {
            color: #555;
            font-size: 18px;
            margin-bottom: 35px;
        }

        /* КНОПКА НА ГОЛОВНУ */
        .btn-back {
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 14px; 
            border: 1px solid rgba(255,255,255,0.4); 
            padding: 8px 12px; 
            border-radius: 6px;
            transition: all 0.25s ease;
            display: inline-block;
        }
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateX(-3px);
        }
        .btn-back:active {
            transform: translateX(-1px) scale(0.97);
        }

        .summary-card {
            background: #f4f0ec; 
            padding: 25px; 
            border-radius: 15px;
            position: sticky;
            top: 20px;
            border: 1px solid #e6dfd8;
        }

        .btn-submit {
            width: 100%; 
            background: #a11e1e; 
            color: white; 
            border: none; 
            padding: 16px; 
            border-radius: 30px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 16px; 
            letter-spacing: 0.5px; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px rgba(161, 30, 30, 0.2);
            position: relative;
        }
        .btn-submit:hover {
            background: #bc2323;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 30, 30, 0.35);
        }
        .btn-submit:active {
            transform: translateY(1px) scale(0.98);
            box-shadow: 0 2px 8px rgba(161, 30, 30, 0.2);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .checkout-container { padding: 15px; margin: 10px auto; }
            .header-logo h1 { font-size: 22px; }
            .header-success h1 { font-size: 32px; }
            .success-card h2 { font-size: 28px; }
            .summary-card { position: static; margin-top: 10px; }
        }

        @media (max-width: 480px) {
            #home_fields .form-row, 
            .checkout-left .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-row .form-group { width: 100%; }
            #card_fields .form-row { flex-direction: row; gap: 10px; }
        }
    </style>
</head>
<body>

<div class="header-logo <?php echo $success ? 'header-success' : ''; ?>">
    <div class="header-flex">
        <h1>Craft Box</h1>
        <?php if (!$success): ?>
            <a href="index.php" class="btn-back">← НА ГОЛОВНУ</a>
        <?php endif; ?>
    </div>
</div>

<div class="checkout-container">
    <?php if ($success): ?>
        <div class="success-card">
            <h2>ЗАМОВЛЕННЯ ПРИЙНЯТО!</h2>
            <p>Дякуємо за покупку! Ми зателефонуємо вам найближчим часом для підтвердження.</p>
            <a href="index.php" style="text-decoration: none; background: #a11e1e; color: white; padding: 15px 35px; border-radius: 25px; font-weight: bold; display: inline-block; transition: all 0.2s; box-shadow: 0 4px 10px rgba(161, 30, 30, 0.2);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'" onmousedown="this.style.transform='scale(0.98)'">НА ГОЛОВНУ</a>
        </div>
    <?php else: ?>
        
        <div id="error_container" class="error-msg" <?php echo $error ? 'style="display:block;"' : ''; ?>>
            <?php echo $error; ?>
        </div>

        <form method="POST" id="mainOrderForm">
            <div class="checkout-grid">
                <div class="checkout-left">
                    <span class="section-title">1. Контактні дані</span>
                    <div class="form-group">
                        <label>Прізвище, Ім'я *</label>
                        <input type="text" name="name" id="name_input" placeholder="Введіть дані" value="<?php echo htmlspecialchars($name ?? ''); ?>" required minlength="2">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Телефон *</label>
                            <input type="text" name="phone" id="phone_input" value="<?php echo htmlspecialchars($phone ?? '+380'); ?>" maxlength="13" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="email@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>

                    <span class="section-title">2. Спосіб доставки</span>
                    <div class="selector-box">
                        <label class="opt active" onclick="toggleTab('delivery', 'np', this)">
                            <input type="radio" name="delivery_type" id="del_np" value="np" checked style="display:none;"> Нова Пошта
                        </label>
                        <label class="opt" onclick="toggleTab('delivery', 'home', this)">
                            <input type="radio" name="delivery_type" id="del_home" value="home" style="display:none;"> Кур'єр (Адреса)
                        </label>
                    </div>

                    <div id="np_fields" class="hidden-block active">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Місто *</label>
                                <input type="text" id="np_city_search" placeholder="Почніть вводити..." autocomplete="off">
                                <div id="city_results" class="search-results"></div>
                                <input type="hidden" name="np_city_ref" id="np_city_ref">
                                <input type="hidden" name="np_city_name" id="np_city_name">
                            </div>
                            <div class="form-group">
                                <label>Відділення / Поштомат *</label>
                                <select name="np_office" id="np_office" disabled>
                                    <option value="">Оберіть відділення</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="home_fields" class="hidden-block">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Місто *</label>
                                <input type="text" name="home_city" id="home_city" placeholder="Місто">
                            </div>
                            <div class="form-group">
                                <label>Вулиця *</label>
                                <input type="text" name="home_street" id="home_street" placeholder="Вулиця">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Будинок *</label>
                                <input type="text" name="home_house" id="home_house" placeholder="Буд.">
                            </div>
                            <div class="form-group">
                                <label>Квартира</label>
                                <input type="text" name="home_flat" id="home_flat" placeholder="Кв.">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-right">
                    <span class="section-title">3. Спосіб оплати</span>
                    <div class="selector-box">
                        <label class="opt active" onclick="toggleTab('payment', 'cod', this)">
                            <input type="radio" name="payment_method" id="pay_cod" value="cod" checked style="display:none;"> При отриманні
                        </label>
                        <label class="opt" onclick="toggleTab('payment', 'card', this)">
                            <input type="radio" name="payment_method" id="pay_card" value="card" style="display:none;"> Банківська карта
                        </label>
                    </div>

                    <div id="card_fields" class="hidden-block">
                        <div class="form-group">
                            <label>Номер карти</label>
                            <input type="text" id="card_num" placeholder="0000 0000 0000 0000" maxlength="19">
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>ММ/РР</label><input type="text" id="card_date" placeholder="12/25" maxlength="5"></div>
                            <div class="form-group"><label>CVV</label><input type="text" id="card_cvv" placeholder="123" maxlength="3"></div>
                        </div>
                    </div>

                    <div class="summary-card">
                        <h4 style="margin: 0 0 10px 0; font-size: 13px; color: #777; font-weight: 600;">РАЗОМ ДО СПЛАТИ:</h4>
                        <?php
                        $total = 0;
                        if (!empty($_SESSION['cart'])) {
                            foreach ($_SESSION['cart'] as $id => $qty) {
                                $st = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                                $st->execute([$id]);
                                $row = $st->fetch();
                                if ($row) $total += $row['price'] * $qty;
                            }
                        }
                        ?>
                        <div class="total-amount" style="font-size: 32px; font-weight: 800; color: #a11e1e; margin-bottom: 25px;"><?php echo $total; ?> грн</div>
                        <button type="submit" class="btn-submit">ПІДТВЕРДИТИ</button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleTab(category, type, element) {
    const parent = element.closest('.selector-box');
    parent.querySelectorAll('.opt').forEach(opt => opt.classList.remove('active'));
    element.classList.add('active');
    element.querySelector('input').checked = true;

    if (category === 'delivery') {
        const isNp = (type === 'np');
        document.getElementById('np_fields').classList.toggle('active', isNp);
        document.getElementById('home_fields').classList.toggle('active', !isNp);

        document.getElementById('home_city').required = !isNp;
        document.getElementById('home_street').required = !isNp;
        document.getElementById('home_house').required = !isNp;
    }
    if (category === 'payment') {
        document.getElementById('card_fields').classList.toggle('active', type === 'card');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const citySearch = document.getElementById('np_city_search');
    const cityResults = document.getElementById('city_results');
    const officeSelect = document.getElementById('np_office');
    const form = document.getElementById('mainOrderForm');
    const errorContainer = document.getElementById('error_container');

    if(form) {
        form.onsubmit = function(e) {
            let errors = [];
            const phone = document.getElementById('phone_input').value;
            const isCard = document.getElementById('pay_card').checked;
            const isHomeDelivery = document.getElementById('del_home').checked;

            if (phone.length < 13) {
                errors.push("Будь ласка, введіть повний номер телефону.");
            }

            if (isHomeDelivery) {
                const hCity = document.getElementById('home_city').value.trim();
                const hStreet = document.getElementById('home_street').value.trim();
                const hHouse = document.getElementById('home_house').value.trim();

                if (!hCity || !hStreet || !hHouse) {
                    errors.push("Заповніть обов'язкові поля адреси доставки (Місто, Вулиця, Будинок).");
                }
            } else {
                const npCity = document.getElementById('np_city_ref').value;
                const npOffice = officeSelect.value;
                if (!npCity || !npOffice) {
                    errors.push("Будь ласка, оберіть місто та відділення Нової Пошти зі списку.");
                }
            }

            if (isCard) {
                const cNum = document.getElementById('card_num').value.replace(/\s/g, '');
                const cDate = document.getElementById('card_date').value;
                const cCvv = document.getElementById('card_cvv').value;

                if (cNum.length < 16) errors.push("Введіть коректний номер карти.");
                if (cDate.length < 5) errors.push("Введіть термін дії карти (ММ/РР).");
                if (cCvv.length < 3) errors.push("Введіть код CVV.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                errorContainer.innerHTML = errors.join('<br>');
                errorContainer.style.display = 'block';
                window.scrollTo({top: 0, behavior: 'smooth'});
                return false;
            }
        };
    }

    if(citySearch) {
        citySearch.addEventListener('input', function() {
            let val = this.value.trim();
            document.getElementById('np_city_ref').value = '';
            document.getElementById('np_city_name').value = '';
            officeSelect.innerHTML = '<option value="">Оберіть відділення</option>';
            officeSelect.disabled = true;

            if (val.length < 2) {
                cityResults.innerHTML = '';
                return;
            }

            fetch(`np_api.php?action=getCities&q=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(res => {
                    let html = '';
                    if (res.data && res.data.length > 0) {
                        res.data.forEach(city => {
                            html += `<div class="result-item" data-ref="${city.Ref}" data-name="${city.Description}">${city.Description} (${city.AreaDescription})</div>`;
                        });
                    }
                    cityResults.innerHTML = html;
                }).catch(err => console.log(err));
        });
    }

    if(cityResults) {
        cityResults.addEventListener('click', function(e) {
            const item = e.target.closest('.result-item');
            if (item) {
                const ref = item.dataset.ref;
                const name = item.dataset.name;
                citySearch.value = name;
                document.getElementById('np_city_ref').value = ref;
                document.getElementById('np_city_name').value = name;
                cityResults.innerHTML = '';
                loadWarehouses(ref);
            }
        });
    }

    function loadWarehouses(cityRef) {
        if(!officeSelect) return;
        officeSelect.disabled = true;
        officeSelect.innerHTML = '<option>Завантаження...</option>';
        fetch(`np_api.php?action=getWarehouses&cityRef=${cityRef}`)
            .then(r => r.json())
            .then(res => {
                let html = '<option value="">Оберіть відділення...</option>';
                if (res.data) {
                    res.data.forEach(wh => {
                        html += `<option value="${wh.Description}">${wh.Description}</option>`;
                    });
                }
                officeSelect.innerHTML = html;
                officeSelect.disabled = false;
            }).catch(err => console.log(err));
    }

    document.addEventListener('click', (e) => {
        if (citySearch && !citySearch.contains(e.target)) cityResults.innerHTML = '';
    });

    const phoneInput = document.getElementById('phone_input');
    if(phoneInput) {
        phoneInput.addEventListener('input', function (e) {
            if (!e.target.value.startsWith('+380')) e.target.value = '+380';
            e.target.value = '+' + e.target.value.replace(/[^\d]/g, '').substring(0, 12);
        });
    }

    const cardNum = document.getElementById('card_num');
    if(cardNum) {
        cardNum.addEventListener('input', function (e) {
            let target = e.target;
            let position = target.selectionEnd;
            let length = target.value.length;
            target.value = target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
            target.selectionEnd = position + (target.value.length > length ? 1 : 0);
        });
    }

    const cardDate = document.getElementById('card_date');
    if (cardDate) {
        cardDate.addEventListener('input', function (e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value.length > 2) {
                e.target.value = value.substring(0, 2) + '/' + value.substring(2, 4);
            } else {
                e.target.value = value;
            }
        });

        cardDate.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && e.target.value.length === 3) {
                e.target.value = e.target.value.substring(0, 2);
            }
        });
    }
});
</script>
</body>
</html>