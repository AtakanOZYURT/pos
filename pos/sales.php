<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satış Sayfası</title>
    <link rel="stylesheet" href="css/sales.css">
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <div class="left-sidebar">
            <?php
                include 'db.php';
                $userDbName = "user_db_" . $_SESSION['user_id'];
                $userConn = new mysqli($servername, $username, $password, $userDbName);

                if ($userConn->connect_error) {
                    die("Veritabanına bağlanırken hata oluştu: " . $userConn->connect_error);
                }

                $query = "SELECT group_name FROM product_groups WHERE show_on_fast_menu = 1 ORDER BY order_number ASC";
                $result = $userConn->query($query);

                $firstCategory = null;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if (!$firstCategory) {
                            $firstCategory = $row['group_name'];
                        }
                        echo "<button onclick=\"loadProducts('".$row['group_name']."')\">" . $row['group_name'] . "</button>";
                    }
                }
                $userConn->close();
            ?>
        </div>
        <div class="main-content">
            <div class="product-list">product list</div>
            <div class="numpad">
                <div class="numpad-button">7</div>
                <div class="numpad-button">8</div>
                <div class="numpad-button">9</div>
                <div class="numpad-button">←</div>
                <div class="numpad-button">4</div>
                <div class="numpad-button">5</div>
                <div class="numpad-button">6</div>
                <div class="numpad-button">C</div>
                <div class="numpad-button">1</div>
                <div class="numpad-button">2</div>
                <div class="numpad-button">3</div>
                <div class="numpad-button double-height">ENTER</div>
                <div class="numpad-button double-width">0</div>
                <div class="numpad-button">*</div>
            </div>
        </div>
        <div class="right-content">
            <div class="search-box">
                <input type="text" placeholder="Barkod veya Ürün Adı ile Ara" />
            </div>
            <div class="tabs">
                <div class="tab" onclick="openTab(event, 'customer1')">Müşteri 01</div>
                <div class="tab" onclick="openTab(event, 'customer2')">Müşteri 02</div>
                <div class="tab" onclick="openTab(event, 'customer3')">Müşteri 03</div>
                <div class="tab" onclick="openTab(event, 'customer4')">Müşteri 04</div>
            </div>
            <div id="customer1" class="sales-list">Sales list for Müşteri 01</div>
            <div id="customer2" class="sales-list">Sales list for Müşteri 02</div>
            <div id="customer3" class="sales-list">Sales list for Müşteri 03</div>
            <div id="customer4" class="sales-list">Sales list for Müşteri 04</div>
            <div class="payment-info">
                <button>İkram</button>
                <button>İndirim</button>
                <label>Toplam:</label>
                <input type="text" readonly />
            </div>
            <div class="payment-option">
                <button>POS</button>
                <button>Nakit</button>
                <button>Veresiye</button>
                <button>Parçalı Ödeme</button>
            </div>
        </div>
        <div class="right-sidebar">
            <button class="price-button">Fiyat Gör</button>
            <button class="keyboard-button">Klavye</button>
            <button class="back-button" onclick="window.location.href='dashboard.php'">Geri</button>
        </div>
    </div>
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("sales-list");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "flex";
            evt.currentTarget.className += " active";
        }

        document.addEventListener('DOMContentLoaded', function() {
            const firstCategory = "<?php echo $firstCategory; ?>";
            if (firstCategory) {
                loadProducts(firstCategory);
            }

            // Varsayılan olarak ilk tabı göster
            document.querySelector('.tab').click();
        });

        function loadProducts(category) {
            const productList = document.querySelector('.product-list');
            productList.innerHTML = ''; // Clear previous products

            fetch(`get_products.php?category=${category}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(product => {
                        const button = document.createElement('button');
                        button.innerHTML = `<div>${product.name}</div><div>${product.price} TL</div>`;
                        productList.appendChild(button);
                    });
                });
        }
    </script>
</body>
</html>
