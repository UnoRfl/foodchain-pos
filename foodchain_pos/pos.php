<?php

require 'config.php';
auth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $items = json_decode(
            $_POST['cart'] ?? '',
            true
        );

        if (!is_array($items) || empty($items)) {
            throw new Exception("Cart is empty.");
        }

        $method = $_POST['payment_method'] ?? '';

        if (!in_array($method, ['Cash', 'GCash'], true)) {
            throw new Exception("Invalid payment method.");
        }

        $paid = (int)round(
            (float)($_POST['amount_paid'] ?? 0) * 100
        );

        $pdo->beginTransaction();

        $lines = [];
        $total = 0;

        foreach ($items as $id => $qty) {

            $id = (int)$id;
            $qty = filter_var(
                $qty,
                FILTER_VALIDATE_INT
            );

            if ($id <= 0 || $qty === false || $qty < 1) {
                throw new Exception("Invalid quantity.");
            }

            $stmt = $pdo->prepare("
                SELECT *
                FROM products
                WHERE id = ?
                AND active = 1
                FOR UPDATE
            ");

            $stmt->execute([$id]);

            $product = $stmt->fetch();

            if (!$product || $product['stock'] < $qty) {
                throw new Exception(
                    "Insufficient product stock."
                );
            }

            $unit = (int)round(
                (float)$product['price'] * 100
            );

            $subtotal = $unit * $qty;

            $total += $subtotal;

            $lines[] = [
                $product,
                $qty,
                $unit,
                $subtotal
            ];
        }

        if ($paid < $total) {
            throw new Exception("Insufficient payment.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO sales
            (
                total,
                payment_method,
                amount_paid,
                change_due,
                user_id
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $total / 100,
            $method,
            $paid / 100,
            ($paid - $total) / 100,
            $_SESSION['uid']
        ]);

        $saleId = (int)$pdo->lastInsertId();

        foreach ($lines as $line) {

            [$product, $qty, $unit, $subtotal] = $line;

            $stmt = $pdo->prepare("
                INSERT INTO sale_items
                (
                    sale_id,
                    product_id,
                    product_name,
                    quantity,
                    unit_price,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $saleId,
                $product['id'],
                $product['name'],
                $qty,
                $unit / 100,
                $subtotal / 100
            ]);

            $stmt = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
            ");

            $stmt->execute([
                $qty,
                $product['id']
            ]);

            $stmt = $pdo->prepare("
                INSERT INTO stock_movements
                (product_id, change_qty, reason)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $product['id'],
                -$qty,
                'Sale #' . $saleId
            ]);
        }

        $pdo->commit();

        redirect('receipt.php?id=' . $saleId);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}

page('Point of Sale');

if ($error) {
    echo '<div class="alert alert-danger">'
        . h($error) .
        '</div>';
}

$products = $pdo->query("
    SELECT id, name, category, price, stock
    FROM products
    WHERE active = 1 AND stock > 0
    ORDER BY category, name
")->fetchAll();

?>

<div class="row">

    <div class="col-lg-7">

        <h4>Available Food Products</h4>

        <div class="row g-2">

            <?php foreach ($products as $p): ?>

                <div class="col-md-6">

                    <div class="card p-3">

                        <h5><?= h($p['name']) ?></h5>

                        <p>
                            <?= h($p['category']) ?>
                            <br>
                            Stock: <?= $p['stock'] ?>
                            <br>
                            Price: <?= money($p['price']) ?>
                        </p>

                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="add(<?= (int)$p['id'] ?>)"
                        >
                            Add to Cart
                        </button>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <div class="col-lg-5">

        <div class="card p-3">

            <h4>Customer Order</h4>

            <div id="cartView"></div>

            <hr>

            <h3 id="total">₱0.00</h3>

            <form method="POST" onsubmit="return checkout()">

                <input
                    type="hidden"
                    name="cart"
                    id="cart"
                >

                <label>Payment Method</label>

                <select
                    name="payment_method"
                    class="form-select mb-3"
                >
                    <option>Cash</option>
                    <option>GCash</option>
                </select>

                <label>Amount Paid</label>

                <input
                    type="number"
                    name="amount_paid"
                    id="paid"
                    step="0.01"
                    min="0"
                    class="form-control mb-3"
                    required
                >

                <button class="btn btn-success w-100">
                    Complete Sale
                </button>

            </form>

        </div>

    </div>

</div>

<script>

const products = <?= json_encode(
    $products,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

const cart = {};

function add(id) {

    let p = products.find(x => +x.id === id);

    if ((cart[id] || 0) < +p.stock) {
        cart[id] = (cart[id] || 0) + 1;
    }

    render();
}

function change(id, amount) {

    cart[id] = (cart[id] || 0) + amount;

    if (cart[id] <= 0) {
        delete cart[id];
    }

    render();
}

function render() {

    let total = 0;
    let html = "";

    for (let id in cart) {

        let p = products.find(x => +x.id === +id);
        let qty = cart[id];

        total += Math.round(+p.price * 100) * qty;

        html += `
            <div class="d-flex justify-content-between mb-2">

                <span>${p.name} x ${qty}</span>

                <span>
                    <button type="button"
                    onclick="change(${id},-1)">-</button>

                    <button type="button"
                    onclick="add(${id})">+</button>
                </span>

            </div>
        `;
    }

    document.getElementById('cartView').innerHTML =
        html || "No items selected";

    document.getElementById('total').textContent =
        "₱" + (total / 100).toFixed(2);

    document.getElementById('cart').value =
        JSON.stringify(cart);
}

function checkout() {

    if (Object.keys(cart).length === 0) {
        alert("Please add products first.");
        return false;
    }

    return true;
}

render();

</script>

<?php endpage(); ?>
