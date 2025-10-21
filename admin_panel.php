<?php
$page_title = 'Yönetim Paneli';
require_once 'header.php'; 

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'Admin' && $_SESSION['user_role'] != 'Firma Admin')) {
    header("Location: index.php?error=unauthorized");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id']; 
$stats = [
    'total_users' => 0,
    'total_companies' => 0,
    'total_trips' => 0,
    'total_bookings' => 0,
    'my_company_trips' => 0,
    'my_company_bookings' => 0,
];

try {
    if ($user_role == 'Admin') {
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
        $stats['total_companies'] = $pdo->query("SELECT COUNT(*) FROM Companies")->fetchColumn();
        $stats['total_trips'] = $pdo->query("SELECT COUNT(*) FROM Trips")->fetchColumn();
        $stats['total_bookings'] = $pdo->query("SELECT COUNT(*) FROM Bookings")->fetchColumn();
    } elseif ($user_role == 'Firma Admin') {
        $stmt_company = $pdo->prepare("SELECT company_id FROM Users WHERE id = ?");
        $stmt_company->execute([$user_id]);
        $company_id = $stmt_company->fetchColumn();

        if ($company_id) {
            $stmt_my_trips = $pdo->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
            $stmt_my_trips->execute([$company_id]);
            $stats['my_company_trips'] = $stmt_my_trips->fetchColumn();

            $stmt_my_bookings = $pdo->prepare("SELECT COUNT(*) FROM Bookings JOIN Trips ON Bookings.trip_id = Trips.id WHERE Trips.company_id = ?");
            $stmt_my_bookings->execute([$company_id]);
            $stats['my_company_bookings'] = $stmt_my_bookings->fetchColumn();
        }
    }
} catch (PDOException $e) {
     error_log("İstatistik çekme hatası: " . $e->getMessage());
}

?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group shadow-sm">
                <a href="admin_panel.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : ''; ?>">
                    Genel Bakış
                </a>

                <?php if ($user_role == 'Admin'): ?>
                    <a href="admin_companies.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_companies.php' ? 'active' : ''; ?>">Firma Yönetimi</a>
                    <a href="admin_users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">Firma Admini Yönetimi</a>
                    <a href="admin_coupons.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_coupons.php' ? 'active' : ''; ?>">Global Kupon Yönetimi</a>
                <?php endif; ?>

                <?php if ($user_role == 'Firma Admin'): ?>
                    <a href="admin_trips.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_trips.php' ? 'active' : ''; ?>">Sefer Yönetimi</a>
                    <a href="admin_coupons_f.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_coupons_f.php' ? 'active' : ''; ?>">Firma Kupon Yönetimi</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-9">
            <div class="rounded-3 admin-content-bg mb-4 shadow-sm">
                <div class="container-fluid py-3">
                    <h1 class="display-5 fw-bold">Yönetim Paneline Hoş Geldiniz!</h1>
                    <p class="fs-4">Hoş Geldin, <?php echo htmlspecialchars($_SESSION['user_fullname']); ?> (Rol: <?php echo htmlspecialchars($user_role); ?>)</p>
                    <p>Sol taraftaki menüden yönetmek istediğiniz alanı seçebilirsiniz.</p>
                </div>
            </div>

            <h2>Hızlı İstatistikler</h2>
             <hr class="mb-4">
             <div class="row">

                 <?php if ($user_role == 'Admin'): ?>
                     <div class="col-md-3 mb-3">
                         <div class="card text-white bg-primary shadow-sm h-100">
                             <div class="card-header">Toplam Kullanıcı</div>
                             <div class="card-body">
                                 <h4 class="card-title"><?php echo $stats['total_users']; ?></h4>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3 mb-3">
                         <div class="card text-white bg-secondary shadow-sm h-100">
                             <div class="card-header">Toplam Firma</div>
                             <div class="card-body">
                                 <h4 class="card-title"><?php echo $stats['total_companies']; ?></h4>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3 mb-3">
                         <div class="card text-white bg-info shadow-sm h-100">
                             <div class="card-header">Toplam Sefer</div>
                             <div class="card-body">
                                 <h4 class="card-title"><?php echo $stats['total_trips']; ?></h4>
                             </div>
                         </div>
                     </div>
                      <div class="col-md-3 mb-3">
                         <div class="card text-white bg-success shadow-sm h-100">
                             <div class="card-header">Toplam Bilet</div>
                             <div class="card-body">
                                 <h4 class="card-title"><?php echo $stats['total_bookings']; ?></h4>
                             </div>
                         </div>
                     </div>
                 <?php endif; ?>

                 <?php if ($user_role == 'Firma Admin'): ?>
                      <div class="col-md-6 mb-3">
                         <div class="card text-white bg-info shadow-sm h-100">
                             <div class="card-header">Firmanızın Sefer Sayısı</div>
                             <div class="card-body">
                                 <h4 class="card-title"><?php echo $stats['my_company_trips']; ?></h4>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-6 mb-3">
                         <div class="card text-white bg-success shadow-sm h-100">
                             <div class="card-header">Firmanızın Satılan Bilet Sayısı</div>
                             <div class="card-body">
                                 <h4 class="card-title"><?php echo $stats['my_company_bookings']; ?></h4>
                             </div>
                         </div>
                     </div>
                 <?php endif; ?>

             </div>
             </div> </div> </div> <?php
require_once 'footer.php';
?>