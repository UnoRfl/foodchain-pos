<?php

require 'config.php';
auth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'add' || $action === 'edit') {

            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? -1);

            if ($name === '' || $category === '' || $price < 0) {
                throw new Exception("Invalid product information.");
            }

            if ($action === 'add') {

                $stock = (int)($_POST['stock'] ?? -1);

                if ($stock < 0) {
                    throw new Exception("Invalid stock.");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (name, category, price, stock)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name, $category, $price, $stock
                ]);

            } else {

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET name=?, category=?, price=?
                    WHERE id=?
                ");

                $stmt->execute([
                    $name, $category, $price, $id
                ]);
            }

        } elseif ($action === 'stock') {

            $qty = (int)($_POST['quantity'] ?? 0);

            if (!$id || !$qty) {
                throw new Exception("Invalid stock quantity.");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT stock FROM products
                WHERE id=? FOR UPDATE
            ");

            $stmt->execute([$id]);

            $old = $stmt->fetchColumn();

            if ($old === false || (int)$old + $qty < 0) {
                throw new Exception("Insufficient stock.");
            }

            $pdo->prepare("
                UPDATE products
                SET stock=stock+?
                WHERE id=?
            ")->execute([$qty, $id]);

            $pdo->prepare("
                INSERT INTO stock_movements
                (product_id, change_qty, reason)
                VALUES (?, ?, ?)
            ")->execute([
                $id, $qty, 'Manual adjustment'
            ]);

            $pdo->commit();

        } elseif ($action === 'disable') {

            $pdo->prepare("
                UPDATE products
                SET active=0
                WHERE id=?
            ")->execute([$id]);
        }

        redirect('products.php');

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}

page('Food Products and Inventory');

if ($error) {
    echo '<div class="alert alert-danger">'
        . h($error) .
        '</div>';
}

?>

<h4>Add New Food Product</h4>

<form method="POST" class="row g-2 mb-4">

    <input type="hidden" name="action" value="add">

    <div class="col-md-3">
        <input name="name"
        class="form-control"
        placeholder="Food Name" required>
    </div>

    <div class="col-md-2">
        <input name="category"
        class="form-control"
        placeholder="Category" required>
    </div>

    <div class="col-md-2">
        <input type="number" step="0.01" min="0"
        name="price"
        class="form-control"
        placeholder="Price" required>
    </div>

    <div class="col-md-2">
        <input type="number" min="0"
        name="stock"
        class="form-control"
        placeholder="Stock" required>
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary">
            Add Product
        </button>
    </div>

</form>

<h4>Inventory List</h4>

<div class="table-responsive">

<table class="table table-striped">

<tr>
    <th>Product</th>
    <th>Category</th>
    <th>Price</th>
    <th>Stock</th>
    <th>Adjust Stock</th>
    <th>Action</th>
</tr>

<?php

$products = $pdo->query("
    SELECT * FROM products
    WHERE active=1
    ORDER BY name
");

foreach ($products as $p):

?>

<tr>

    <td><?= h($p['name']) ?></td>

    <td><?= h($p['category']) ?></td>

    <td><?= money($p['price']) ?></td>

    <td>
        <?= $p['stock'] ?>

        <?php if ($p['stock'] <= $p['low_stock_threshold']): ?>
            <span class="badge bg-danger">Low Stock</span>
        <?php endif; ?>
    </td>

    <td>

        <form method="POST" class="d-flex gap-1">

            <input type="hidden" name="action" value="stock">

            <input type="hidden" name="id"
            value="<?= $p['id'] ?>">

            <input type="number"
            name="quantity"
            class="form-control form-control-sm"
            placeholder="+/-"
            required>

            <button class="btn btn-success btn-sm">
                Apply
            </button>

        </form>

    </td>

    <td>

        <form method="POST"
        onsubmit="return confirm('Disable this product?')">

            <input type="hidden" name="action" value="disable">

            <input type="hidden" name="id"
            value="<?= $p['id'] ?>">

            <button class="btn btn-danger btn-sm">
                Disable
            </button>

        </form>

    </td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php endpage(); ?>
