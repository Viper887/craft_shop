<?php
require 'config.php'; 

$error = '';

// --- ОБРОБКА КЛАСИЧНОГО ВХОДУ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Введено невалідний формат email.";
    } elseif (mb_strlen($password) <= 8) {
        $error = "Пароль має містити більше 8 символів.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Невірний email або пароль.";
        }
    }
}

// --- ОБРОБКА ВХОДУ ТА РЕЄСТРАЦІЇ ЧЕРЕЗ GOOGLE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['google_jwt'])) {
    $jwt = $_POST['google_jwt'];
    
    // Перевіряємо, чи була передана роль (для нових реєстрацій)
    $chosen_role = (isset($_POST['user_role']) && in_array($_POST['user_role'], ['buyer', 'seller'])) 
                   ? $_POST['user_role'] 
                   : 'buyer';

    $token_parts = explode('.', $jwt);
    if (count($token_parts) === 3) {
        $payload = json_decode(base64_decode($token_parts[1]), true);
        $email = filter_var($payload['email'], FILTER_VALIDATE_EMAIL);
        $name = htmlspecialchars($payload['name']);

        if ($email) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // РЕЄСТРАЦІЯ: створюємо користувача з обраною роллю
                $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, 'google_auth')");
                $stmt->execute([$name, $email, $chosen_role]);
                $user_id = $pdo->lastInsertId();
                $role = $chosen_role;
            } else {
                // ВХІД: якщо користувач вже є, роль не змінюємо, беремо з бази
                $user_id = $user['id'];
                $role = $user['role'];
            }

            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;

            header("Location: index.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід — Craft Box</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="style.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="auth-container">
        <h2>Вхід до системи</h2>
        <?php if ($error) echo "<p style='color: #a31d1d; font-weight: bold;'>$error</p>"; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required minlength="9">
            <button type="submit">Увійти</button>
        </form>

        <div style="margin: 20px 0; text-align: center; color: #666; font-size: 14px;">або через Google</div>

        <div style="display: flex; justify-content: center;">
            <div id="g_id_onload"
                 data-client_id="216643206707-kbimtrv0iq154vu1pq5uag8hn4n4ajuo.apps.googleusercontent.com"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin" data-type="standard" data-theme="outline"></div>
        </div>
        
        <p style="margin-top: 20px;">Ще немає акаунту? <a href="register.php" style="color: #a31d1d; font-weight: bold;">Зареєструватися</a></p>
    </div>

    <!-- Прихована форма для Google (тут роль за замовчуванням buyer) -->
    <form id="google-auth-form" method="POST" style="display:none;">
        <input type="hidden" name="google_jwt" id="google_jwt">
        <input type="hidden" name="user_role" value="buyer"> 
    </form>

    <script>
    function handleCredentialResponse(response) {
        document.getElementById('google_jwt').value = response.credential;
        document.getElementById('google-auth-form').submit();
    }
    </script>
</body>
</html>