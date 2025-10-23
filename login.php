<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    if (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: index.php');
    exit;
}

$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "E-posta ve şifre alanları zorunludur.";
    } else {
        try {
            $sql = "SELECT * FROM Users WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_fullname'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];

                $redirect_url = isset($_GET['intended']) ? urldecode($_GET['intended']) : 'index.php';
                if (ob_get_level() > 0) { ob_end_clean(); }
                header("Location: " . $redirect_url);
                exit;
            } else {
                $error_message = "E-posta veya şifre hatalı.";
            }
        } catch (PDOException $e) {
            error_log("Giriş veritabanı hatası: " . $e->getMessage());
            $error_message = "Giriş sırasında bir sorun oluştu. Lütfen tekrar deneyin.";
        }
    }
}

$page_title = 'Giriş Yap';
ob_start();
require_once 'header.php';
?>

<div class="auth-bg">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                         <div class="text-center mb-4">
                             <img src="img/logo.png" alt="<?php echo SITE_NAME; ?> Logo" height="60">
                         </div>
                        <h1 class="card-title text-center mb-4 fs-4">PUSULA'ya Giriş Yap</h1>

                        <?php
                        if (isset($_GET['error'])) {
                            echo "<div class='alert alert-warning'>" . htmlspecialchars($_GET['error']) . "</div>";
                        }
                        if (!empty($error_message)) {
                            echo "<div class='alert alert-danger'>". htmlspecialchars($error_message) ."</div>";
                        }
                        ?>

                        <form action="login.php<?php echo isset($_GET['intended']) ? '?intended=' . urlencode($_GET['intended']) : ''; ?>" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi:</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3 bg-light">
                        <small>Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
ob_end_flush();
