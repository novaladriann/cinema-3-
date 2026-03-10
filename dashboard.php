<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: join-us.php?mode=login");
    exit;
}

echo "Selamat datang, " . $_SESSION['nama'];
?>
<br>
<a href="logout.php">Logout</a>