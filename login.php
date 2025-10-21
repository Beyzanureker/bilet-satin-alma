<?php
$page_title = 'Giriş Yap';
require_once 'header.php'; 

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    try {
        $sql = "SELECT * FROM Users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_fullname'] = $user['fullname'];
            $_SESSION['user_role'] = $user['role'];

            if (isset($_GET['intended'])) {
                 header("Location: " . urldecode($_GET['intended']));
            } else {
                 header("Location: index.php");
            }
            exit;
        } else {
            $error_message = "E-posta veya şifre hatalı.";
        }
    } catch (PDOException $e) {
        $error_message = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>

<div class="auth-bg"> 
    <div class="container py-5"> <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg"> 
                    <div class="card-body p-4"> 
                        <div class="text-center mb-4">
                             <img src="img/logo.png" alt="<?php echo SITE_NAME; ?> Logo" height="60"> </div>
                        <h1 class="card-title text-center mb-4 fs-4">PUSULA'ya Giriş Yap</h1> 

                        <?php
                        if (isset($_GET['error'])) {
                            echo "<div class='alert alert-warning'>" . htmlspecialchars($_GET['error']) . "</div>";
                        }
                        if (!empty($error_message)) {
                            echo "<div class='alert alert-danger'>$error_message</div>";
                        }
                        ?>

                        <form action="login.php<?php echo isset($_GET['intended']) ? '?intended=' . urlencode($_GET['intended']) : ''; ?>" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
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
?>