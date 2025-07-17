<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Моя первая PHP-страница</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #667eea; }
    </style>
</head>
<body>
    <h1><?php echo "Привет, мир!"; ?></h1>
    <p>Сегодня: <?php echo date('d.m.Y H:i:s'); ?></p>
    <p>Версия PHP: <?php echo phpversion() ?></p>
</body>
</html>