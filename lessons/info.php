<?php
// Объявление переменных
$firstName = "Иван";
$lastName = "Иванов";
$age = 25;
$city = "Москва";
$isStudent = true;
$salary = 75000.50;
$hobbies = ["Программирование", "Фотография", "Путешествия"];

// Вычисления
$fullName = $firstName . " " . $lastName;
$birthYear = date('Y') - $age;
$status = $isStudent ? "Студент" : "Работает";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Информация о пользователе</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .info-card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hobby { 
            display: inline-block; 
            background: #667eea; 
            color: white; 
            padding: 5px 10px; 
            margin: 3px; 
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="info-card">
        <h2>Карточка пользователя</h2>
        <p><strong>ФИО:</strong> <?php echo $fullName ?></p>
        <p><strong>Возраст:</strong> <?php echo $age ?> лет (рожден в <?php echo $birthYear ?> году)</p>
        <p><strong>Город:</strong> <?php echo $city ?></p>
        <p><strong>Статус:</strong> <?php echo $status ?></p>
        <p><strong>Зарплата:</strong> <?php echo number_format($salary, 2, ',', ' ') ?> руб.</p>
        
        <h3>Увлечения:</h3>
        <?php foreach ($hobbies as $hobby): ?>
            <span class="hobby"><?php echo $hobby ?></span>
        <?php endforeach; ?>
        
        <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
            Страница сгенерирована: <?php echo date('d.m.Y в H:i:s') ?>
        </p>
    </div>
</body>
</html>