<?php
$page_title = 'Firma Admin Yönetimi';
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Admin') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_firm_admin'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id']; 

    if (empty($fullname) || empty($email) || empty($password) || empty($company_id)) {
        $error_message = "Tüm alanlar zorunludur.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $sql = "INSERT INTO Users (fullname, email, password, role, company_id) 
                    VALUES (?, ?, ?, 'Firma Admin', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fullname, $email, $hashed_password, $company_id]);
            $success_message = "Yeni Firma Admini '$fullname' başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { 
                $error_message = "Bu e-posta adresi zaten kullanılıyor.";
            } else {
                $error_message = "Kullanıcı oluşturulurken hata oluştu: " . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_firm_admin'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ? AND role = 'Firma Admin'");
        $stmt->execute([$user_id]);
        $success_message = "Firma Admini başarıyla silindi.";
    } catch (PDOException $e) {
        $error_message = "Kullanıcı silinirken hata oluştu: " . $e->getMessage();
    }
}

$firm_admins = [];
$companies = [];
try {
    $sql_admins = "SELECT U.id, U.fullname, U.email, C.name as company_name 
                   FROM Users U 
                   JOIN Companies C ON U.company_id = C.id 
                   WHERE U.role = 'Firma Admin' 
                   ORDER BY C.name, U.fullname";
    $stmt_admins = $pdo->query($sql_admins);
    $firm_admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
    $stmt_companies = $pdo->query("SELECT * FROM Companies ORDER BY name");
    $companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Veriler listelenirken bir hata oluştu: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="admin_panel.php" class="list-group-item list-group-item-action">Genel Bakış</a>
                <a href="admin_companies.php" class="list-group-item list-group-item-action">Firma Yönetimi</a>
                <a href="admin_users.php" class="list-group-item list-group-item-action active">Firma Admini Yönetimi</a>
                <a href="admin_coupons.php" class="list-group-item list-group-item-action">Global Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Firma Admini Yönetimi</h2>
            <hr>

            <?php if ($success_message) echo "<div class='alert alert-success'>$success_message</div>"; ?>
            <?php if ($error_message) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Yeni Firma Admini Ekle
                </div>
                <div class="card-body">
                    <form action="admin_users.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fullname" class="form-label">Ad Soyad:</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-posta:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Şifre:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_id" class="form-label">Atanacak Firma:</label>
                                <select class="form-select" id="company_id" name="company_id" required>
                                    <option value="">-- Firma Seçin --</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="create_firm_admin" class="btn btn-primary">Firma Admini Oluştur</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Mevcut Firma Adminleri
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Atandığı Firma</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($firm_admins)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Henüz eklenmiş firma admini yok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($firm_admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['company_name']); ?></td>
                                        <td>
                                            <form action="admin_users.php" method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" name="delete_firm_admin" class="btn btn-danger btn-sm">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once 'footer.php'; 
?>