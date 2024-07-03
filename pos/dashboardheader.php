<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcının oturum açıp açmadığını kontrol et
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentDate = date('Y-m-d');
$licenseEndDate = $_SESSION['license_end_date'];
$daysLeft = (strtotime($licenseEndDate) - strtotime($currentDate)) / (60 * 60 * 24);

if ($daysLeft < 0) {
    session_destroy();
    header("Location: login.php?message=Lisans süreniz dolmuştur. Lisans satın alarak programı kullanmaya devam edebilirsiniz.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Header</title>
    <link rel="stylesheet" href="css/dashboardheader.css">
</head>
<body>
    <header>
        <div class="logo">Alba</div>
        <div class="company-info">
            <span class="company-name"><?php echo htmlspecialchars($_SESSION['company']); ?></span>
            <span class="license-info">
                <?php
                if ($daysLeft == 0) {
                    echo "Lisans Bitiş Tarihinin Son Günü";
                } else {
                    echo "Lisans Bitişine " . $daysLeft . " Gün Kaldı";
                }
                ?>
            </span>
            <?php if ($daysLeft <= 30 && $daysLeft > 0): ?>
                <button class="buy-license" onclick="window.location.href='buy_license.php'">Lisans Satın Al</button>
            <?php endif; ?>
        </div>
        <div class="logout">
            <form action="logout.php" method="post">
                <button type="submit">Çıkış</button>
            </form>
        </div>
    </header>
</body>
</html>
