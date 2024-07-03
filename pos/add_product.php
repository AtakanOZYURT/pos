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

// Kategorileri ve KDV oranlarını çekme
$categories = $userConn->query("SELECT id, group_name, vat_rate FROM product_groups")->fetch_all(MYSQLI_ASSOC);

// AJAX isteklerini işleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['barcode'])) {
        echo searchProductByBarcode($_POST['barcode']);
        exit();
    } elseif (isset($_POST['product_name'])) {
        echo searchProductByName($_POST['product_name']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Ekle</title>
    <link rel="stylesheet" href="css/add_product.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#category").on("change", function() {
                var vatRate = $(this).find('option:selected').data('vat');
                if (vatRate !== undefined) {
                    $("#vat_rate").val(vatRate);
                }
            });

            // Sayfa yüklendiğinde ilk kategori seçiliyken KDV oranını ayarla
            var initialVatRate = $("#category").find('option:selected').data('vat');
            if (initialVatRate !== undefined) {
                $("#vat_rate").val(initialVatRate);
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <a href="products.php" class="btn btn-back">← Geri</a>
        <h2>Ürün Ekle</h2>
        <form action="save_product.php" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="product_barcode">Ürün Barkodu:</label>
                    <input type="text" id="product_barcode" name="product_barcode">
                    <div id="barcode_result" class="result-dropdown"></div>
                </div>
                <div class="form-group">
                    <label for="product_name">Ürün Adı:</label>
                    <input type="text" id="product_name" name="product_name" required>
                    <div id="name_result" class="result-dropdown"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="category">Kategori:</label>
                    <select id="category" name="category" required>
                        <option value="">Seçiniz</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" data-vat="<?= $category['vat_rate'] ?>"><?= $category['group_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vat_rate">KDV Oranı:</label>
                    <select id="vat_rate" name="vat_rate" required>
                        <option value="0">0%</option>
                        <option value="1">1%</option>
                        <option value="10">10%</option>
                        <option value="20">20%</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="unit">Ürün Birimi:</label>
                    <select id="unit" name="unit" required>
                        <option value="">Seçiniz</option>
                        <option value="Adet">Adet</option>
                        <option value="Kg">Kg</option>
                        <option value="Metre">Metre</option>
                        <option value="Paket">Paket</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="purchase_price_excl_vat">KDV Hariç Alış Fiyatı:</label>
                    <input type="number" id="purchase_price_excl_vat" name="purchase_price_excl_vat" class="currency-format" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="purchase_price_incl_vat">KDV Dahil Alış Fiyatı:</label>
                    <input type="number" id="purchase_price_incl_vat" name="purchase_price_incl_vat" class="currency-format" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="sale_price_incl_vat">Satış Fiyatı (KDV Dahil):</label>
                    <input type="number" id="sale_price_incl_vat" name="sale_price_incl_vat" class="currency-format" step="0.01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="profit_margin">Kar Oranı:</label>
                    <input type="number" id="profit_margin" name="profit_margin" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="stock_code">Stok Kodu:</label>
                    <input type="text" id="stock_code" name="stock_code" required>
                </div>
                <div class="form-group">
                    <label for="fast_product_order">Hızlı Ürün Sırası:</label>
                    <input type="number" id="fast_product_order" name="fast_product_order" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="product_status">Ürün Satışa:</label>
                    <select id="product_status" name="product_status" required>
                        <option value="Açık">Açık</option>
                        <option value="Kapalı">Kapalı</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="product_description">Ürün Açıklaması:</label>
                    <textarea id="product_description" name="product_description" rows="4"></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ürün Kaydet</button>
                <button type="reset" class="btn btn-secondary">Ürün Sil</button>
            </div>
        </form>
    </div>
</body>
</html>
