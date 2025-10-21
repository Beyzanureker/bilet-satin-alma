<?php
session_start();
require_once 'db.php'; 

require_once 'fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) { die("Bu işlemi yapmak için giriş yapmalısınız."); }
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) { die("Geçersiz bilet ID'si."); }

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT
                B.id as booking_id, B.seat_number, B.booking_time, B.coupon_code, B.final_price,
                U.fullname as user_fullname, U.email as user_email,
                T.departure_location, T.arrival_location, T.departure_time, T.arrival_time, T.price as original_price,
                C.name as company_name
            FROM Bookings B
            JOIN Users U ON B.user_id = U.id
            JOIN Trips T ON B.trip_id = T.id
            JOIN Companies C ON T.company_id = C.id
            WHERE B.id = ? AND B.user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) { die("Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok."); }

    function fix_tr($str) {
        if ($str === null || $str === '') return '';
        $str = html_entity_decode((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $converted = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $str);
         return $converted === false ? $str : $converted;
    }

    $pdf = new FPDF('P', 'mm', 'A4'); 
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 12); 
    $pdf->SetFillColor(230, 230, 230); 
    $pdf->SetDrawColor(180, 180, 180);

    $pdf->Image('img/logo.png', 10, 8, 30); 
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->Cell(0, 15, fix_tr(SITE_NAME . ' E-Bilet'), 0, 1, 'C'); 
    $pdf->Ln(15); 
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetFillColor(240, 240, 240); 
    $pdf->Cell(0, 10, fix_tr('Yolcu Bilgileri'), 1, 1, 'L', true); 
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(40, 8, fix_tr('Ad Soyad:'), 0, 0);
    $pdf->Cell(0, 8, fix_tr($ticket['user_fullname']), 0, 1);
    $pdf->Cell(40, 8, fix_tr('E-posta:'), 0, 0);
    $pdf->Cell(0, 8, $ticket['user_email'], 0, 1); 
    $pdf->Ln(7);

    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, fix_tr('Sefer Bilgileri'), 1, 1, 'L', true);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(40, 8, fix_tr('Firma:'), 0, 0);
    $pdf->Cell(0, 8, fix_tr($ticket['company_name']), 0, 1);
    $pdf->Cell(40, 8, fix_tr('Güzergah:'), 0, 0);
    $pdf->Cell(0, 8, fix_tr($ticket['departure_location'] . ' -> ' . $ticket['arrival_location']), 0, 1);
    $pdf->Cell(40, 8, fix_tr('Kalkış:'), 0, 0);
    $pdf->Cell(0, 8, date('d M Y, H:i', strtotime($ticket['departure_time'])), 0, 1);
     $pdf->Cell(40, 8, fix_tr('Varış (Tahmini):'), 0, 0); 
     $pdf->Cell(0, 8, date('d M Y, H:i', strtotime($ticket['arrival_time'])), 0, 1);
    $pdf->Ln(7);

    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, fix_tr('Bilet Detayları'), 1, 1, 'L', true);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(40, 8, fix_tr('Koltuk No:'), 0, 0);
    $pdf->Cell(0, 8, $ticket['seat_number'], 0, 1);

    $display_price = $ticket['final_price'] !== null ? $ticket['final_price'] : $ticket['original_price'];
    $pdf->Cell(40, 8, fix_tr('Ödenen Tutar:'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 12); 
    $pdf->Cell(0, 8, number_format($display_price, 2, ',', '.') . ' TL', 0, 1);
    $pdf->SetFont('Helvetica', '', 12); 
    if ($ticket['coupon_code']) {
        $pdf->Cell(40, 8, fix_tr('Kullanılan Kupon:'), 0, 0);
        $pdf->Cell(0, 8, fix_tr($ticket['coupon_code']), 0, 1);
        $pdf->Cell(40, 8, fix_tr('Orijinal Fiyat:'), 0, 0);
        $pdf->Cell(0, 8, number_format($ticket['original_price'], 2, ',', '.') . ' TL', 0, 1);
    }

    $pdf->Cell(40, 8, fix_tr('Bilet ID:'), 0, 0);
    $pdf->Cell(0, 8, $ticket['booking_id'], 0, 1);
    $pdf->Cell(40, 8, fix_tr('Satın Alınma:'), 0, 0);
    $pdf->Cell(0, 8, date('d M Y, H:i', strtotime($ticket['booking_time'])), 0, 1);
    $pdf->Ln(15); 

    $pdf->SetFont('Helvetica', 'I', 10);
    $pdf->Cell(0, 10, fix_tr('İyi yolculuklar dileriz! - ' . SITE_NAME), 0, 0, 'C');
    $pdf->Output('D', SITE_NAME.'_Bilet_' . $ticket['booking_id'] . '.pdf');
    exit;

} catch (Exception $e) {
    die("Bilet oluşturulurken bir hata oluştu: " . $e->getMessage());
}
?>