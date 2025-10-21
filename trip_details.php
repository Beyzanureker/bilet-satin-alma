<?php
$page_title = 'Sefer Detayları'; 
require_once 'header.php'; 

$trip = null;
$error_message = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Geçersiz sefer ID'si.";
} else {
    $trip_id = $_GET['id'];

    try {
        $sql = "SELECT Trips.*, Companies.name as company_name 
                FROM Trips 
                JOIN Companies ON Trips.company_id = Companies.id
                WHERE Trips.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trip_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trip) {
            $error_message = "Sefer bulunamadı.";
        }

    } catch (PDOException $e) {
        $error_message = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>

<?php elseif (!empty($trip)): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Yolculuk Detayları</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h3><?php echo htmlspecialchars($trip['departure_location']); ?> &rarr; <?php echo htmlspecialchars($trip['arrival_location']); ?></h3>
                    <h5 class="text-muted"><?php echo htmlspecialchars($trip['company_name']); ?></h5>
                    <hr>
                    <p><strong>Kalkış Tarihi ve Saati:</strong> <?php echo date('d F Y, H:i', strtotime($trip['departure_time'])); ?></p>
                    <p><strong>Varış Tarihi ve Saati:</strong> <?php echo date('d F Y, H:i', strtotime($trip['arrival_time'])); ?></p>
                    <p><strong>Toplam Koltuk Sayısı:</strong> <?php echo htmlspecialchars($trip['seat_count']); ?></p>
                </div>
                <div class="col-md-4 text-center bg-light p-4 rounded">
                    <h4>Bilet Fiyatı</h4>
                    <h2 class="display-4 text-success"><?php echo htmlspecialchars($trip['price']); ?> TL</h2>
                    <div class="d-grid mt-4">
                        <a href="buy_ticket.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-lg btn-success">Hemen Satın Al</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php 
require_once 'footer.php'; 
?>