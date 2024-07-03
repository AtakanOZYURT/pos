<?php
$servername = "localhost";
$username = "root";
$password = "123456"; // Buraya yeni belirlediğiniz şifreyi girin
$dbname = "pos";

// Veritabanı bağlantısı oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
?>
