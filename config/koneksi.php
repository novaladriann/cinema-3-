<?php
$conn = new mysqli("localhost", "root", "", "db_cinem4_1_");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>