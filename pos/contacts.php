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

// Müşterileri ve tedarikçileri veritabanından çek
$customersSql = "SELECT * FROM customers ORDER BY id ASC";
$customersResult = $userConn->query($customersSql);

$suppliersSql = "SELECT * FROM suppliers ORDER BY id ASC";
$suppliersResult = $userConn->query($suppliersSql);

// Yeni müşteri ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['type'])) {
    $type = $_POST['type'];

    if ($type == 'customer') {
        $first_name = mb_strtoupper(mysqli_real_escape_string($userConn, $_POST['first_name']));
        $last_name = mb_strtoupper(mysqli_real_escape_string($userConn, $_POST['last_name']));
        $phone_number = mysqli_real_escape_string($userConn, $_POST['phone_number']);
        $address = mb_strtoupper(mysqli_real_escape_string($userConn, substr($_POST['address'], 0, 50))); // Adres alanını 50 karakterle sınırla
        $open_account_limit = floatval(str_replace(',', '.', str_replace('.', '', $_POST['open_account_limit'])));

        $insertSql = "INSERT INTO customers (first_name, last_name, phone_number, address, open_account_limit) VALUES (?, ?, ?, ?, ?)";
        $stmt = $userConn->prepare($insertSql);
        $stmt->bind_param("ssssd", $first_name, $last_name, $phone_number, $address, $open_account_limit);
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            echo json_encode([
                "status" => "success",
                "newRow" => "<tr id='row-$newId'>
                                <td></td>
                                <td><a href='contacts_details.php?type=customer&id=$newId'>" . htmlspecialchars($first_name . " " . $last_name) . "</a></td>
                                <td>" . htmlspecialchars($phone_number) . "</td>
                                <td>" . nl2br(htmlspecialchars($address)) . "</td>
                                <td>" . number_format($open_account_limit, 2, ',', '.') . " TL</td>
                             </tr>",
                "newId" => $newId
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
        $userConn->close();
        exit();
    }

    if ($type == 'supplier') {
        $supplier_name = mb_strtoupper(mysqli_real_escape_string($userConn, $_POST['supplier_name']));
        $phone_number = mysqli_real_escape_string($userConn, $_POST['phone_number']);
        $address = mb_strtoupper(mysqli_real_escape_string($userConn, substr($_POST['address'], 0, 50))); // Adres alanını 50 karakterle sınırla
        $tax_office = mb_strtoupper(mysqli_real_escape_string($userConn, $_POST['tax_office']));
        $tax_number = mysqli_real_escape_string($userConn, $_POST['tax_number']);

        $insertSql = "INSERT INTO suppliers (supplier_name, phone_number, address, tax_office, tax_number) VALUES (?, ?, ?, ?, ?)";
        $stmt = $userConn->prepare($insertSql);
        $stmt->bind_param("sssss", $supplier_name, $phone_number, $address, $tax_office, $tax_number);
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            echo json_encode([
                "status" => "success",
                "newRow" => "<tr id='row-$newId'>
                                <td></td>
                                <td><a href='contacts_details.php?type=supplier&id=$newId'>" . htmlspecialchars($supplier_name) . "</a></td>
                                <td>" . htmlspecialchars($phone_number) . "</td>
                                <td>" . nl2br(htmlspecialchars($address)) . "</td>
                                <td>" . htmlspecialchars($tax_office) . "</td>
                                <td>" . htmlspecialchars($tax_number) . "</td>
                             </tr>",
                "newId" => $newId
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
        $userConn->close();
        exit();
    }
}

// Müşteri veya tedarikçi silme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_type']) && isset($_POST['delete_id'])) {
    $delete_type = $_POST['delete_type'];
    $delete_id = intval($_POST['delete_id']);

    if ($delete_type == 'customer') {
        $deleteSql = "DELETE FROM customers WHERE id = ?";
        $stmt = $userConn->prepare($deleteSql);
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($delete_type == 'supplier') {
        $deleteSql = "DELETE FROM suppliers WHERE id = ?";
        $stmt = $userConn->prepare($deleteSql);
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(["status" => "success"]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim Bilgileri</title>
    <link rel="stylesheet" href="css/contacts.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function phoneNumberFormatView(phoneNumberString) {
            let cleaned = ('' + phoneNumberString).replace(/\D/g, '');
            let match = cleaned.match(/^(90|)?(\d{3})(\d{3})(\d{2})(\d{2})$/);
            if (match) {
                let intlCode = (match[1] ? '+90 ' : '');
                return [intlCode, match[2], ' ', match[3], ' ', match[4], ' ', match[5]].join('');
            }
            return phoneNumberString;
        }

        $(document).ready(function() {
            $(".tablinks").click(function() {
                var target = $(this).attr("data-target");
                $(".tabcontent").hide();
                $("#" + target).show();
                $(".tablinks").removeClass("active");
                $(this).addClass("active");
            });

            // İlk sekmeyi göster
            $(".tablinks").first().click();

            // Modalların gösterilmesi ve kapatılması
            $(".button-green").click(function() {
                $("#customerModal").show();
            });

            $(".button-blue").click(function() {
                $("#supplierModal").show();
            });

            $(".close").click(function() {
                $(this).closest(".modal").hide();
            });

            // Müşteri ekleme işlemi
            $("#customerForm").submit(function(e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "contacts.php",
                    data: $(this).serialize() + "&type=customer",
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.status === "success") {
                            $("#customerModal").hide();
                            $("tbody#customerTable").append(result.newRow);
                            updateRowNumbers("customerTable");
                            highlightNewRow(result.newId);
                        } else {
                            alert(result.message);
                        }
                    }
                });
            });

            // Tedarikçi ekleme işlemi
            $("#supplierForm").submit(function(e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "contacts.php",
                    data: $(this).serialize() + "&type=supplier",
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.status === "success") {
                            $("#supplierModal").hide();
                            $("tbody#supplierTable").append(result.newRow);
                            updateRowNumbers("supplierTable");
                            highlightNewRow(result.newId);
                        } else {
                            alert(result.message);
                        }
                    }
                });
            });

            function highlightNewRow(id) {
                var newRow = $("#row-" + id);
                newRow.addClass("highlight");
                setTimeout(function() {
                    newRow.removeClass("highlight");
                }, 3000);
            }

            // Dinamik arama işlemi
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $(".tabcontent:visible tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updateRowNumbers($(".tabcontent:visible tbody").attr('id'));
            });

            // Açık hesap limitini formatlama
            $("#open_account_limit").on("input", function() {
                var value = $(this).val().replace(/\D/g, '');
                value = (value / 100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
                $(this).val(value);
            });

            // Telefon numarası girişini formatlama
            $("#phone_number, #supplier_phone_number").on("input", function() {
                var inputValue = $(this).val().replace(/\D/g, '');
                var formattedValue = '';
                if (inputValue.length === 0) {
                    formattedValue = '0 ';
                } else {
                    if (inputValue.startsWith('0')) {
                        inputValue = inputValue.slice(1);
                    }
                    formattedValue = '0 (';
                    for (let i = 0; i < inputValue.length; i++) {
                        if (i === 3) {
                            formattedValue += ') ';
                        } else if (i === 6 || i === 8) {
                            formattedValue += ' ';
                        }
                        formattedValue += inputValue.charAt(i);
                    }
                }
                $(this).val(formattedValue);
            }).attr("maxlength", "17").attr("placeholder", "0 (___) ___ __ __");

            // Silme işlemi
            $(document).on("click", ".delete-button", function() {
                var deleteType = $(this).data("type");
                var deleteId = $(this).data("id");
                if (confirm("Bu kaydı silmek istediğinizden emin misiniz?")) {
                    $.ajax({
                        type: "POST",
                        url: "contacts.php",
                        data: { delete_type: deleteType, delete_id: deleteId },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.status === "success") {
                                $("#row-" + deleteId).remove();
                                updateRowNumbers(deleteType + "Table");
                            } else {
                                alert("Silme işlemi başarısız oldu.");
                            }
                        }
                    });
                }
            });

            function updateRowNumbers(tableId) {
                $("#" + tableId + " tr").each(function(index) {
                    $(this).find("td:first").text(index + 1);
                });
            }
        });
    </script>
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <div class="header-buttons">
            <div class="back-button">
                <a href="dashboard.php" class="button-blue">← Geri</a>
            </div>
            <div class="buttons">
                <input type="text" id="searchInput" placeholder="Ara...">
                <button class="button-green">Müşteri Oluştur</button>
                <button class="button-blue">Tedarikçi Oluştur</button>
            </div>
        </div>
        <div class="tabs">
            <button class="tablinks green" data-target="customers">Müşteriler</button>
            <button class="tablinks blue" data-target="suppliers">Tedarikçiler</button>
        </div>
        <div id="customers" class="tabcontent">
            <h2>Müşteriler</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sıra No</th>
                        <th>Müşteri Adı Soyadı</th>
                        <th>Telefon</th>
                        <th>Adres</th>
                        <th>Açık Hesap Limiti</th>
                    </tr>
                </thead>
                <tbody id="customerTable">
                    <?php
                    $siraNo = 1;
                    if ($customersResult->num_rows > 0): ?>
                        <?php while ($row = $customersResult->fetch_assoc()): ?>
                            <tr id="row-<?php echo htmlspecialchars($row['id']); ?>">
                                <td><?php echo $siraNo++; ?></td>
                                <td><a href="contacts_details.php?type=customer&id=<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></a></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($row['address'])); ?></td>
                                <td><?php echo number_format($row['open_account_limit'], 2, ',', '.') . " TL"; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Kayıt bulunamadı.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="suppliers" class="tabcontent">
            <h2>Tedarikçiler</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sıra No</th>
                        <th>Tedarikçi Adı</th>
                        <th>Telefon</th>
                        <th>Adres</th>
                        <th>Vergi Dairesi</th>
                        <th>Vergi Numarası</th>
                    </tr>
                </thead>
                <tbody id="supplierTable">
                    <?php
                    $siraNo = 1;
                    if ($suppliersResult->num_rows > 0): ?>
                        <?php while ($row = $suppliersResult->fetch_assoc()): ?>
                            <tr id="row-<?php echo htmlspecialchars($row['id']); ?>">
                                <td><?php echo $siraNo++; ?></td>
                                <td><a href="contacts_details.php?type=supplier&id=<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['supplier_name']); ?></a></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($row['address'])); ?></td>
                                <td><?php echo htmlspecialchars($row['tax_office']); ?></td>
                                <td><?php echo htmlspecialchars($row['tax_number']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Kayıt bulunamadı.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="customerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Müşteri Oluştur</h2>
            <form id="customerForm" method="POST">
                <input type="hidden" name="customerForm" value="1">
                <label for="first_name">Ad <span class="required">*</span>:</label>
                <input type="text" id="first_name" name="first_name" required>
                <label for="last_name">Soyad:</label>
                <input type="text" id="last_name" name="last_name">
                <label for="phone_number">Telefon Numarası:</label>
                <input type="text" id="phone_number" name="phone_number" placeholder="0 (___) ___ __ __">
                <label for="address">Adres:</label>
                <input type="text" id="address" name="address" maxlength="50"> <!-- Adres alanı 50 karakter ile sınırlandırıldı -->
                <label for="open_account_limit">Açık Hesap Limiti <span class="required">*</span>: <span class="hint">Müşteri veresiye limiti belirlemek istiyorsanız TL limit belirtiniz. Limit istemiyorsanız "0" olarak bırakınız.</span></label>
                <input type="text" id="open_account_limit" name="open_account_limit" value="0" required pattern="\d{1,3}(\.\d{3})*(,\d{2})?">
                <button type="submit" class="button-green">Kaydet</button>
            </form>
        </div>
    </div>

    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Tedarikçi Oluştur</h2>
            <form id="supplierForm" method="POST">
                <input type="hidden" name="supplierForm" value="1">
                <label for="supplier_name">Tedarikçi Adı <span class="required">*</span>:</label>
                <input type="text" id="supplier_name" name="supplier_name" required>
                <label for="supplier_phone_number">Telefon Numarası:</label>
                <input type="text" id="supplier_phone_number" name="phone_number" placeholder="0 (___) ___ __ __">
                <label for="address">Adres:</label>
                <input type="text" id="address" name="address" maxlength="50"> <!-- Adres alanı 50 karakter ile sınırlandırıldı -->
                <label for="tax_office">Vergi Dairesi:</label>
                <input type="text" id="tax_office" name="tax_office">
                <label for="tax_number">Vergi Numarası:</label>
                <input type="text" id="tax_number" name="tax_number" maxlength="11">
                <button type="submit" class="button-blue">Kaydet</button>
            </form>
        </div>
    </div>
</body>
</html>
