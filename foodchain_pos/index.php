<?php

require 'config.php';
auth();

$stats = $pdo->query("
    SELECT
        COUNT(*) AS orders,
        COALESCE(SUM(total), 0) AS revenue
    FROM sales
    WHERE created_at >= CURDATE()
    AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch();

$products = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE active = 1
")->fetchColumn();

$low = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE active = 1
    AND stock <= low_stock_threshold
")->fetchColumn();

page('FoodChain Dashboard');

?>

<div class="row g-3">

    <div class="col-md-3">
        <div class="card p-3">
            <p>Today's Sales</p>
            <h3><?= money($stats['revenue']) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <p>Today's Orders</p>
            <h3><?= $stats['orders'] ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <p>Active Products</p>
            <h3><?= $products ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <p>Low Stock Items</p>
            <h3><?= $low ?></h3>
        </div>
    </div>

</div>

<?php endpage(); ?>
