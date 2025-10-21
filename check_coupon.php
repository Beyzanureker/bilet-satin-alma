<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
$response = [
    'success' => false,
    'message' => '',
    'new_price' => 0,
    'discount_amount' => 0
];

try {
    $coupon_code = trim($_POST['coupon_code']);
    $trip_id = intval($_POST['trip_id']);

    if (empty($coupon_code) || empty($trip_id)) {
        throw new Exception('Kupon kodu veya sefer ID boş.');
    }
    $stmt_trip = $pdo->prepare("SELECT price, company_id FROM Trips WHERE id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception('Sefer bulunamadı.');
    }
    $now = date('Y-m-d');
    $sql_coupon = "SELECT * FROM Coupons 
                   WHERE code = ? AND usage_limit > 0 AND expire_date >= ?
                   AND (company_id = ? OR company_id IS NULL)";
    $stmt_coupon = $pdo->prepare($sql_coupon);
    $stmt_coupon->execute([$coupon_code, $now, $trip['company_id']]);
    $coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        $discount_amount = $trip['price'] * ($coupon['discount_rate'] / 100);
        $final_price = $trip['price'] - $discount_amount;
        
        $response['success'] = true;
        $response['message'] = "Kupon başarıyla uygulandı! %" . $coupon['discount_rate'] . " indirim kazandınız.";
        $response['new_price'] = round($final_price, 2); // Fiyatı yuvarla
        $response['discount_amount'] = round($discount_amount, 2);
    } else {
        throw new Exception('Geçersiz, süresi dolmuş veya bu sefer için kullanılamaz bir kupon girdiniz.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>