<?php
require_once __DIR__.'/config/app.php';
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'petugas') {
    go('petugas/dashboard.php');
}
if (!empty($_SESSION['user_id'])) go('dashboard.php');
go('login.php');
