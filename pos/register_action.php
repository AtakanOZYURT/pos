<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


include 'db.php'; // Genel veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al ve temizle
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $company = mysqli_real_escape_string($conn, $_POST['company']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $passwordConfirm = mysqli_real_escape_string($conn, $_POST['passwordConfirm']);

    // Şifrelerin eşleşip eşleşmediğini kontrol et
    if ($password !== $passwordConfirm) {
        die("Parolalar eşleşmiyor.");
    }

    // Email adresinin var olup olmadığını kontrol et
    $emailCheckSql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($emailCheckSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        die("Bu email adresi zaten kayıtlı.");
    }

    // Şifreyi hash'le
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Lisans başlangıç ve bitiş tarihlerini ayarla
    $licenseStartDate = date('Y-m-d');
    $licenseEndDate = date('Y-m-d', strtotime('+7 days'));

    // Yeni kullanıcıyı genel veritabanına kaydet
    $userInsertSql = "INSERT INTO users (first_name, last_name, company, phone, city, email, password, license_start_date, license_end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($userInsertSql);
    $stmt->bind_param("sssssssss", $firstName, $lastName, $company, $phone, $city, $email, $passwordHash, $licenseStartDate, $licenseEndDate);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $userDbName = "user_db_" . $userId;

        // Kullanıcıya özel veritabanı oluştur
        $createDbSql = "CREATE DATABASE `$userDbName`";
        if ($conn->query($createDbSql)) {
            // Ayrı bir kullanıcı oluştur ve yetki ver (Güvenlik için önerilen yöntem)
            $newUser = "user_" . $userId;
            $newPassword = bin2hex(random_bytes(10)); // Güçlü bir parola oluştur
            $createUserSql = "CREATE USER '$newUser'@'localhost' IDENTIFIED BY '$newPassword'";
            if ($conn->query($createUserSql)) {
                $grantPrivilegesSql = "GRANT ALL PRIVILEGES ON `$userDbName`.* TO '$newUser'@'localhost'";
                if ($conn->query($grantPrivilegesSql)) {
                    // Tabloları oluşturmak için kullanıcıya özel veritabanına bağlan
                    $userConn = new mysqli($servername, $newUser, $newPassword, $userDbName);

                    // Bağlantıyı kontrol etme
                    if ($userConn->connect_error) {
                        die("Kullanıcı veritabanı bağlantısı hatası: " . $userConn->connect_error);
                    }

                    // Tablo oluşturma SQL komutları
                    $createTablesSql = "
                    CREATE TABLE customers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        first_name VARCHAR(100) NOT NULL,
                        last_name VARCHAR(100) NOT NULL,
                        phone_number VARCHAR(15) NOT NULL,
                        address TEXT NOT NULL,
                        open_account_limit DECIMAL(10, 2) NOT NULL,
                        total_purchase INT DEFAULT 0,
                        total_debt DECIMAL(10, 2) DEFAULT 0.00,
                        total_payment DECIMAL(10, 2) DEFAULT 0.00
                    );

                    CREATE TABLE suppliers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        supplier_name VARCHAR(200) NOT NULL,
                        phone_number VARCHAR(15) NOT NULL,
                        address TEXT NOT NULL,
                        tax_office VARCHAR(100) NOT NULL,
                        tax_number VARCHAR(15) NOT NULL,
                        total_sales INT DEFAULT 0,
                        total_debt DECIMAL(10, 2) DEFAULT 0.00,
                        total_payment DECIMAL(10, 2) DEFAULT 0.00
                    );

                    CREATE TABLE transactions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        customer_id INT,
                        supplier_id INT,
                        product_name VARCHAR(200) NOT NULL,
                        quantity INT NOT NULL,
                        price DECIMAL(10, 2) NOT NULL,
                        total_amount DECIMAL(10, 2) NOT NULL,
                        transaction_date DATE NOT NULL,
                        payment_amount DECIMAL(10, 2) DEFAULT 0.00,
                        transaction_type ENUM('purchase', 'payment') NOT NULL,
                        FOREIGN KEY (customer_id) REFERENCES customers(id),
                        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
                    );

                    CREATE TABLE product_groups (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        group_name VARCHAR(255) NOT NULL,
                        show_on_fast_menu BOOLEAN NOT NULL DEFAULT FALSE,
                        total_products INT NOT NULL DEFAULT 0,
                        order_number INT NOT NULL DEFAULT 0,
                        vat_rate INT(11) NOT NULL
                    );

                    CREATE TABLE products (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        product_barcode VARCHAR(255) NOT NULL,
                        product_name VARCHAR(255) NOT NULL,
                        category_id INT NOT NULL,
                        product_unit VARCHAR(50) NOT NULL,
                        purchase_price_excl_vat DECIMAL(10, 2) NOT NULL,
                        purchase_price_incl_vat DECIMAL(10, 2) NOT NULL,
                        sale_price_incl_vat DECIMAL(10, 2) NOT NULL,
                        profit_margin DECIMAL(10, 2) NOT NULL,
                        vat_rate DECIMAL(5, 2) NOT NULL,
                        stock_code VARCHAR(255) NULL,
                        fast_product_order INT NOT NULL,
                        product_status ENUM('Açık', 'Kapalı') NOT NULL,
                        product_description TEXT,
                        first_purchase_date DATE,
                        last_modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        stock INT NOT NULL,
                        critical_stock INT NOT NULL
                    );                    
                    ";

                    if ($userConn->multi_query($createTablesSql)) {
                        // Kayıt başarılı, teşekkür mesajı ve yönlendirme
                        echo "<!DOCTYPE html>
                        <html>
                        <head>
                            <title>Kayıt Başarılı</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    height: 100vh;
                                    margin: 0;
                                    background-color: #f2f2f2;
                                }
                                .message-container {
                                    background-color: #fff;
                                    padding: 20px;
                                    border-radius: 8px;
                                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                                    text-align: center;
                                }
                                .message-container h1 {
                                    color: #28a745;
                                }
                                .message-container p {
                                    margin: 10px 0;
                                }
                            </style>
                        </head>
                        <body>
                            <div class='message-container'>
                                <h1>Kayıt Başarılı!</h1>
                                <p>Hoş geldiniz, $firstName!</p>
                                <p>Hesabınız başarıyla oluşturuldu.</p>
                                <p><a href='login.php'>Giriş Yap</a> sayfasına yönlendiriliyorsunuz...</p>
                            </div>
                            <script>
                                setTimeout(function() {
                                    window.location.href = 'login.php';
                                }, 5000); 
                            </script>
                        </body>
                        </html>";

                        $userConn->close(); // Kullanıcı veritabanı bağlantısını kapat
                    } else {
                        echo "Kullanıcı veritabanı tabloları oluşturulurken hata: " . $userConn->error;
                    }
                } else {
                    echo "Kullanıcıya yetki verme hatası: " . $conn->error;
                }
            } else {
                echo "Kullanıcı oluşturma hatası: " . $conn->error;
            }
        } else {
            echo "Kullanıcı veritabanı oluşturulurken hata oluştu: " . $conn->error;
        }
    } else {
        echo "Kayıt işlemi sırasında hata oluştu: " . $stmt->error;
    }

    $stmt->close(); // Prepared statement'ı kapat
    mysqli_close($conn); // Genel veritabanı bağlantısını kapat
}
?>
