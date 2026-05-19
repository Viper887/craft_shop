<?php 
require 'config.php'; 

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    die("Доступ заборонено. Тільки для продавців.");
}

$success_msg = "";
$error_msg = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    // --- НОВІ ПОЛЯ ---
    $description = trim($_POST['description'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    // -----------------
    $cropped_image = $_POST['cropped_image'] ?? '';

    if(empty($title) || empty($price) || empty($cropped_image)) {
        $error_msg = "Будь ласка, заповніть усі обов'язкові поля та додайте фото!";
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 50) {
        $error_msg = "Назва має бути від 3 до 50 символів!";
    } elseif ($price <= 0 || $price > 1000000) {
        $error_msg = "Ціна має бути в межах від 0.01 до 1,000,000 грн!";
    } else {
        $imgData = str_replace('data:image/png;base64,', '', $cropped_image);
        $imgData = str_replace(' ', '+', $imgData);
        $data = base64_decode($imgData);

        $fileName = time() . "_product.png";
        $path = "uploads/" . $fileName;

        if (!is_dir('uploads')) mkdir('uploads', 0777, true);

        if(file_put_contents($path, $data)) {
            // Оновлений SQL-запит з урахуванням нових колонок
            $stmt = $pdo->prepare("INSERT INTO products (seller_id, title, price, description, weight, image_path) VALUES (?,?,?,?,?,?)");
            if($stmt->execute([$_SESSION['user_id'], $title, $price, $description, $weight, $path])) {
                $success_msg = "Товар успішно додано!";
            } else {
                $error_msg = "Помилка при збереженні в базу даних.";
            }
        } else {
            $error_msg = "Помилка при збереженні файлу зображення.";
        }
    }
} 
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додати товар | Craft Box</title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        .add-product-body { background-color: #e6e2dd; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding-bottom: 50px; font-family: sans-serif; }
        .header-logo { width: 100%; background-color: #a11e1e; padding: 15px 0; text-align: center; color: white; margin-bottom: 40px; }
        .back-link { color: white; text-decoration: none; font-size: 14px; opacity: 0.8; }
        .add-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; box-sizing: border-box; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; outline: none; box-sizing: border-box; font-family: inherit; }
        .form-group textarea { height: 100px; resize: vertical; }
        
        #preview-container { 
            width: 100%; height: 250px; border: 2px dashed #ddd; border-radius: 10px; 
            margin-top: 15px; display: flex; align-items: center; justify-content: center; 
            overflow: hidden; background: #f9f9f9; position: relative;
        }
        #preview-img { max-width: 100%; max-height: 100%; display: none; object-fit: contain; }
        .placeholder-text { color: #aaa; font-size: 14px; text-align: center; padding: 10px; }
        .btn-file { border: 1px solid #000; padding: 12px; border-radius: 10px; width: 100%; display: block; text-align: center; cursor: pointer; font-weight: bold; }

        #cropper-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; flex-direction: column;
        }
        .cropper-container-box { width: 95%; max-width: 600px; background: white; padding: 20px; border-radius: 15px; }
        .cropper-area { max-height: 60vh; margin-bottom: 20px; overflow: hidden; }
        .submit-btn, .crop-save-btn { background-color: #a11e1e; color: white; border: none; padding: 15px; border-radius: 25px; width: 100%; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .error-banner { color: #a11e1e; background: #fdeaea; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 15px; border: 1px solid #a11e1e; }
        .success-banner { color: #155724; background: #d4edda; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 15px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body class="add-product-body">

    <div class="header-logo">
        <h1>Craft Box</h1>
        <a href="seller_profile.php?id=<?= $_SESSION['user_id'] ?>" class="back-link">← Назад до профілю</a>
    </div>

    <div class="add-card">
        <?php if($success_msg): ?>
            <div class="success-banner"><?= $success_msg ?></div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="error-banner"><?= $error_msg ?></div>
        <?php endif; ?>

        <form id="product-form" method="POST">
            <div class="form-group">
                <label>Назва товару (50 символів)</label>
                <input type="text" name="title" required minlength="3" maxlength="50" placeholder="Введіть назву" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Вага / Грамовка (напр. 250 г)</label>
                <input type="text" name="weight" placeholder="250 г" value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Ціна (грн) (від 0,01 до 1,000,000)</label>
                <input type="number" name="price" step="0.01" min="0.01" max="1000000" required placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Опис товару</label>
                <textarea name="description" placeholder="Детально опишіть ваш товар..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Фото товару</label>
                <label for="image-input" class="btn-file">Обрати фото</label>
                <input type="file" id="image-input" accept="image/*" style="display:none;">
                <input type="hidden" name="cropped_image" id="cropped_image_input">
                
                <div id="preview-container">
                    <img id="preview-img" src="#" alt="Прев'ю">
                    <span class="placeholder-text">Тут з'явиться ваше обрізане фото</span>
                </div>
            </div>

            <button type="submit" class="submit-btn">Додати товар</button>
        </form>
    </div>

    <div id="cropper-modal">
        <div class="cropper-container-box">
            <h3 style="margin-top:0;">Налаштуйте фото (вільна обрізка)</h3>
            <div class="cropper-area">
                <img id="cropper-img" style="max-width: 100%;">
            </div>
            <button type="button" class="crop-save-btn" id="save-crop">Зберегти область</button>
            <button type="button" style="background:none; border:none; color:gray; width:100%; margin-top:10px; cursor:pointer;" onclick="closeModal()">Скасувати</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        const imageInput = document.getElementById('image-input');
        const cropperModal = document.getElementById('cropper-modal');
        const cropperImg = document.getElementById('cropper-img');
        const previewImg = document.getElementById('preview-img');
        const placeholderText = document.querySelector('.placeholder-text');
        const croppedInput = document.getElementById('cropped_image_input');

        imageInput.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    cropperImg.src = event.target.result;
                    cropperModal.style.display = 'flex';
                    if (cropper) cropper.destroy();
                    cropper = new Cropper(cropperImg, {
                        aspectRatio: null, 
                        viewMode: 1,
                        autoCropArea: 1
                    });
                };
                reader.readAsDataURL(files[0]);
            }
        });

        document.getElementById('save-crop').addEventListener('click', function() {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({
                maxWidth: 1000, 
                maxHeight: 1000
            });
            const base64Image = canvas.toDataURL('image/png');
            previewImg.src = base64Image;
            previewImg.style.display = 'block';
            placeholderText.style.display = 'none';
            croppedInput.value = base64Image;
            closeModal();
        });

        function closeModal() {
            cropperModal.style.display = 'none';
            imageInput.value = ''; 
        }
    </script>
</body>
</html>