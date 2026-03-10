<?php
session_start();
require 'config/koneksi.php';
require 'vendor/autoload.php';

date_default_timezone_set("Asia/Jakarta");

use PHPMailer\PHPMailer\PHPMailer;

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$wa         = trim($_POST['wa'] ?? '');
$password   = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (
    $first_name === '' ||
    $last_name === '' ||
    $email === '' ||
    $wa === '' ||
    $password === '' ||
    $password_confirm === ''
) {
    $_SESSION['error'] = 'register_empty';
    header("Location: join-us.php?mode=register");
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['error'] = 'password_not_match';
    header("Location: join-us.php?mode=register");
    exit;
}

/* cek email sudah terdaftar atau belum */
$checkEmail = $conn->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkResult = $checkEmail->get_result();

if ($checkResult->num_rows > 0) {
    $_SESSION['error'] = 'email_exists';
    header("Location: join-us.php?mode=register");
    exit;
}

/* Hash password */
$hash = password_hash($password, PASSWORD_DEFAULT);

/* Generate OTP */
$verification_code = rand(100000, 999999);
$expired = date("Y-m-d H:i:s", strtotime("+5 minutes"));

/* Simpan ke database */
$stmt = $conn->prepare("INSERT INTO users
(first_name, last_name, email, wa, password, verification_code, verification_expired)
VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "sssssss",
    $first_name,
    $last_name,
    $email,
    $wa,
    $hash,
    $verification_code,
    $expired
);

/*
  tetap tangani kemungkinan race condition:
  misalnya 2 request masuk hampir bersamaan dan UNIQUE email di DB yang menangkap
*/
if (!$stmt->execute()) {
    if ($conn->errno == 1062) {
        $_SESSION['error'] = 'email_exists';
        header("Location: join-us.php?mode=register");
        exit;
    }

    $_SESSION['error'] = 'register_failed';
    header("Location: join-us.php?mode=register");
    exit;
}

/* ================== KIRIM EMAIL ================== */
$mail = new PHPMailer(true);
$mail->AddEmbeddedImage('assets/img/logo-cinem4.png', 'logo_cinem4');
$mail->CharSet = 'UTF-8';
$mail->isHTML(true);

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'fourcinem4@gmail.com';
$mail->Password = 'qvtocqgdpbwbmsrq';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('fourcinem4@gmail.com', 'CINEM4');
$mail->addAddress($email);

$mail->Subject = 'Kode Verifikasi CINEM4';

$mail->Body = "
<div style='font-family:Arial;background:#0f172a;padding:40px'>

    <div style='max-width:520px;margin:auto;background:#1e293b;
    border-radius:14px;padding:35px;text-align:center;color:white;
    box-shadow:0 10px 30px rgba(0,0,0,0.4)'>

        <img src='cid:logo_cinem4' style='width:230px;margin-bottom:20px'>

        <h2 style='margin-top:5px'>Verifikasi Akun</h2>

        <p style='color:#cbd5f5;font-size:15px'>
        Halo <b>$first_name</b>, terima kasih telah mendaftar di CINEM4.
        Gunakan kode berikut untuk memverifikasi akun Anda.
        </p>

        <div style='
        margin-top:30px;
        font-size:40px;
        font-weight:bold;
        letter-spacing:10px;
        background:#0f172a;
        padding:18px 28px;
        border-radius:10px;
        display:inline-block;
        box-shadow:0 0 15px rgba(59,130,246,0.8);
        border:1px solid rgba(59,130,246,0.6);
        '>
        $verification_code
        </div>

        <p style='margin-top:25px;font-size:14px;color:#94a3b8'>
        Kode ini berlaku selama <b>5 menit</b>.
        </p>

        <hr style='border:none;border-top:1px solid #334155;margin:30px 0'>

        <p style='font-size:12px;color:#64748b'>
        Jika Anda tidak merasa membuat akun CINEM4, abaikan email ini.
        </p>

        <p style='font-size:12px;color:#475569;margin-top:10px'>
        © " . date("Y") . " CINEM4. All rights reserved.
        </p>

    </div>

</div>
";

$mail->send();

header("Location: verify.php?email=" . urlencode($email));
exit;
?>