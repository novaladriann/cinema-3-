<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'fourcinem4@gmail.com'; // ganti
    $mail->Password = 'qvtocqgdpbwbmsrq';  // ganti
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('fourcinem4@gmail.com', 'CINEM4 TEST');
    $mail->addAddress('aldoali658@gmail.com'); // kirim ke diri sendiri

    $mail->Subject = 'Test Email CINEM4';
    $mail->Body    = 'Kalau email ini masuk, berarti SMTP berhasil!';
    $mail->SMTPDebug = 2;
    $mail->send();
    echo "Email BERHASIL dikirim!";
    
} catch (Exception $e) {
    echo "Email GAGAL: {$mail->ErrorInfo}";
}