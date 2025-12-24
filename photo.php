<?php
require_once 'config.php';

// получаем id фотографии из параметра
$photoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($photoId <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = getDB();

    // увеличиваем счетчик просмотров
    $stmt = $pdo->prepare("UPDATE photos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$photoId]);

    // получаем информацию о фотографии
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name, u.email as user_email
        FROM photos p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();

    // если фотография не найдена перенаправляем на главную
    if (!$photo) {
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($photo['title']) ?> - Галерея</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="index.php">← Вернуться к галерее</a>
    </div>

    <div class="photo-container">
        <h1 class="photo-title"><?= htmlspecialchars($photo['title']) ?></h1>

        <div class="photo-wrapper">
            <img src="uploads/<?= htmlspecialchars($photo['filename']) ?>"
                 alt="<?= htmlspecialchars($photo['title']) ?>"
                 class="photo-img">
        </div>

        <div class="photo-details">
            <div class="detail-row">
                <span class="detail-label">Автор:</span>
                <span class="detail-value"><?= htmlspecialchars($photo['user_name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($photo['user_email']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Дата загрузки:</span>
                <span class="detail-value">
                        <?= date('d.m.Y H:i', strtotime($photo['uploaded_at'])) ?>
                    </span>
            </div>
        </div>

        <div class="views-count">
            Просмотров: <?= $photo['views'] ?>
        </div>
    </div>
</div>
</body>
</html>