<?php
$page_title = 'Biletlerim';
require_once 'header.php';

$cancel_success = '';
$cancel_error = '';
if (isset($_SESSION['cancel_success'])) {
    $cancel_success = $_SESSION['cancel_success'];
    unset($_SESSION['cancel_success']);
}
if (isset($_SESSION['cancel_error'])) {
    $cancel_error = $_SESSION['cancel_error'];
    unset($_SESSION['cancel_error']);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Biletlerinizi görmek için giriş yapmalısınız.");
    exit;
}

$my_bookings = [];
$error_message = '';
$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT
                Bookings.id as booking_id,
                Bookings.seat_number,
                Bookings.booking_time,
                Bookings.coupon_code,
                Bookings.final_price,
                Trips.departure_location,
                Trips.arrival_location,
                Trips.departure_time,
                Trips.price,
                Companies.name as company_name
            FROM Bookings
            JOIN Trips ON Bookings.trip_id = Trips.id
            JOIN Companies ON Trips.company_id = Companies.id
            WHERE Bookings.user_id = ?
            ORDER BY Trips.departure_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Biletleriniz getirilirken bir hata oluştu: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Satın Aldığım Biletler</h2>

    <?php if (!empty($cancel_success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($cancel_success); ?></div>
    <?php endif; ?>
    <?php if (!empty($cancel_error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($cancel_error); ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>

    <?php elseif (empty($my_bookings)): ?>
        <div class="alert alert-warning">Henüz satın alınmış bir biletiniz bulunmamaktadır.</div>

    <?php else: ?>
        <?php foreach ($my_bookings as $booking): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($booking['company_name']); ?></h5>
                    <span class="badge bg-secondary">Koltuk No: <?php echo $booking['seat_number']; ?></span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center gy-2">
                        <div class="col-md-6">
                            <strong><?php echo htmlspecialchars($booking['departure_location']); ?></strong> &rarr;
                            <strong><?php echo htmlspecialchars($booking['arrival_location']); ?></strong>
                            <br>
                            <small class="text-muted">
                                Kalkış: <?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?>
                            </small>
                        </div>
                        <div class="col-md-2 text-center text-md-end">
                             <?php
                                 $display_price = $booking['final_price'] !== null ? $booking['final_price'] : $booking['price'];
                                 $show_original_price = $booking['final_price'] !== null && $booking['coupon_code'] && $booking['final_price'] < $booking['price'];
                             ?>
                             <h5 class="text-primary mb-0"><?php echo htmlspecialchars(number_format($display_price, 2, ',', '.')); ?> TL</h5>
                             <?php if ($show_original_price): ?>
                                 <small class="text-muted text-decoration-line-through">(<?php echo number_format($booking['price'], 2, ',', '.'); ?> TL)</small>
                             <?php endif; ?>
                         </div>
                        <div class="col-md-4 text-center text-md-end">
                            <a href="download_pdf.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-info btn-sm mb-1 mb-md-0">PDF İndir</a>
                            <a href="cancel_ticket.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger btn-sm mb-1 mb-md-0">İptal Et</a>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted d-flex justify-content-between flex-wrap">
                    <span>Satın Alınma: <?php echo date('d M Y H:i', strtotime($booking['booking_time'])); ?></span>
                    <?php if ($booking['coupon_code']): ?>
                        <span class="text-success fw-bold">Kupon: <?php echo htmlspecialchars($booking['coupon_code']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>