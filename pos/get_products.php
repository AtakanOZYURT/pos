<?php
session_start();
include 'db.php';

// Kullanıcının veritabanı adı
$userDbName = "user_db_" . $_SESSION['user_id'];
$userConn = new mysqli($servername, $username, $password, $userDbName);

// Bağlantıyı kontrol et
if ($userConn->connect_error) {
    die("Veritabanına bağlanırken hata oluştu: " . $userConn->connect_error);
}

$category = $_GET['category'];
$query = "SELECT product_name, sale_price_incl_vat FROM products WHERE category_id = (SELECT id FROM product_groups WHERE group_name = ?) AND product_status != 'KAPALI'";
$stmt = $userConn->prepare($query);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'name' => $row['product_name'],
        'price' => $row['sale_price_incl_vat']
    ];
}

echo json_encode($products);
?>
