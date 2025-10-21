<?php
$page_title = 'Firma Yönetimi';
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Admin') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$success_message = '';
$error_message = '';
$edit_company = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_company'])) {
    $company_name = trim($_POST['company_name']);
    if (!empty($company_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Companies (name) VALUES (?)");
            $stmt->execute([$company_name]);
            $success_message = "Yeni firma '$company_name' başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            $error_message = "Firma oluşturulurken hata oluştu: " . $e->getMessage();
        }
    } else {
        $error_message = "Firma adı boş olamaz.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_company'])) {
    $company_id = $_POST['company_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $success_message = "Firma başarıyla silindi.";
    } catch (PDOException $e) {
        $error_message = "Firma silinirken hata oluştu. (Bu firmaya kayıtlı seferler veya adminler olabilir): " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    $company_id = $_POST['company_id'];
    $company_name = trim($_POST['company_name']);
    if (!empty($company_name) && !empty($company_id)) {
        try {
            $stmt = $pdo->prepare("UPDATE Companies SET name = ? WHERE id = ?");
            $stmt->execute([$company_name, $company_id]);
            $success_message = "Firma başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error_message = "Firma güncellenirken hata oluştu: " . $e->getMessage();
        }
    } else {
        $error_message = "Firma adı veya ID boş olamaz.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $company_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM Companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $edit_company = $stmt->fetch(PDO::FETCH_ASSOC);
}

$companies = [];
try {
    $stmt = $pdo->query("SELECT * FROM Companies ORDER BY name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Firmalar listelenirken bir hata oluştu: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="admin_panel.php" class="list-group-item list-group-item-action">Genel Bakış</a>
                <a href="admin_companies.php" class="list-group-item list-group-item-action active">Firma Yönetimi</a>
                <a href="admin_users.php" class="list-group-item list-group-item-action">Firma Admini Yönetimi</a>
                <a href="admin_coupons.php" class="list-group-item list-group-item-action">Global Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Firma Yönetimi</h2>
            <hr>

            <?php if ($success_message) echo "<div class='alert alert-success'>$success_message</div>"; ?>
            <?php if ($error_message) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <?php echo $edit_company ? 'Firmayı Düzenle' : 'Yeni Firma Ekle'; ?>
                </div>
                <div class="card-body">
                    <form action="admin_companies.php" method="POST">
                        <?php if ($edit_company): ?>
                            <input type="hidden" name="company_id" value="<?php echo $edit_company['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="text" class="form-control" name="company_name" 
                                   placeholder="Yeni firma adı..." 
                                   value="<?php echo $edit_company ? htmlspecialchars($edit_company['name']) : ''; ?>" required>
                            
                            <?php if ($edit_company): ?>
                                <button type="submit" name="update_company" class="btn btn-warning">Güncelle</button>
                                <a href="admin_companies.php" class="btn btn-secondary">İptal</a>
                            <?php else: ?>
                                <button type="submit" name="create_company" class="btn btn-primary">Ekle</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Mevcut Firmalar
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Firma Adı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Henüz eklenmiş firma yok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><?php echo $company['id']; ?></td>
                                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                                        <td>
                                            <a href="admin_companies.php?edit_id=<?php echo $company['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                                            
                                            <form action="admin_companies.php" method="POST" class="d-inline" onsubmit="return confirm('Bu firmayı silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                <button type="submit" name="delete_company" class="btn btn-danger btn-sm">Sil</button>
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