<?php 
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password_raw = $_POST['password'];
        $role = $_POST['role'];

        if (!$email) {
            $error = "Введено некоректний Email.";
        } elseif (mb_strlen($password_raw) <= 8) {
            $error = "Пароль має бути довшим за 8 символів.";
        } elseif (!in_array($role, ['buyer', 'seller'])) {
            $error = "Некоректно обрано роль.";
        } else {
            $pass = password_hash($password_raw, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $pass, $role]);
                header("Location: login.php");
                exit();
            } catch (PDOException $e) {
                $error = "Помилка: можливо, такий email вже зареєстрований.";
            }
        }
    } else {
        $error = "Будь ласка, заповніть усі поля!";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Реєстрація — Craft Box</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="auth-container">
        <h2>Реєстрація</h2>
        <?php if ($error) echo "<p style='color: #a31d1d; margin-bottom: 10px; font-weight: bold;'>$error</p>"; ?>
        
        <form method="POST">
            <input type="text" name="name" placeholder="Ім'я" required>
            <input type="email" name="email" placeholder="Email" required 
                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$">
            <input type="password" name="password" placeholder="Пароль (мінімум 9 символів)" required minlength="9">
            
            <!-- Додано id="role_select" для перехоплення значення через JS -->
            <select name="role" id="role_select">
                <option value="buyer">Я хочу купувати</option>
                <option value="seller">Я хочу продавати</option>
            </select>
            <button type="submit">Зареєструватися</button>
        </form>

        <div style="margin: 20px 0; text-align: center; color: #666; font-size: 14px;">або швидка реєстрація</div>

        <div style="display: flex; justify-content: center;">
            <div id="g_id_onload"
                 data-client_id="216643206707-kbimtrv0iq154vu1pq5uag8hn4n4ajuo.apps.googleusercontent.com"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin" data-type="standard" data-shape="rectangular"></div>
        </div>

        <p style="margin-top: 20px;">Вже є акаунт? <a href="login.php" style="color: #a31d1d; font-weight: bold;">Увійти</a></p>
    </div>

    <!-- Форма для відправки даних у login.php -->
    <form id="google-auth-form" action="login.php" method="POST" style="display:none;">
        <input type="hidden" name="google_jwt" id="google_jwt">
        <input type="hidden" name="user_role" id="google_role">
    </form>

    <script>
    function handleCredentialResponse(response) {
        // Отримуємо обрану роль із селектора
        const selectedRole = document.getElementById('role_select').value;
        
        document.getElementById('google_jwt').value = response.credential;
        document.getElementById('google_role').value = selectedRole;
        document.getElementById('google-auth-form').submit();
    }
    </script>
</body>
</html>