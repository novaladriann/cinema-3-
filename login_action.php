<?php
session_start();
require 'config/koneksi.php';

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'empty';
    header("Location: join-us.php?mode=login");
    exit;
}

$stmt = $conn->prepare("SELECT id_user, first_name, last_name, email, password, is_verified FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'email_not_found';
    header("Location: join-us.php?mode=login");
    exit;
}

$user = $result->fetch_assoc();

if ((int)$user['is_verified'] !== 1) {
    $_SESSION['error'] = 'not_verified';
    header("Location: join-us.php?mode=login");
    exit;
}

if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'wrong_password';
    header("Location: join-us.php?mode=login");
    exit;
}

session_regenerate_id(true);

$_SESSION['user']    = true;
$_SESSION['user_id'] = $user['id_user']; // INI YANG BENAR
$_SESSION['name']    = trim($user['first_name'] . ' ' . $user['last_name']);
$_SESSION['email']   = $user['email'];

header("Location: index.php");
exit;