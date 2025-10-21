<?php
$page_title = 'Global Kupon Yönetimi';
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Admin') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    $code = trim($_POST['code']);
    $discount_rate = floatval($_POST['discount_rate']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    $company_id = NULL;

    if (empty($code) || $discount_rate <= 0 || $usage_limit <= 0 || empty($expire_date)) {
        $error_message = "Tüm alanlar zorunludur ve oran/limit 0'dan büyük olmalıdır.";
    } else {
        try {
            $sql = "INSERT INTO Coupons (code, discount_rate, usage_limit, expire_date, company_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $discount_rate, $usage_limit, $expire_date, $company_id]);
            $success_message = "Global kupon '$code' başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Bu kupon kodu zaten kullanılıyor.";
            } else {
                $error_message = "Kupon oluşturulurken hata oluştu: " . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $coupon_id = $_POST['coupon_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        $success_message = "Kupon başarıyla silindi.";
    } catch (PDOException $e) {
        $error_message = "Kupon silinirken hata oluştu: " . $e->getMessage();
    }
}

$coupons = [];
try {
    $sql = "SELECT C.*, CO.name as company_name 
            FROM Coupons C 
            LEFT JOIN Companies CO ON C.company_id = CO.id
            ORDER BY C.expire_date DESC";
    $stmt = $pdo->query($sql);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Kuponlar listelenirken bir hata oluştu: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="admin_panel.php" class="list-group-item list-group-item-action">Genel Bakış</a>
                <a href="admin_companies.php" class="list-group-item list-group-item-action">Firma Yönetimi</a>
                <a href="admin_users.php" class="list-group-item list-group-item-action">Firma Admini Yönetimi</a>
                <a href="admin_coupons.php" class="list-group-item list-group-item-action active">Global Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Kupon Yönetimi</h2>
            <hr>

            <?php if ($success_message) echo "<div class='alert alert-success'>$success_message</div>"; ?>
            <?php if ($error_message) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Yeni Global Kupon Ekle (Tüm Firmalarda Geçerli)
                </div>
                <div class="card-body">
                    <form action="admin_coupons.php" method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code" class="form-label">Kupon Kodu:</label>
                                <input type="text" class="form-control" id="code" name="code" placeholder="Örn: YAZ20" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="discount_rate" class="form-label">İndirim (%):</label>
                                <input type="number" class="form-control" id="discount_rate" name="discount_rate" placeholder="Örn: 10" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="usage_limit" class="form-label">Kullanım Limiti:</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" placeholder="Örn: 100" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="expire_date" class="form-label">Son Kullanma Tarihi:</label>
                                <input type="date" class="form-control" id="expire_date" name="expire_date" required>
                            </div>
                        </div>
                        <button type="submit" name="create_coupon" class="btn btn-primary">Global Kupon Oluştur</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Tüm Kuponlar (Global ve Firmaya Özel)
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Kupon Kodu</th>
                                <th>Firma</th>
                                <th>İndirim</th>
                                <th>Limit</th>
                                <th>Son Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Henüz eklenmiş kupon yok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                        <td>
                                            <?php if ($coupon['company_id'] == NULL): ?>
                                                <span class="badge bg-success">Global Kupon</span>
                                            <?php else: ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($coupon['company_name']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>%<?php echo htmlspecialchars($coupon['discount_rate']); ?></td>
                                        <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($coupon['expire_date'])); ?></td>
                                        <td>
                                            <form action="admin_coupons.php" method="POST" class="d-inline" onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Sil</button>
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