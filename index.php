<?php
$page_title = 'Ana Sayfa';
require_once 'header.php';

$provinces = [
    "Adana", "Adıyaman", "Afyonkarahisar", "Ağrı", "Amasya", "Ankara", "Antalya", "Artvin",
    "Aydın", "Balıkesir", "Bilecik", "Bingöl", "Bitlis", "Bolu", "Burdur", "Bursa",
    "Çanakkale", "Çankırı", "Çorum", "Denizli", "Diyarbakır", "Edirne", "Elazığ",
    "Erzincan", "Erzurum", "Eskişehir", "Gaziantep", "Giresun", "Gümüşhane", "Hakkari",
    "Hatay", "Isparta", "Mersin", "İstanbul", "İzmir", "Kars", "Kastamonu", "Kayseri",
    "Kırklareli", "Kırşehir", "Kocaeli", "Konya", "Kütahya", "Malatya", "Manisa",
    "Kahramanmaraş", "Mardin", "Muğla", "Muş", "Nevşehir", "Niğde", "Ordu", "Rize",
    "Sakarya", "Samsun", "Siirt", "Sinop", "Sivas", "Tekirdağ", "Tokat", "Trabzon",
    "Tunceli", "Şanlıurfa", "Uşak", "Van", "Yozgat", "Zonguldak", "Aksaray", "Bayburt",
    "Karaman", "Kırıkkale", "Batman", "Şırnak", "Bartın", "Ardahan", "Iğdır", "Yalova",
    "Karabük", "Kilis", "Osmaniye", "Düzce"
];
sort($provinces);

$trips = [];
$search_error = '';
$is_search_performed = isset($_GET['departure_location']); 

if ($is_search_performed) {
    $departure_location = trim($_GET['departure_location']);
    $arrival_location = trim($_GET['arrival_location']);
    $departure_date = trim($_GET['departure_date']);

    if (empty($departure_location) || empty($arrival_location) || empty($departure_date)) {
        $search_error = "Lütfen kalkış, varış noktası ve tarih seçiniz.";
    } else {
        try {
            $sql = "SELECT Trips.*, Companies.name as company_name
                    FROM Trips
                    JOIN Companies ON Trips.company_id = Companies.id
                    WHERE departure_location LIKE ?
                    AND arrival_location LIKE ?
                    AND DATE(departure_time) = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([ '%' . $departure_location . '%', '%' . $arrival_location . '%', $departure_date ]);
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $search_error = "Seferler aranırken bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<style>
.hero-section {
    background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('img/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}
.hero-section .form-select, .hero-section .form-control {
    opacity: 0.95;
}
.trip-card .row > div { 
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.trip-card .route-column { 
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.25rem;
}
</style>

<div class="p-5 mb-4 rounded-3 text-center hero-section" id="hero-area">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold"><?php echo SITE_TAGLINE; ?></h1>
        <p class="fs-4">Hayalindeki yolculuk bir tık uzağında. Nereye gitmek istersin?</p>
        <form action="index.php" method="GET" class="row g-3 justify-content-center align-items-center">
            <div class="col-md-3">
                <select class="form-select form-select-lg" name="departure_location" required>
                    <option value="" disabled selected>Nereden?</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province); ?>" <?php echo (isset($_GET['departure_location']) && $_GET['departure_location'] == $province) ? 'selected' : ''; ?>><?php echo htmlspecialchars($province); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-lg" name="arrival_location" required>
                    <option value="" disabled selected>Nereye?</option>
                    <?php foreach ($provinces as $province): ?>
                         <option value="<?php echo htmlspecialchars($province); ?>" <?php echo (isset($_GET['arrival_location']) && $_GET['arrival_location'] == $province) ? 'selected' : ''; ?>><?php echo htmlspecialchars($province); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-lg" name="departure_date"
                       min="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo isset($_GET['departure_date']) ? htmlspecialchars($_GET['departure_date']) : date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">Sefer Bul</button>
            </div>
        </form>
    </div>
</div>

<div class="container mt-5">
    <?php if ($is_search_performed):  ?>
        <h2>Arama Sonuçları</h2>
        <?php if (!empty($search_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($search_error); ?></div>
        <?php elseif (empty($trips)): ?>
            <div class="alert alert-warning">Aradığınız kriterlere uygun sefer bulunamadı.</div>
        <?php else: ?>
            <?php foreach ($trips as $trip): ?>
                <?php
                    $departure = new DateTime($trip['departure_time']);
                    $arrival = new DateTime($trip['arrival_time']);
                    $interval = $departure->diff($arrival);
                    $duration_str = '';
                    if ($interval->h > 0) $duration_str .= $interval->format('%h Saat');
                    if ($interval->i > 0) $duration_str .= ($duration_str ? ' ' : '') . $interval->format('%i Dakika');
                    if (empty($duration_str)) $duration_str = '-';
                ?>
                <div class="card mb-3 shadow-sm trip-card">
                    <div class="card-body">
                        <div class="row align-items-center gy-3 gy-md-0 text-center text-md-start">

                            <div class="col-md-2">
                                <h6 class="card-title mb-0 fw-bold"><?php echo htmlspecialchars($trip['company_name']); ?></h6>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="fs-5 fw-bold"><?php echo $departure->format('H:i'); ?></span><br>
                                <small class="text-muted">(<?php echo $duration_str; ?>)</small>
                            </div>
                            <div class="col-md-4 text-center route-column">
                                <span class="fw-bold"><?php echo htmlspecialchars($trip['departure_location']); ?></span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="fw-bold"><?php echo htmlspecialchars($trip['arrival_location']); ?></span>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <h4 class="text-primary mb-0"><?php echo htmlspecialchars(number_format($trip['price'], 2, ',', '.')); ?> TL</h4>
                            </div>
                            <div class="col-md-2 text-md-end d-grid d-md-block">
                                <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn btn-success fw-bold">Koltuk Seç</a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach;  ?>
        <?php endif;  ?>
        <hr class="my-5">
    <?php endif;  ?>
</div> <div class="container my-5">
    <h2 class="text-center mb-4">Türkiye'nin Önde Gelen Otobüs Bileti Sitesi</h2>
    <div class="row text-center">
        <div class="col-md-6 col-lg-3 mb-4 d-flex"> <div class="card h-100 shadow-sm flex-fill"> <div class="card-body d-flex flex-column"> <i class="bi bi-headset fs-1 text-primary mb-3"></i> <h5 class="card-title">7/24 Müşteri Hizmetleri</h5> <p class="card-text mt-auto">Tüm işlemlerde müşteri hizmetleri ekibimiz 7/24 yanınızda. Bir tıkla destek ekibimize bağlanabilirsiniz.</p> </div> </div> </div>
        <div class="col-md-6 col-lg-3 mb-4 d-flex"> <div class="card h-100 shadow-sm flex-fill"> <div class="card-body d-flex flex-column"> <i class="bi bi-lock-fill fs-1 text-primary mb-3"></i> <h5 class="card-title">Güvenli Ödeme</h5> <p class="card-text mt-auto">Tüm otobüs bileti alım işlemlerinizi evinizden, ister ofisinizden kolay, hızlı ve güvenli bir şekilde gerçekleştirebilirsiniz.</p> </div> </div> </div>
        <div class="col-md-6 col-lg-3 mb-4 d-flex"> <div class="card h-100 shadow-sm flex-fill"> <div class="card-body d-flex flex-column"> <i class="bi bi-wallet-fill fs-1 text-primary mb-3"></i> <h5 class="card-title">Bütçe Dostu</h5> <p class="card-text mt-auto">Pusula size tüm firmaların otobüs biletlerini sorgulama ve karşılaştırma imkanı sunar. Uygun otobüs biletini bulabilirsiniz.</p> </div> </div> </div>
        <div class="col-md-6 col-lg-3 mb-4 d-flex"> <div class="card h-100 shadow-sm flex-fill"> <div class="card-body d-flex flex-column"> <i class="bi bi-bus-front-fill fs-1 text-primary mb-3"></i> <h5 class="card-title">Seçkin Firmalar</h5> <p class="card-text mt-auto">Pusula olarak seçkin otobüs firmalarını sizler için bir araya getirdik. Firmaları karşılaştırabilir, uygun otobüs biletini bulabilirsiniz.</p> </div> </div> </div>
    </div>
</div>

<div class="container my-5">
    <h2 class="text-center mb-4">Sıkça Sorulan Sorular</h2>
    <div class="accordion" id="faqAccordion">
        <div class="accordion-item"> <h2 class="accordion-header" id="headingOne"> <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne"> Pusula'da hangi otobüs firmaları bulunuyor? </button> </h2> <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion"> <div class="accordion-body"> Pusula ile Kamil Koç, Metro Turizm, Pamukkale, Ali Osman Ulusoy gibi Türkiye’nin dört bir yanına seferler düzenleyen yüzlerce otobüs firmasına ulaşabilirsiniz. Sistemimize sürekli yeni firmalar eklenmektedir. </div> </div> </div>
        <div class="accordion-item"> <h2 class="accordion-header" id="headingTwo"> <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo"> Pusula'dan otobüs bileti nasıl satın alabilirim? </button> </h2> <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion"> <div class="accordion-body"> Pusula üzerinden otobüs bileti satın almak için web sitemizi kullanabilirsiniz. Seyahat etmek istediğiniz yeri ve tarihi girdikten sonra tüm seferleri karşılaştırabilir, size uygun sefer için koltuk seçip bilgilerinizi girerek biletinizi satın alabilirsiniz. </div> </div> </div>
        <div class="accordion-item"> <h2 class="accordion-header" id="headingThree"> <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree"> Pusula güvenilir mi? </button> </h2> <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion"> <div class="accordion-body"> Pusula üzerinden biletinizi güvenle satın alabilirsiniz. Sitemizden yapacağınız tüm satın alım işlemleri SSL sertifikası ile güvence altındadır. Güvenliğiniz bizim için önceliklidir. </div> </div> </div>
        <div class="accordion-item"> <h2 class="accordion-header" id="headingFour"> <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour"> Pusula'dan otobüs bileti alırken komisyon ödenir mi? </button> </h2> <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion"> <div class="accordion-body"> Bilet komisyon ücreti almayız. Pusula'yı kullanarak otobüs bileti alırken sadece biletinizin fiyatını ödersiniz. Ekstra veya gizli ücretler yoktur. </div> </div> </div>
        <div class="accordion-item"> <h2 class="accordion-header" id="headingFive"> <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive"> Otobüs biletimi iptal edebilir miyim? </button> </h2> <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion"> <div class="accordion-body"> Evet, otobüs biletinizi sefer saatine 1 saat kalana kadar "Biletlerim" sayfasından kolayca iptal edebilirsiniz. İptal durumunda bilet ücreti hesabınıza iade edilir. Lütfen otobüs firmalarının kendi iptal koşullarının da geçerli olabileceğini unutmayın. </div> </div> </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>