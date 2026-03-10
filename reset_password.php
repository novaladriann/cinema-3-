<?php
require 'config/koneksi.php';

$token = $_GET['token'] ?? '';

$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expired > NOW()");
$stmt->bind_param("s",$token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("Token tidak valid atau sudah kadaluarsa");
}
?>

<?php include 'partials/head.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="container py-5" style="max-width:500px">

<h3 class="text-light mb-4">Reset Password</h3>

<form method="post" action="reset_password_action.php">

<input type="hidden" name="token" value="<?= $token ?>">

<div class="mb-3">
<label class="form-label text-light">Password Baru</label>
<input type="password" name="password"
class="form-control bg-dark text-light border-secondary"
required>
</div>

<button class="btn btn-light w-100">
Reset Password
</button>

</form>

</div>

<?php include 'partials/footer.php'; ?>