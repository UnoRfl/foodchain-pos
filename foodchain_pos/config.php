<?php
session_start();

date_default_timezone_set('Asia/Manila');

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=foodchain_pos;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

function h($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function money($value) {
    return '₱' . number_format((float)$value, 2);
}

function auth() {
    if (empty($_SESSION['uid'])) {
        header('Location: login.php');
        exit;
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function page($title) {
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1">

        <title>' . h($title) . '</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    </head>

    <body class="bg-light">

    <nav class="navbar navbar-expand navbar-dark bg-dark px-3">

        <a class="navbar-brand" href="index.php">
            FoodChain POS
        </a>

        <div class="navbar-nav">
            <a class="nav-link" href="index.php">Dashboard</a>
            <a class="nav-link" href="pos.php">POS</a>
            <a class="nav-link" href="products.php">Inventory</a>
            <a class="nav-link" href="transactions.php">Transactions</a>
            <a class="nav-link" href="reports.php">Reports</a>
            <a class="nav-link" href="logout.php">Logout</a>
        </div>

    </nav>

    <main class="container py-4">

    <h2 class="mb-4">' . h($title) . '</h2>
    ';
}

function endpage() {
    echo '
    </main>
    </body>
    </html>
    ';
}
?>
