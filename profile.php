<?php
$page_title = 'Profilim'; 
require_once 'header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Profilinizi görmek için giriş yapmalısınız.");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_info = null;
$error_message = '';
$success_message = '';

try {
    $stmt = $pdo->prepare("SELECT fullname, email, balance FROM Users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Kullanıcı bilgileri getirilirken hata oluştu: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Lütfen tüm şifre alanlarını doldurun.";
    } 

    elseif ($new_password !== $confirm_password) {
        $error_message = "Yeni şifreler eşleşmiyor.";
    } 

    elseif (strlen($new_password) < 6) {
        $error_message = "Yeni şifre en az 6 karakter olmalıdır.";
    } 

    else {
        try {
            $stmt_check = $pdo->prepare("SELECT password FROM Users WHERE id = ?");
            $stmt_check->execute([$user_id]);
            $stored_hash = $stmt_check->fetchColumn();
            if (password_verify($current_password, $stored_hash)) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE Users SET password = ? WHERE id = ?");
                $stmt_update->execute([$new_hashed_password, $user_id]);
                $success_message = "Şifreniz başarıyla güncellendi.";
            } else {
                $error_message = "Mevcut şifrenizi yanlış girdiniz.";
            }
        } catch (PDOException $e) {
            $error_message = "Şifre güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
        }
    }
}

?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action active">Profil Bilgileri</a>
                <a href="my_tickets.php" class="list-group-item list-group-item-action">Biletlerim</a>
                </div>
        </div>

        <div class="col-md-9">
            <h2>Profil Bilgileri</h2>
            <hr>

            <?php if (!empty($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
            <?php if (!empty($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>

            <?php if ($user_info): ?>
                <div class="card mb-4">
                    <div class="card-header">Kullanıcı Detayları</div>
                    <div class="card-body">
                        <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($user_info['fullname']); ?></p>
                        <p><strong>E-posta:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                        <p><strong>Mevcut Bakiye:</strong> <span class="badge bg-success fs-6"><?php echo number_format($user_info['balance'], 2, ',', '.'); ?> TL</span></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Şifre Değiştir</div>
                    <div class="card-body">
                        <form action="profile.php" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mevcut Şifre:</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre:</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar):</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">Şifreyi Güncelle</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">Kullanıcı bilgileri yüklenemedi.</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php 
require_once 'footer.php'; 
?>