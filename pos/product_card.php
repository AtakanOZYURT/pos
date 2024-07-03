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

// Ürünleri çekme
$products = $userConn->query("SELECT * FROM products")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Kartları</title>
    <link rel="stylesheet" href="css/product_card.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <a href="products.php" class="btn btn-back">← Geri</a>
        <div class="header">
            <h2>Ürün Kartları</h2>
            <div class="buttons">
                <a href="add_product.php" class="btn btn-primary">Ürün Ekle</a>
                <a href="update_product.php" class="btn btn-secondary">Ürün Güncelle</a>
                <a href="delete_product.php" class="btn btn-danger">Ürün Sil</a>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ürün Barkodu</th>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Ürün Birimi</th>
                        <th>KDV Hariç Alış Fiyatı</th>
                        <th>KDV Dahil Alış Fiyatı</th>
                        <th>Satış Fiyatı (KDV Dahil)</th>
                        <th>Kar Oranı</th>
                        <th>Stok Kodu</th>
                        <th>Hızlı Ürün Sırası</th>
                        <th>Ürün Satışa</th>
                        <th>Ürün Açıklaması</th>
                        <th>Oluşturulma Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['product_barcode'] ?></td>
                        <td><?= $product['product_name'] ?></td>
                        <td><?= $product['category_id'] ?></td>
                        <td><?= $product['product_unit'] ?></td>
                        <td><?= $product['purchase_price_excl_vat'] ?></td>
                        <td><?= $product['purchase_price_incl_vat'] ?></td>
                        <td><?= $product['sale_price_incl_vat'] ?></td>
                        <td><?= $product['profit_margin'] ?></td>
                        <td><?= $product['stock_code'] ?></td>
                        <td><?= $product['fast_product_order'] ?></td>
                        <td><?= $product['product_status'] ?></td>
                        <td><?= $product['product_description'] ?></td>
                        <td><?= $product['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
