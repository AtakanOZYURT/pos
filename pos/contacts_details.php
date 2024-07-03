<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Kullanıcının oturum açıp açmadığını kontrol et
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kullanıcının veritabanı adı
$userDbName = "user_db_" . $_SESSION['user_id'];

// Kullanıcının özel veritabanına bağlan
$userConn = new mysqli($servername, $username, $password, $userDbName);

// Bağlantıyı kontrol et
if ($userConn->connect_error) {
    die("Veritabanına bağlanırken hata oluştu: " . $userConn->connect_error);
}

$type = $_GET['type'];
$id = intval($_GET['id']);

if ($type == 'customer') {
    $detailsSql = "SELECT * FROM customers WHERE id = ?";
} else if ($type == 'supplier') {
    $detailsSql = "SELECT * FROM suppliers WHERE id = ?";
} else {
    die("Geçersiz tür.");
}

$stmt = $userConn->prepare($detailsSql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$details = $result->fetch_assoc();
$stmt->close();
$userConn->close();

if (!$details) {
    die("Kayıt bulunamadı.");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaylar</title>
    <link rel="stylesheet" href="css/contacts_details.css">
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <div class="header-buttons">
            <div class="back-button">
                <a href="contacts.php" class="button-blue">← Geri</a>
            </div>
            <div class="buttons-right">
                <button class="button-green">Tahsilat Ekle</button>
                <button class="button-blue">Ödeme Ekle</button>
                <button class="button-yellow">Verisiye Borç Ekle</button>
                <button class="button-red">Müşteriyi Sil</button>
            </div>
        </div>
        <h2><?php echo ($type == 'customer') ? "Müşteri Detayları" : "Tedarikçi Detayları"; ?></h2>
        <div class="details-header">
            <h3><?php echo ($type == 'customer') ? htmlspecialchars($details['first_name'] . " " . $details['last_name']) : htmlspecialchars($details['supplier_name']); ?></h3>
        </div>
        <div class="details-body">
            <div class="details-stats">
                <div class="stat-card">
                    <h4>Toplam Satış</h4>
                    <p>₺ 0,00</p>
                </div>
                <div class="stat-card">
                    <h4>Toplam Borç</h4>
                    <p>₺ 0,00</p>
                </div>
                <div class="stat-card">
                    <h4>Ödeme</h4>
                    <p>₺ 0,00</p>
                </div>
                <div class="stat-card">
                    <h4>Kalan Borç</h4>
                    <p>₺ 0,00</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
