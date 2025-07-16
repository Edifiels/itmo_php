<?php
/**
 * Система анализа продаж
 */

$sales = [
    ["date" => "2025-07-01", "product" => "Телефон", "amount" => 25000, "quantity" => 1, "category" => "Электроника"],
    ["date" => "2025-07-01", "product" => "Книга", "amount" => 500, "quantity" => 2, "category" => "Книги"],
    ["date" => "2025-07-02", "product" => "Ноутбук", "amount" => 50000, "quantity" => 1, "category" => "Электроника"],
    ["date" => "2025-07-02", "product" => "Стол", "amount" => 15000, "quantity" => 1, "category" => "Мебель"],
    ["date" => "2025-07-03", "product" => "Планшет", "amount" => 30000, "quantity" => 1, "category" => "Электроника"],
    ["date" => "2025-07-03", "product" => "Стул", "amount" => 5000, "quantity" => 2, "category" => "Мебель"]
];

// 1. Общая статистика (цикл foreach)
$totalSales = 0;
$totalQuantity = 0;
$salesCount = count($sales);

foreach ($sales as $sale) {
    $totalSales += $sale["amount"];
    $totalQuantity += $sale["quantity"];
}

echo "<h2>Общая статистика</h2>";
echo "<ul>";
echo "<li>Общая сумма: " . number_format($totalSales) . " руб.</li>";
echo "<li>Товаров продано: $totalQuantity шт.</li>";
echo "<li>Средний чек: " . number_format($totalSales / $salesCount) . " руб.</li>";
echo "</ul>";

// 2. Статистика по категориям (foreach + условия)
$categoryStats = [];
foreach ($sales as $sale) {
    $category = $sale["category"];
    
    if (!isset($categoryStats[$category])) {
        $categoryStats[$category] = [
            "total_amount" => 0,
            "total_quantity" => 0,
            "count" => 0
        ];
    }
    
    $categoryStats[$category]["total_amount"] += $sale["amount"];
    $categoryStats[$category]["total_quantity"] += $sale["quantity"];
    $categoryStats[$category]["count"]++;
}

echo "<h2>Статистика по категориям</h2>";
foreach ($categoryStats as $category => $stats) {
    $avgCheck = $stats["total_amount"] / $stats["count"];
    echo "<p><strong>$category:</strong> ";
    echo number_format($stats["total_amount"]) . " руб., ";
    echo "средний чек: " . number_format($avgCheck) . " руб.</p>";
}

// 3. ТОП-5 продаж (for + сортировка пузырьком)
$sortedSales = $sales;
$n = count($sortedSales);

for ($i = 0; $i < $n - 1; $i++) {
    for ($j = 0; $j < $n - $i - 1; $j++) {
        if ($sortedSales[$j]["amount"] < $sortedSales[$j + 1]["amount"]) {
            // Обмен элементов
            $temp = $sortedSales[$j];
            $sortedSales[$j] = $sortedSales[$j + 1];
            $sortedSales[$j + 1] = $temp;
        }
    }
}

echo "<h2>ТОП-5 самых дорогих продаж</h2>";
echo "<ol>";
for ($i = 0; $i < min(5, count($sortedSales)); $i++) {
    $sale = $sortedSales[$i];
    echo "<li>{$sale['product']} - " . number_format($sale['amount']) . " руб. ({$sale['date']})</li>";
}
echo "</ol>";

// 4. Поиск аномалий (while + break/continue)
$averageSale = $totalSales / $salesCount;
$threshold = $averageSale * 2;
$anomalies = [];
$i = 0;

while ($i < count($sales)) {
    $sale = $sales[$i];
    
    // Пропускаем мелкие продажи
    if ($sale["amount"] < 1000) {
        $i++;
        continue;
    }
    
    // Если найдена аномалия
    if ($sale["amount"] > $threshold) {
        $anomalies[] = $sale;
        
        // Если найдено 3 аномалии, прекращаем поиск
        if (count($anomalies) >= 3) {
            break;
        }
    }
    
    $i++;
}

if (!empty($anomalies)) {
    echo "<h2>Аномально высокие продажи</h2>";
    echo "<ul>";
    foreach ($anomalies as $anomaly) {
        echo "<li>{$anomaly['date']}: {$anomaly['product']} - " . number_format($anomaly['amount']) . " руб.</li>";
    }
    echo "</ul>";
}
?>