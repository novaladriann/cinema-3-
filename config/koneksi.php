<?php
$conn = new mysqli("localhost", "root", "", "db_cinem4(1)");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>