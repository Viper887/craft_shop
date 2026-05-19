<?php
header('Content-Type: text/plain; charset=utf-8');

echo "--- Спроба створити запит на google.com --- \n\n";

// Ініціалізація cURL для Google
$ch = curl_init('https://www.google.com');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Дозволяємо редіректи
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);     // Таймаут з'єднання (5 секунд)
curl_setopt($ch, CURLOPT_TIMEOUT, 5);            // Таймаут відповіді (5 секунд)

// Вимикаємо перевірку SSL (на випадок проблем із сертифікатами на сервері)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error_code = curl_errno($ch);
$curl_error_text = curl_error($ch);

curl_close($ch);

// Аналізуємо результат запиту
if ($curl_error_code !== 0) {
    echo "❌ Помилка cURL!\n";
    echo "Код помилки: " . $curl_error_code . "\n";
    echo "Текст помилки: " . $curl_error_text . "\n";
    echo "HTTP статус: " . $http_code . "\n";
    
    if ($curl_error_code === 7) {
        echo "\n💡 Порада: Код 7 (CURLE_COULDNT_CONNECT) означає, що хостинг повністю блокує вихідні запити. Тобі потрібно написати в підтримку хостингу, щоб вони відкрили порти для зовнішніх запитів (outgoing connections) або зняли обмеження брандмауера.";
    }
} else {
    echo "✅ Запит успішний!\n";
    echo "HTTP статус сервера Google: " . $http_code . "\n\n";
    echo "--- Перші 200 символів відповіді від Google: ---\n";
    echo substr($response, 0, 200) . "...\n";
}