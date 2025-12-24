<?php
require_once 'config.php';

// получаем все фотографии отсортированные по популярности
try {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT p.*, u.name as user_name 
        FROM photos p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.views DESC, p.uploaded_at DESC
    ");
    $photos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Галерея фотографий</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Галерея фотографий</h1>
        <p style="color: #666; margin-bottom: 20px;">
            Всего фотографий: <?= count($photos) ?>
        </p>
        <a href="upload.php" class="upload-btn">➕ Добавить фотографию</a>
    </header>

    <?php if (empty($photos)): ?>
        <div class="empty">
            <h2>Галерея пока пуста</h2>
            <p style="color: #999;">Станьте первым, кто загрузит фотографию!</p>
        </div>
    <?php else: ?>
        <div class="gallery">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-card">
                    <a href="photo.php?id=<?= $photo['id'] ?>">
                        <img src="uploads/thumbnails/<?= htmlspecialchars($photo['thumbnail']) ?>"
                             alt="<?= htmlspecialchars($photo['title']) ?>"
                             class="photo-img">
                        <div class="photo-info">
                            <div class="photo-title">
                                <?= htmlspecialchars($photo['title']) ?>
                            </div>
                            <div class="photo-meta">
                                <span><?= htmlspecialchars($photo['user_name']) ?></span>
                                <span class="views"><?= $photo['views'] ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>