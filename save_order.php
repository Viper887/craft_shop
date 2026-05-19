<?php
require 'config.php'; // Підключення бази та session_start()

// 1. Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    die("Помилка: Ви повинні увійти в систему, щоб зробити замовлення.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $buyer_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $address = htmlspecialchars(trim($_POST['address']));

    // 2. Базова валідація
    if (empty($address) || $product_id <= 0) {
        die("Помилка: Некоректні дані замовлення.");
    }

    try {
        // 3. Запис замовлення в базу даних
        $sql = "INSERT INTO orders (buyer_id, product_id, address, status) 
                VALUES (:buyer_id, :product_id, :address, 'new')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':buyer_id'   => $buyer_id,
            ':product_id' => $product_id,
            ':address'    => $address
        ]);

        // 4. Успішне завершення
        echo "<!DOCTYPE html>
        <html lang='uk'>
        <head>
            <link rel='stylesheet' href='style.css'>
            <title>Замовлення прийнято</title>
            <link rel="icon" type="image/png" href="uploads/favicon.png">
        </head>
        <body>
            <div class='card' style='text-align: center;'>
                <h2 style='color: #4CAF50;'>Дякуємо за замовлення!</h2>
                <p>Ваш запит на крафтовий товар прийнято.</p>
                <p>Адреса доставки: <strong>$address</strong></p>
                <br>
                <a href='index.php' class='button'>Повернутися до каталогу</a>
            </div>
            <script>
                // Автоматичне повернення через 5 секунд
                setTimeout(function(){ window.location.href = 'index.php'; }, 5000);
            </script>
        </body>
        </html>";

    } catch (PDOException $e) {
        // Обробка помилок (наприклад, якщо товару вже немає)
        die("Помилка при збереженні замовлення: " . $e->getMessage());
    }
} else {
    // Якщо зайшли на файл не через POST-форму
    header("Location: index.php");
    exit();
}
?>