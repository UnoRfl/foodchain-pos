<?php

require 'config.php';
auth();

$month = $_GET['month'] ?? date('Y-m');

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}

$start = $month . '-01';

$end = date(
    'Y-m-d',
    strtotime($start . ' +1 month')
);

$stmt = $pdo->prepare("
    SELECT
        DATE(created_at) AS day,
        COUNT(*) AS orders,
        SUM(total) AS total

    FROM sales

    WHERE created_at >= ?
    AND created_at < ?

    GROUP BY DATE(created_at)

    ORDER BY day
");

$stmt->execute([$start, $end]);

page('Sales Summary and Reports');

?>

<form class="mb-4">

    <label>Select Month</label>

    <input
        type="month"
        name="month"
        value="<?= h($month) ?>"
    >

    <button class="btn btn-primary">
        Generate Report
    </button>

</form>

<div class="table-responsive">

<table class="table table-bordered table-striped">

<tr>
    <th>Date</th>
    <th>Number of Orders</th>
    <th>Total Sales</th>
</tr>

<?php

$total = 0;
$orders = 0;

foreach ($stmt as $row):

    $total += (float)$row['total'];
    $orders += (int)$row['orders'];

?>

<tr>

    <td><?= h($row['day']) ?></td>

    <td><?= $row['orders'] ?></td>

    <td><?= money($row['total']) ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>

<div class="card p-4">

    <h4>Monthly Sales Summary</h4>

    <h5>Total Orders: <?= $orders ?></h5>

    <h3>Total Revenue: <?= money($total) ?></h3>

</div>

<?php endpage(); ?>
