<?php
$page_title = 'Sefer Yönetimi';
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Firma Admin') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$company_id = null;
try {
    $stmt_company = $pdo->prepare("SELECT company_id FROM Users WHERE id = ?");
    $stmt_company->execute([$_SESSION['user_id']]);
    $company_id = $stmt_company->fetchColumn();
} catch (PDOException $e) {
    die("Firma bilgileriniz alınırken bir hata oluştu: " . $e->getMessage());
}

if (!$company_id) {
    die("Bir firmaya atanmamışsınız. Lütfen Süper Admin ile iletişime geçin.");
}

$success_message = '';
$error_message = '';
$edit_trip = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_trip'])) {
    $departure_location = trim($_POST['departure_location']);
    $arrival_location = trim($_POST['arrival_location']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = floatval($_POST['price']);
    $seat_count = intval($_POST['seat_count']);

    try {
        $sql = "INSERT INTO Trips (company_id, departure_location, arrival_location, departure_time, arrival_time, price, seat_count) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$company_id, $departure_location, $arrival_location, $departure_time, $arrival_time, $price, $seat_count]);
        $success_message = "Yeni sefer başarıyla oluşturuldu.";
    } catch (PDOException $e) {
        $error_message = "Sefer oluşturulurken hata oluştu: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip'])) {
    $trip_id = $_POST['trip_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$trip_id, $company_id]);
        $success_message = "Sefer başarıyla silindi.";
    } catch (PDOException $e) {
        $error_message = "Sefer silinirken hata oluştu: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $trip_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    $edit_trip = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trip'])) {
    $trip_id = $_POST['trip_id'];
    $departure_location = trim($_POST['departure_location']);
    $arrival_location = trim($_POST['arrival_location']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = floatval($_POST['price']);
    $seat_count = intval($_POST['seat_count']);

    try {
        $sql = "UPDATE Trips SET departure_location = ?, arrival_location = ?, departure_time = ?, 
                arrival_time = ?, price = ?, seat_count = ? 
                WHERE id = ? AND company_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$departure_location, $arrival_location, $departure_time, $arrival_time, $price, $seat_count, $trip_id, $company_id]);
        $success_message = "Sefer başarıyla güncellendi.";
    } catch (PDOException $e) {
        $error_message = "Sefer güncellenirken hata oluştu: " . $e->getMessage();
    }
}
$trips = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
    $stmt->execute([$company_id]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Seferler listelenirken bir hata oluştu: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="admin_panel.php" class="list-group-item list-group-item-action">Genel Bakış</a>
                <a href="admin_trips.php" class="list-group-item list-group-item-action active">Sefer Yönetimi</a>
                <a href="admin_coupons_f.php" class="list-group-item list-group-item-action">Firma Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Sefer Yönetimi</h2>
            <hr>

            <?php if ($success_message) echo "<div class='alert alert-success'>$success_message</div>"; ?>
            <?php if ($error_message) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <?php echo $edit_trip ? 'Seferi Düzenle' : 'Yeni Sefer Ekle'; ?>
                </div>
                <div class="card-body">
                    <form action="admin_trips.php" method="POST">
                        <?php if ($edit_trip): ?>
                            <input type="hidden" name="trip_id" value="<?php echo $edit_trip['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="departure_location" class="form-label">Kalkış Yeri:</label>
                                <input type="text" class="form-control" name="departure_location" value="<?php echo $edit_trip ? htmlspecialchars($edit_trip['departure_location']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="arrival_location" class="form-label">Varış Yeri:</label>
                                <input type="text" class="form-control" name="arrival_location" value="<?php echo $edit_trip ? htmlspecialchars($edit_trip['arrival_location']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="departure_time" class="form-label">Kalkış Zamanı:</label>
                                <input type="datetime-local" class="form-control" name="departure_time" value="<?php echo $edit_trip ? date('Y-m-d\TH:i', strtotime($edit_trip['departure_time'])) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="arrival_time" class="form-label">Varış Zamanı:</label>
                                <input type="datetime-local" class="form-control" name="arrival_time" value="<?php echo $edit_trip ? date('Y-m-d\TH:i', strtotime($edit_trip['arrival_time'])) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Fiyat (TL):</label>
                                <input type="number" class="form-control" name="price" step="0.01" value="<?php echo $edit_trip ? $edit_trip['price'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="seat_count" class="form-label">Koltuk Sayısı:</label>
                                <input type="number" class="form-control" name="seat_count" value="<?php echo $edit_trip ? $edit_trip['seat_count'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <?php if ($edit_trip): ?>
                            <button type="submit" name="update_trip" class="btn btn-warning">Güncelle</button>
                            <a href="admin_trips.php" class="btn btn-secondary">İptal</a>
                        <?php else: ?>
                            <button type="submit" name="create_trip" class="btn btn-primary">Sefer Oluştur</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Firmanızın Seferleri
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Kalkış Zamanı</th>
                                <th>Fiyat</th>
                                <th>Koltuk</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trips)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Henüz eklenmiş seferiniz yok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trips as $trip): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trip['departure_location']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['arrival_location']); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($trip['departure_time'])); ?></td>
                                        <td><?php echo $trip['price']; ?> TL</td>
                                        <td><?php echo $trip['seat_count']; ?></td>
                                        <td>
                                            <a href="admin_trips.php?edit_id=<?php echo $trip['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                                            <form action="admin_trips.php" method="POST" class="d-inline" onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                                <button type="submit" name="delete_trip" class="btn btn-danger btn-sm">Sil</button>
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