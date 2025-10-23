<?php
$page_title = 'Kayıt Ol';
require_once 'header.php'; 

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$success_message = '';
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        $error_message = "Girilen şifreler eşleşmiyor.";
    } 
    elseif (strlen($password) < 6) {
         $error_message = "Şifre en az 6 karakter olmalıdır.";
    }
    else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            
               $sql = "INSERT INTO Users (fullname, email, password, balance) VALUES (?, ?, ?, 800)"; 
               $stmt = $pdo->prepare($sql);
               $stmt->execute([$fullname, $email, $hashed_password]);
            $success_message = "Kayıt işlemi başarılı! Giriş yapabilirsiniz.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                 $error_message = "Bu e-posta adresi zaten kullanılıyor.";
            } else {
                $error_message = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    } 
}
?>

<div class="auth-bg">
    <div class="container py-5"> <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                         <div class="text-center mb-4">
                             <img src="img/logo.png" alt="<?php echo SITE_NAME; ?> Logo" height="60">
                         </div>
                        <h1 class="card-title text-center mb-4 fs-4">PUSULA'ya Kayıt Ol</h1>

                        <?php
                        if (!empty($success_message)) {
                            echo "<div class='alert alert-success'>$success_message</div>";
                        }
                        if (!empty($error_message)) {
                            echo "<div class='alert alert-danger'>$error_message</div>";
                        }
                        ?>

                        <?php if (empty($success_message)): ?>
                            <form action="register.php" method="POST">
                                <div class="mb-3">
                                    <label for="fullname" class="form-label">Ad Soyad:</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-posta Adresi:</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Şifre:</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Şifre (Tekrar):</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">Kayıt Ol</button> 
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="d-grid mt-4">
                                <a href="login.php" class="btn btn-primary btn-lg">Giriş Yap</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center py-3 bg-light">
                        <small>Zaten bir hesabın var mı? <a href="login.php">Giriş Yap</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once 'footer.php';
?>