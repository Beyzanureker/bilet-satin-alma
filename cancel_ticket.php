<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    $_SESSION['cancel_error'] = "Geçersiz bilet ID'si.";
    header("Location: my_tickets.php"); exit;
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    $sql = "SELECT B.id, T.departure_time, B.final_price, T.price as original_price /* Fallback için orijinal fiyatı da alalım */
            FROM Bookings B
            JOIN Trips T ON B.trip_id = T.id
            WHERE B.id = ? AND B.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) { throw new Exception("Bilet bulunamadı veya bu bileti iptal etme yetkiniz yok."); }

    $departure_time = new DateTime($booking['departure_time']);
    $now = new DateTime();
    $interval = $now->diff($departure_time);
    $minutes_to_departure = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    if ($interval->invert == 1 || $minutes_to_departure < 60) { throw new Exception("Seferin kalkış saatine 1 saatten az kaldığı için bilet iptal edilemez."); }

    $stmt_delete = $pdo->prepare("DELETE FROM Bookings WHERE id = ?");
    $stmt_delete->execute([$booking_id]);

    $refund_amount = $booking['final_price'] !== null ? $booking['final_price'] : $booking['original_price'];
    if ($refund_amount > 0) {
        $stmt_refund = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
        $stmt_refund->execute([$refund_amount, $user_id]);
    }

    $pdo->commit();
    $_SESSION['cancel_success'] = "Biletiniz başarıyla iptal edildi. " . number_format($refund_amount, 2, ',', '.') . " TL hesabınıza iade edildi.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION['cancel_error'] = "İptal işlemi başarısız: " . $e->getMessage();
}

header("Location: my_tickets.php");
exit;
?>