<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="register-container">
        <h2>Alba</h2>
        <h3>Kayıt Ol!</h3>
        <form action="register_action.php" method="post">
            <label for="firstName">İsim</label>
            <input type="text" id="firstName" name="firstName" required>

            <label for="lastName">Soyisim</label>
            <input type="text" id="lastName" name="lastName" required>

            <label for="company">Firma</label>
            <input type="text" id="company" name="company" required>

            <label for="phone">Telefon</label>
            <input type="text" id="phone" name="phone" required>

            <label for="city">Şehir seçiniz</label>
            <select id="city" name="city" required>
                <option value="">Şehir seçiniz</option>
                <option value="Istanbul">İstanbul</option>
                <option value="Ankara">Ankara</option>
                <!-- Diğer şehirler -->
            </select>

            <label for="email">E-posta</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>

            <label for="passwordConfirm">Parola (Tekrar)</label>
            <input type="password" id="passwordConfirm" name="passwordConfirm" required>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">Kullanıcı sözleşmesini ve gizlilik ilkelerini okudum ve kabul ediyorum.</label>
            </div>

            <button type="submit">Kayıt Ol!</button>
        </form>
        <p>Zaten üye misiniz? <a href="login.php">Giriş Yapın!</a></p>
    </div>
</body>
</html>
