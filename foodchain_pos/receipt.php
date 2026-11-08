<?php

require 'config.php';
auth();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT * FROM sales WHERE id=?
");

$stmt->execute([$id]);

$sale = $stmt->fetch();

if (!$sale) {
    http_response_code(404);
    exit("Sale not found.");
}

$stmt = $pdo->prepare("
    SELECT * FROM sale_items
    WHERE sale_id=?
");

$stmt->execute([$id]);

page('Sales Receipt');

?>

<div class="card p-4" style="max-width:520px">

    <h3 class="text-center">FOODCHAIN POS</h3>

    <p class="text-center">
        Thank you for your purchase!
    </p>

    <hr>

    <p>
        Receipt No: <?= $sale['id'] ?><br>

        Date:
        <?= h($sale['created_at']) ?><br>

        Payment:
        <?= h($sale['payment_method']) ?>
    </p>

    <table class="table">

        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>

        <?php foreach ($stmt as $item): ?>

        <tr>

            <td><?= h($item['product_name']) ?></td>

            <td><?= $item['quantity'] ?></td>

            <td><?= money($item['subtotal']) ?></td>

        </tr>

        <?php endforeach; ?>

    </table>

    <hr>

    <h4>Total: <?= money($sale['total']) ?></h4>

    <p>
        Amount Paid: <?= money($sale['amount_paid']) ?><br>

        Change: <?= money($sale['change_due']) ?>
    </p>

    <button onclick="window.print()"
    class="btn btn-primary w-100">
        Print Receipt
    </button>

</div>

<?php endpage(); ?>
