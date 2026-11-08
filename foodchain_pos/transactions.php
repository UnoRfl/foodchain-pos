<?php

require 'config.php';
auth();

$date = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM sales
    WHERE created_at >= ?
    AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY created_at DESC
");

$stmt->execute([$date, $date]);

page('Daily Transactions');

?>

<form class="mb-3">

    <input
        type="date"
        name="date"
        value="<?= h($date) ?>"
    >

    <button class="btn btn-primary">
        Search
    </button>

</form>

<div class="table-responsive">

<table class="table table-striped">

<tr>
    <th>Transaction No.</th>
    <th>Date and Time</th>
    <th>Payment Method</th>
    <th>Total Amount</th>
    <th>Receipt</th>
</tr>

<?php

$sum = 0;

foreach ($stmt as $sale):

    $sum += (float)$sale['total'];

?>

<tr>

    <td><?= $sale['id'] ?></td>

    <td><?= h($sale['created_at']) ?></td>

    <td><?= h($sale['payment_method']) ?></td>

    <td><?= money($sale['total']) ?></td>

    <td>
        <a href="receipt.php?id=<?= $sale['id'] ?>">
            View Receipt
        </a>
    </td>

</tr>

<?php endforeach; ?>

</table>

</div>

<h4>
    Daily Sales Total: <?= money($sum) ?>
</h4>

<?php endpage(); ?>
