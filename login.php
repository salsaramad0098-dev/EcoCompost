
<?php
require_once __DIR__ . '/config/app.php';

// Jika sudah login, arahkan sesuai hak akses
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'petugas') {
        go('petugas/dashboard.php');
    } else {
        go('dashboard.php');
    }
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $q = $pdo->prepare(
        'SELECT * FROM users WHERE username = ? LIMIT 1'
    );
    $q->execute([$username]);
    $u = $q->fetch();

    if ($u && password_verify($password, $u['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $u['id'];
        $_SESSION['nama'] = $u['nama'];
        $_SESSION['role'] = $u['role'];

        // Arahkan berdasarkan role akun
        if ($u['role'] === 'admin') {
            go('dashboard.php');
        } elseif ($u['role'] === 'petugas') {
            go('petugas/dashboard.php');
        } else {
            $err = 'Hak akses akun tidak dikenali.';
            $_SESSION = [];
        }

    } else {
        $err = 'Username atau password salah.';
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login EcoCompost</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>

<body class="login">

<div class="loginbox">

    <div class="loginmark">🌱</div>

    <div class="eyebrow">SMART COMPOST SUPPLY CHAIN</div>

    <h1>EcoCompost</h1>

    <p>
        Selamat datang di sistem monitoring kerusakan
        sampah organik dan penjadwalan rantai pasok kompos.
    </p>

    <?php if ($err): ?>
        <div class="alert error">
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <label>Username</label>
        <input
            type="text"
            name="username"
            placeholder="Masukkan username"
            required
            autofocus
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
        >

        <button type="submit" class="btn full">
            Masuk ke sistem →
        </button>

    </form>

    <small>
        Gunakan akun yang telah dibuat oleh administrator.
    </small>

</div>

</body>
</html>
