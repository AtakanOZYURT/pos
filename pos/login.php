<?php
session_start();
include 'db.php'; // Veritabanı bağlantı bilgilerini içeren dosya

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Mesajı bir kez gösterdikten sonra oturumdan kaldır
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al ve temizle
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Email adresinin var olup olmadığını kontrol et
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Lisans bitiş tarihini kontrol et
        $currentDate = date('Y-m-d');
        if ($currentDate > $user['license_end_date']) {
            $_SESSION['message'] = 'Lisans süreniz dolmuştur. Lisans satın alarak programı kullanmaya devam edebilirsiniz.';
            header("Location: login.php");
            exit();
        }

        // Şifreyi kontrol et
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['company'] = $user['company']; // Şirket adını oturuma kaydet
            $_SESSION['license_end_date'] = $user['license_end_date']; // Lisans bitiş tarihini oturuma kaydet
            header("Location: dashboard.php"); 
            exit();
        } else {
            $error = "Mailiniz ve Şifreniz yanlış."; // Hata mesajını ayarla
        }
    } else {
        $error = "Mailiniz ve Şifreniz yanlış."; // Hata mesajını ayarla
    }

    $stmt->close();
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Giriş Yap</h2>
        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" autocomplete="email" required><br><br>
            <label for="password">Şifre:</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required><br><br>
            <div class="forgot-password">
                <a href="forgot_password.php">Şifremi Unuttum</a>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
        <div class="links">
            <a href="register.php">Kayıt Ol</a>
        </div>
    </div>
</body>
</html>
