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

// Müşterileri veritabanından çek
$sql = "SELECT id, first_name, last_name, purchase_count, debt_amount FROM customers ORDER BY id ASC";
$result = $userConn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteriler</title>
    <link rel="stylesheet" href="css/customers.css">
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <div class="back-button">
            <a href="contacts.php" class="button-blue">← Geri</a>
        </div>
        <h1>Müşteriler</h1>
        <table>
            <thead>
                <tr>
                    <th>Sıra No</th>
                    <th>Müşteri Adı Soyadı</th>
                    <th>Alışveriş Sayısı</th>
                    <th>Borç Miktarı</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $index = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $index++; ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['purchase_count']); ?></td>
                            <td><?php echo htmlspecialchars($row['debt_amount']); ?></td>
                            <td><a href="edit_customer.php?id=<?php echo $row['id']; ?>">Düzenle</a> | <a href="delete_customer.php?id=<?php echo $row['id']; ?>">Sil</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Kayıt bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php $userConn->close(); ?>
    </div>
</body>
</html>
