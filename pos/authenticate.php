<?php
session_start();
include 'db.php'; // Veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emailOrPhone = $_POST['emailOrPhone'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE (email='$emailOrPhone' OR phone='$emailOrPhone') AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $_SESSION['user'] = mysqli_fetch_assoc($result);
        header("Location: dashboard.php"); // Giriş başarılı, yönlendirme
    } else {
        echo "Hatalı e-posta/telefon veya şifre.";
    }
}
?>
