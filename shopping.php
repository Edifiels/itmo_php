<?php
// Товары и их цены
$products = [
    "Хлеб" => 45.50,
    "Молоко" => 85.00,
    "Яйца" => 120.00,
    "Мясо" => 450.00,
    "Овощи" => 200.00,
    "Фрукты" => 180.00
];

// Подсчет статистики
$totalSum = array_sum($products);
$averagePrice = $totalSum / count($products);
$maxPrice = max($products);
$minPrice = min($products);

// Поиск самого дорогого товара
$expensiveProduct = array_search($maxPrice, $products);
$cheapProduct = array_search($minPrice, $products);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список покупок</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .total-row { background: #e6f3ff; font-weight: bold; }
        .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Список покупок</h1>
        
        <table>
            <tr>
                <th>Товар</th>
                <th>Цена (руб.)</th>
                <th>Процент от общей суммы</th>
            </tr>
            <?php foreach ($products as $product => $price): ?>
            <tr>
                <td><?= $product ?></td>
                <td><?= number_format($price, 2) ?></td>
                <td><?= round(($price / $totalSum) * 100, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>ИТОГО:</td>
                <td><?= number_format($totalSum, 2) ?></td>
                <td>100%</td>
            </tr>
        </table>
        
        <div class="stats">
            <div class="stat-card">
                <h3>📊 Статистика</h3>
                <p>Товаров: <?= count($products) ?></p>
                <p>Средняя цена: <?= number_format($averagePrice, 2) ?> руб.</p>
            </div>
            <div class="stat-card">
                <h3>💰 Диапазон цен</h3>
                <p>Самый дорогой: <?= $expensiveProduct ?> (<?= $maxPrice ?> руб.)</p>
                <p>Самый дешевый: <?= $cheapProduct ?> (<?= $minPrice ?> руб.)</p>
            </div>
        </div>
    </div>
</body>
</html>