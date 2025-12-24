<?php
require_once 'config.php';

$message = '';
$error = '';

// обрабатываем отправку формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $title = trim($_POST['title'] ?? '');

    // проверяем заполнение полей
    if (empty($name) || empty($email) || empty($title)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Неверный формат email';
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ошибка загрузки файла';
    } else {
        $file = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];

        // проверяем тип файла
        if (!in_array($file['type'], $allowed)) {
            $error = 'Разрешены только изображения JPG, PNG, GIF';
        } else {
            // создаем папки если их нет
            if (!file_exists('uploads')) mkdir('uploads', 0777, true);
            if (!file_exists('uploads/thumbnails')) mkdir('uploads/thumbnails', 0777, true);

            // генерируем уникальное имя файла
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $ext;
            $filepath = 'uploads/' . $filename;
            $thumbpath = 'uploads/thumbnails/' . $filename;

            // перемещаем загруженный файл
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // создаем миниатюру
                createThumbnail($filepath, $thumbpath, 200, 200);

                try {
                    $pdo = getDB();

                    // добавляем или находим пользователя
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        $userId = $user['id'];
                    } else {
                        // создаем нового пользователя
                        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                        $stmt->execute([$name, $email]);
                        $userId = $pdo->lastInsertId();
                    }

                    // добавляем фотографию в базу
                    $stmt = $pdo->prepare("INSERT INTO photos (user_id, filename, thumbnail, title) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $filename, $filename, $title]);

                    $message = 'Фотография успешно загружена!';

                    // очищаем форму
                    $_POST = [];

                } catch (PDOException $e) {
                    $error = 'Ошибка базы данных: ' . $e->getMessage();
                }
            } else {
                $error = 'Ошибка при сохранении файла';
            }
        }
    }
}

// функция создания миниатюры
function createThumbnail($source, $dest, $maxWidth, $maxHeight) {
    // получаем информацию об изображении
    list($width, $height, $type) = getimagesize($source);

    // создаем изображение из файла в зависимости от типа
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    // вычисляем новые размеры с сохранением пропорций
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    // создаем новое изображение
    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    // для png сохраняем прозрачность
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    // изменяем размер изображения
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // сохраняем миниатюру
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $dest, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $dest, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $dest);
            break;
    }

    // освобождаем память
    imagedestroy($image);
    imagedestroy($thumb);

    return true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка фотографии</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container upload-container">
    <div class="photo-container">
        <h1>Загрузить фотографию</h1>
        <div class="nav" style="text-align: center; margin-bottom: 30px;">
            <a href="index.php">← Вернуться к галерее</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Ваше имя:</label>
                <input type="text" id="name" name="name" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="title">Название фотографии:</label>
                <input type="text" id="title" name="title" required
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="photo">Выберите фотографию (JPG, PNG, GIF):</label>
                <input type="file" id="photo" name="photo" accept="image/*" required>
            </div>

            <button type="submit">Загрузить фотографию</button>
        </form>
    </div>
</div>
</body>
</html>