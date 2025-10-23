<?php
ob_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php?error=Bilet almak için giriş yapmalısınız.&intended=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'Koltuk Seçimi';
require_once 'header.php';


$trip = null; $booked_seats_info = []; $error_message = ''; $success_message = '';
$user_id = $_SESSION['user_id']; $trip_id = null; $final_price = 0; $original_price = 0;


if (!isset($_GET['trip_id']) || !is_numeric($_GET['trip_id'])) {
    $error_message = "Geçersiz sefer ID'si.";
} else {
    $trip_id = $_GET['trip_id'];
    try {
        $sql = "SELECT Trips.*, Companies.id as company_id, Companies.name as company_name FROM Trips JOIN Companies ON Trips.company_id = Companies.id WHERE Trips.id = ?";
        $stmt = $pdo->prepare($sql); $stmt->execute([$trip_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($trip) {
            $original_price = $trip['price'];
            $final_price = $original_price;
            $sql_seats = "SELECT seat_number, gender FROM Bookings WHERE trip_id = ?";
            $stmt_seats = $pdo->prepare($sql_seats); $stmt_seats->execute([$trip_id]);
            $booked_seats_info = $stmt_seats->fetchAll(PDO::FETCH_KEY_PAIR);
        } else { $error_message = "Sefer bulunamadı."; }
    } catch (PDOException $e) { $error_message = "Veritabanı hatası: " . $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seat_number']) && !empty($_POST['seat_number']) && $trip) { 
    $selected_seat = intval($_POST['seat_number']);
    $selected_gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $coupon_code = isset($_POST['final_coupon_code']) ? trim($_POST['final_coupon_code']) : '';
    $final_price = isset($_POST['final_price']) ? floatval($_POST['final_price']) : $trip['price'];

    try {
         $stmt_seats = $pdo->prepare("SELECT seat_number, gender FROM Bookings WHERE trip_id = ?");
         $stmt_seats->execute([$trip_id]);
         $current_booked_seats = $stmt_seats->fetchAll(PDO::FETCH_KEY_PAIR);

        if (array_key_exists($selected_seat, $current_booked_seats)) { throw new Exception("Bu koltuk siz işlem yaparken doldu. Lütfen başka bir koltuk seçin."); }
        if (empty($selected_gender) || ($selected_gender != 'male' && $selected_gender != 'female')) { throw new Exception("Lütfen geçerli bir cinsiyet seçin."); }

        $adjacent_seat = null; $mod4 = $selected_seat % 4;
        if ($mod4 === 1) $adjacent_seat = $selected_seat + 1; else if ($mod4 === 2) $adjacent_seat = $selected_seat - 1;
        else if ($mod4 === 3) $adjacent_seat = $selected_seat + 1; else if ($mod4 === 0) $adjacent_seat = $selected_seat - 1;
        if ($adjacent_seat && $adjacent_seat > 0 && $adjacent_seat <= $trip['seat_count'] && array_key_exists($adjacent_seat, $current_booked_seats) && $current_booked_seats[$adjacent_seat] != $selected_gender) { throw new Exception("Seçtiğiniz koltuğun yanındaki koltuk farklı cinsiyet tarafından rezerve edilmiş.");}

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT balance FROM Users WHERE id = ?"); $stmt->execute([$user_id]); $user_balance = $stmt->fetchColumn();
        if ($user_balance >= $final_price) {
            $new_balance = $user_balance - $final_price;
            $stmt_update = $pdo->prepare("UPDATE Users SET balance = ? WHERE id = ?"); $stmt_update->execute([$new_balance, $user_id]);
            $stmt_insert = $pdo->prepare("INSERT INTO Bookings (user_id, trip_id, seat_number, gender, coupon_code, final_price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->execute([$user_id, $trip_id, $selected_seat, $selected_gender, (!empty($coupon_code) ? $coupon_code : NULL), $final_price]);
            if (!empty($coupon_code)) { $stmt_coupon_update = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE code = ?"); $stmt_coupon_update->execute([$coupon_code]); }
            $pdo->commit();
            $success_message = "Biletiniz başarıyla satın alındı! Koltuk No: $selected_seat. Ödenen Tutar: " . number_format($final_price, 2, ',', '.') . " TL.";
            $booked_seats_info[$selected_seat] = $selected_gender; 
        } else { throw new Exception("Yetersiz Bakiye! Mevcut: " . number_format($user_balance, 2, ',', '.') . " TL, Gereken: " . number_format($final_price, 2, ',', '.') . " TL"); }
    } catch (Exception $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } $error_message = $e->getMessage(); }
}
?>

<?php if (!empty($error_message) && !$trip): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php elseif ($trip): ?>
    <h2 class="mb-4">Yolculuk Detayları & Koltuk Seçimi</h2>

    <div id="price-display" class="mb-3 fs-5 alert alert-secondary">
        <strong>Sefer:</strong> <?php echo htmlspecialchars($trip['departure_location']); ?> &rarr; <?php echo htmlspecialchars($trip['arrival_location']); ?> |
        <strong>Firma:</strong> <?php echo htmlspecialchars($trip['company_name']); ?> |
        <strong>Fiyat:</strong>
        <span id="original-price" data-price="<?php echo $original_price; ?>"><?php echo number_format($original_price, 2, ',', '.'); ?> TL</span>
        <span id="new-price-display" class="text-success fw-bold" style="display:none;"></span>
    </div>

    <div id="alert-messages" class="my-3"></div>

    <?php if (!empty($success_message)) echo "<div class='alert alert-success'>".htmlspecialchars($success_message)."</div>"; ?>
    <?php if (!empty($error_message) && empty($success_message)) echo "<div class='alert alert-danger'>".htmlspecialchars($error_message)."</div>"; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($success_message)): ?>
                <form action="buy_ticket.php?trip_id=<?php echo $trip_id; ?>" method="POST" id="buy-form">

                    <div class="mb-4 text-center gender-selection">
                        <h5>Cinsiyetinizi Seçin:</h5>
                        <input type="radio" class="btn-check" id="gender_male" name="gender" value="male" required>
                        <label for="gender_male" class="btn btn-outline-primary"><i class="bi bi-gender-male"></i> Erkek</label>
                        <input type="radio" class="btn-check" id="gender_female" name="gender" value="female" required>
                        <label for="gender_female" class="btn btn-outline-danger"><i class="bi bi-gender-female"></i> Kadın</label>
                    </div>

                    <h5 class="card-title text-center">Lütfen Koltuğunuzu Seçiniz</h5>
                    <hr>
                    <div id="seat-map-container" class="mb-3">
                         <div class="text-center text-muted mb-2"><small><i class="bi bi-arrow-up"></i> Ön Taraf</small></div>
                        <?php
                        $totalSeats = $trip['seat_count'];
                        for ($i = 1; $i <= $totalSeats; $i += 4) {
                            echo '<div class="seat-row">'; 
                            $seat1 = $i; $seat2 = $i + 1; $seat3 = $i + 2; $seat4 = $i + 3;
                          
                            if ($seat1 <= $totalSeats) echo '<div class="seat" data-seat-number="' . $seat1 . '">' . $seat1 . '</div>'; else echo '<div class="seat aisle"></div>'; 
                            if ($seat2 <= $totalSeats) echo '<div class="seat" data-seat-number="' . $seat2 . '">' . $seat2 . '</div>'; else echo '<div class="seat aisle"></div>'; 
                            
                            echo '<div class="seat aisle"></div>';
                          
                            if ($seat3 <= $totalSeats) echo '<div class="seat" data-seat-number="' . $seat3 . '">' . $seat3 . '</div>'; else echo '<div class="seat aisle"></div>'; 
                            if ($seat4 <= $totalSeats) echo '<div class="seat" data-seat-number="' . $seat4 . '">' . $seat4 . '</div>'; else echo '<div class="seat aisle"></div>'; 
                            echo '</div>'; 
                        }
                        ?>
                         <div class="text-center text-muted mt-2"><small>Arka Taraf <i class="bi bi-arrow-down"></i></small></div>
                    </div>
                     <input type="hidden" name="seat_number" id="selected-seat-input" value="">

                    <div class="row justify-content-center mt-4">
                         <div class="col-md-6">
                            <label for="coupon-code-input" class="form-label">İndirim Kuponu:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="coupon-code-input" placeholder="Kupon kodunuz varsa girin">
                                <button class="btn btn-outline-secondary" type="button" id="apply-coupon-btn">Uygula</button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="final_price" id="final-price-input" value="<?php echo $original_price; ?>">
                    <input type="hidden" name="final_coupon_code" id="final-coupon-input" value="">

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="buy-button" disabled>Satın Al</button>
                    </div>
                </form>
            <?php else: ?>
                 <div class="text-center">
                    <a href="index.php" class="btn btn-secondary">Ana Sayfaya Dön</a>
                    <a href="my_tickets.php" class="btn btn-info">Biletlerim Sayfasına Git</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="alert alert-light mt-3">
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <span><span class="badge" style="background-color: #ffffff; color: #495057; border: 1px solid #adb5bd;">&nbsp;</span> Boş</span>
            <span><span class="badge" style="background-color: #add8e6; color: #000; border: 1px solid #90c5e2;">&nbsp;</span> Dolu (Erkek)</span>
            <span><span class="badge" style="background-color: #ffb6c1; color: #000; border: 1px solid #f7a0ac;">&nbsp;</span> Dolu (Kadın)</span>
             <span><span class="badge" style="background-color: var(--bs-success); color: #000; border: 1px solid #e6ac00;">&nbsp;</span> Seçilen</span>
            <span><span class="badge" style="background-color: #e9ecef; color: #adb5bd; border: 1px solid #dee2e6;">&nbsp;</span> Seçilemez</span>
        </div>
    </div>
<?php endif; ?>

<?php
echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>';
require_once 'footer.php';
?>

<script>
$(document).ready(function() {
   
    const bookedSeats = <?php echo json_encode($booked_seats_info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || {};
    const totalSeats = <?php echo $trip ? intval($trip['seat_count']) : 0; ?>;
    const tripId = <?php echo $trip_id ? intval($trip_id) : 'null'; ?>;
    const originalPrice = <?php echo $trip ? floatval($trip['price']) : 0; ?>;
    let selectedSeat = null;
    let selectedGender = null;

  
    function showAlert(message, type = 'warning') {
        const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert"> ${$('<div>').text(message).html()} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> </div>`;
        $('#alert-messages').html(alertHtml);
    }

    
    function refreshSeatsUI() {
        $('.seat[data-seat-number]').each(function() {
            const seatNumber = parseInt($(this).data('seat-number'));
            const seatElement = $(this);

           
            seatElement.removeClass('booked-male booked-female selected disabled-adjacent')
                       .prop('disabled', false) 
                       .css('cursor', 'pointer');

            
            if (bookedSeats.hasOwnProperty(seatNumber)) {
                const gender = bookedSeats[seatNumber];
                seatElement.addClass(gender === 'male' ? 'booked-male' : 'booked-female').prop('disabled', true);
            }
            
            else if (selectedGender) {
                let adjacentSeat = null; const mod4 = seatNumber % 4;
                if (mod4 === 1) adjacentSeat = seatNumber + 1; else if (mod4 === 2) adjacentSeat = seatNumber - 1;
                else if (mod4 === 3) adjacentSeat = seatNumber + 1; else if (mod4 === 0) adjacentSeat = seatNumber - 1;

                if (adjacentSeat && adjacentSeat > 0 && adjacentSeat <= totalSeats && bookedSeats.hasOwnProperty(adjacentSeat) && bookedSeats[adjacentSeat] !== selectedGender) {
                    seatElement.addClass('disabled-adjacent').prop('disabled', true);
                }
            }
        });

        
        if(selectedSeat && !$('.seat[data-seat-number="' + selectedSeat + '"]').prop('disabled')){
             $('.seat[data-seat-number="' + selectedSeat + '"]').addClass('selected');
             $('#buy-button').prop('disabled', false);
        } else {
             selectedSeat = null;
             $('#selected-seat-input').val('');
             $('#buy-button').prop('disabled', true);
             $('.seat.selected').removeClass('selected');
        }
    }

    $('input[name="gender"]').on('change', function() {
        selectedGender = $(this).val();
        selectedSeat = null; 
        $('#selected-seat-input').val('');
        refreshSeatsUI(); 
        $('#alert-messages').html('');
    });

    
    $('#seat-map-container').on('click', '.seat[data-seat-number]:not([disabled])', function() {
        if (!selectedGender) {
            showAlert('Lütfen önce cinsiyetinizi seçin.');
            return;
        }
        $('.seat.selected').removeClass('selected'); 
        selectedSeat = parseInt($(this).data('seat-number'));
        $(this).addClass('selected'); 
        $('#selected-seat-input').val(selectedSeat); 
        $('#buy-button').prop('disabled', false); 
        $('#alert-messages').html('');
    });

   
     $('#apply-coupon-btn').on('click', function() {
        var couponCode = $('#coupon-code-input').val();
        if (couponCode === '' || !tripId) {
             showAlert('Lütfen bir kupon kodu girin.', 'warning');
            return;
        }
        $.ajax({
            type: 'POST', url: 'check_coupon.php', data: { coupon_code: couponCode, trip_id: tripId }, dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.success ? 'success' : 'danger');
                if (response.success) {
                    $('#original-price').addClass('text-decoration-line-through text-danger');
                    $('#new-price-display').text(response.new_price.toFixed(2).replace('.', ',') + ' TL').show();
                    $('#final-price-input').val(response.new_price);
                    $('#final-coupon-input').val(couponCode);
                } else {
                    $('#original-price').removeClass('text-decoration-line-through text-danger');
                    $('#new-price-display').hide();
                    $('#final-price-input').val(originalPrice);
                    $('#final-coupon-input').val('');
                }
            },
            error: function() { showAlert('Kupon denetlenirken bir sunucu hatası oluştu.', 'danger'); }
        });
     });

    
    refreshSeatsUI();
});
</script>