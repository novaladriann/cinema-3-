<?php
/**
 * admin/auth.php
 * Include file ini di SETIAP halaman admin (selain login.php)
 * untuk memastikan hanya admin yang sudah login bisa akses.
 *
 * Contoh pemakaian:
 *   <?php
 *   session_start();
 *   require 'auth.php';
 *   ?>
 */

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* Helper: ambil data admin yang sedang login */
function currentAdmin(): array {
    return $_SESSION['admin'] ?? [];
}

/* Helper: cek apakah admin adalah superadmin */
function isSuperAdmin(): bool {
    return ($_SESSION['admin']['role'] ?? '') === 'superadmin';
}