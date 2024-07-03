<?php
include 'db.php'; // Veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = mysqli_real_escape_string($conn, $_POST['user_id']);
    $licenseDuration = mysqli_real_escape_string($conn, $_POST['license_duration']); // Örneğin '1 month', '1 year'

    // Lisans başlangıç ve bitiş tarihlerini hesaplayın
    $licenseStartDate = date('Y-m-d');
    $licenseEndDate = date('Y-m-d', strtotime("+$licenseDuration"));

    // Kullanıcı lisans bilgilerini güncelleme
    $sql = "UPDATE users SET license_start_date='$licenseStartDate', license_end_date='$licenseEndDate', status='active' WHERE id='$userId'";

    if (mysqli_query($conn, $sql)) {
        echo "Lisans güncellendi!";
    } else {
        echo "Hata: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
