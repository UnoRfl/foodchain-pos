<?php

require 'config.php';

if (!empty($_SESSION['uid'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE username = ?"
    );

    $stmt->execute([$username]);

    $user = $stmt->fetch();

    if ($user && password_verify(
        $password,
        $user['password_hash']
    )) {

        session_regenerate_id(true);

        $_SESSION['uid'] = $user['id'];

        redirect('index.php');

    } else {

        $error = "Invalid username or password.";

    }
}

page('Administrator Login');

if ($error) {
    echo '<div class="alert alert-danger">'
        . h($error) .
        '</div>';
}

?>

<div class="card p-4" style="max-width:420px">

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            class="form-control mb-3"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            class="form-control mb-3"
            required
        >

        <button class="btn btn-primary w-100">
            Login
        </button>

    </form>

</div>

<?php endpage(); ?>
