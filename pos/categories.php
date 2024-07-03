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

// Silme isteğini işle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $groupId = intval($_POST['delete_id']);
    $deleteSql = "DELETE FROM product_groups WHERE id = ?";
    $stmt = $userConn->prepare($deleteSql);
    $stmt->bind_param("i", $groupId);
    if ($stmt->execute()) {
        // Kategoriyi sildikten sonra sıra numaralarını güncelle
        $sql = "SELECT id FROM product_groups ORDER BY order_number ASC";
        $result = $userConn->query($sql);
        $orderNumber = 1;
        while ($row = $result->fetch_assoc()) {
            $updateOrderSql = "UPDATE product_groups SET order_number = ? WHERE id = ?";
            $stmt = $userConn->prepare($updateOrderSql);
            $stmt->bind_param("ii", $orderNumber, $row['id']);
            $stmt->execute();
            $orderNumber++;
        }
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    $userConn->close();
    exit();
}

// Yeni kategori ekleme ve düzenleme isteğini işle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $groupName = mysqli_real_escape_string($userConn, $_POST['group_name']);
    $vatRate = intval($_POST['vat_rate']);
    $showOnFastMenu = isset($_POST['show_on_fast_menu']) && $_POST['show_on_fast_menu'] == '1' ? 1 : 0;

    // Aynı isimde kategori olup olmadığını kontrol et
    $checkSql = "SELECT COUNT(*) AS count FROM product_groups WHERE group_name = ? AND id != ?";
    $stmt = $userConn->prepare($checkSql);
    $groupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $stmt->bind_param("si", $groupName, $groupId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        echo json_encode(["status" => "error", "message" => "Aynı isimde kategori mevcut."]);
        $stmt->close();
        $userConn->close();
        exit();
    }

    if ($action == 'add') {
        // Mevcut en yüksek sıra numarasını bul ve yeni değeri ayarla
        $maxOrderSql = "SELECT MAX(order_number) AS max_order FROM product_groups";
        $maxOrderResult = $userConn->query($maxOrderSql);
        $maxOrderRow = $maxOrderResult->fetch_assoc();
        $orderNumber = $maxOrderRow['max_order'] + 1;

        $addSql = "INSERT INTO product_groups (group_name, vat_rate, show_on_fast_menu, order_number) VALUES (?, ?, ?, ?)";
        $stmt = $userConn->prepare($addSql);
        $stmt->bind_param("sdii", $groupName, $vatRate, $showOnFastMenu, $orderNumber);
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "close_modal" => !isset($_POST['reset']),
                "new_data" => [
                    "id" => $stmt->insert_id,
                    "order_number" => $orderNumber,
                    "group_name" => $groupName,
                    "vat_rate" => $vatRate,
                    "show_on_fast_menu" => $showOnFastMenu ? '✔' : '❌',
                    "total_products" => 0
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
        $userConn->close();
        exit();
    } elseif ($action == 'edit' && isset($_POST['group_id'])) {
        $groupId = intval($_POST['group_id']);
        $editSql = "UPDATE product_groups SET group_name = ?, vat_rate = ?, show_on_fast_menu = ? WHERE id = ?";
        $stmt = $userConn->prepare($editSql);
        $stmt->bind_param("sdii", $groupName, $vatRate, $showOnFastMenu, $groupId);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
        $userConn->close();
        exit();
    }
}

// Kategorileri veritabanından sıraya göre çek
$sql = "SELECT * FROM product_groups ORDER BY order_number ASC";
$result = $userConn->query($sql);

// Kategori sıralamasını güncelleme isteğini işle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order'])) {
    $order = $_POST['order'];
    foreach ($order as $index => $id) {
        $newOrderNumber = $index + 1;
        $updateOrderSql = "UPDATE product_groups SET order_number = ? WHERE id = ?";
        $stmt = $userConn->prepare($updateOrderSql);
        $stmt->bind_param("ii", $newOrderNumber, $id);
        $stmt->execute();
    }
    echo json_encode(["status" => "success"]);
    $stmt->close();
    $userConn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Kategorileri</title>
    <link rel="stylesheet" href="css/categories.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            $("tbody").sortable({
                update: function(event, ui) {
                    var order = $(this).sortable('toArray', { attribute: 'data-id' });
                    $.ajax({
                        type: 'POST',
                        url: 'categories.php',
                        data: { order: order },
                        success: function(response) {
                            console.log("Sıralama güncellendi:", response);
                            location.reload();
                        }
                    });
                }
            });
            $("tbody").disableSelection();
        });

        function showModal(title, message, confirmCallback) {
            const modal = document.getElementById('generalModal');
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            const confirmButton = document.getElementById('confirmButton');
            confirmButton.onclick = confirmCallback;
            modal.style.display = 'block';
        }

        function confirmDelete(groupName, groupId) {
            showModal(
                "Kategori Sil",
                `"${groupName}" adlı kategoriyi silmek istediğinizden emin misiniz?`,
                function() {
                    $.ajax({
                        type: 'POST',
                        url: 'categories.php',
                        data: { delete_id: groupId },
                        success: function(response) {
                            const result = JSON.parse(response);
                            if (result.status === 'success') {
                                location.reload();
                            } else {
                                alert("Hata: " + result.message);
                            }
                            closeModal(); // Modali kapat
                        }
                    });
                }
            );
        }

        function closeModal() {
            document.getElementById('generalModal').style.display = 'none';
        }

        function showAddCategoryModal() {
            const modal = document.getElementById('addCategoryModal');
            modal.style.display = 'block';
            document.getElementById('addCategoryForm').reset();
            document.getElementById('categoryModalTitle').textContent = 'Yeni Kategori Ekle';
            document.getElementById('addCategoryForm').dataset.action = 'add';
            document.getElementById('group_id').value = '';
        }

        function closeAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'none';
            location.reload(); // Modal kapandıktan sonra sayfayı yenile
        }

        function addCategory(resetForm) {
            const action = document.getElementById('addCategoryForm').dataset.action;
            const groupId = document.getElementById('group_id').value;
            const groupName = document.getElementById('group_name').value.toUpperCase();
            const vatRate = document.getElementById('vat_rate').value;
            const showOnFastMenu = document.getElementById('show_on_fast_menu').checked ? 1 : 0;

            $.ajax({
                type: 'POST',
                url: 'categories.php',
                data: {
                    action: action,
                    group_id: groupId,
                    group_name: groupName,
                    vat_rate: vatRate,
                    show_on_fast_menu: showOnFastMenu,
                    reset: resetForm
                },
                success: function(response) {
                    console.log("Sunucu yanıtı:", response); // Dönen veriyi kontrol edin
                    try {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            if (action === 'add') {
                                if (resetForm) {
                                    document.getElementById('group_name').value = '';
                                    document.getElementById('vat_rate').value = '0';
                                    document.getElementById('show_on_fast_menu').checked = false;
                                }
                            } else if (action === 'edit') {
                                location.reload();
                            }
                            if (!resetForm) {
                                closeAddCategoryModal(); // Modalı kapat ve sayfayı yenile
                            }
                        } else {
                            alert("Hata: " + result.message);
                        }
                    } catch (e) {
                        console.error("JSON parse hatası:", e);
                        console.error("Ham yanıt:", response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX hatası:", xhr.responseText);
                }
            });
        }

        function showEditCategoryModal(id, name, vatRate, showOnFastMenu) {
            const modal = document.getElementById('addCategoryModal');
            modal.style.display = 'block';
            document.getElementById('categoryModalTitle').textContent = 'Kategori Düzenle';
            document.getElementById('addCategoryForm').dataset.action = 'edit';
            document.getElementById('group_id').value = id;
            document.getElementById('group_name').value = name;
            document.getElementById('vat_rate').value = vatRate;
            document.getElementById('show_on_fast_menu').checked = showOnFastMenu === 1;
        }
    </script>
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <h1>Ürün Grupları</h1>
        <div class="add-group">
            <button class="button-green" onclick="showAddCategoryModal()">+ Yeni Grup Ekle</button>
        </div>
        <div class="back-button">
            <a href="products.php" class="button-blue">← Geri</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Sıra</th>
                    <th>Kategori Adı</th>
                    <th>KDV Oranı</th>
                    <th>Hızlı Menü Gösterilsin Mi?</th>
                    <th>Grupta Kayıtlı Ürün Adedi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?php echo $row['id']; ?>">
                            <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['vat_rate']); ?>%</td>
                            <td><?php echo $row['show_on_fast_menu'] ? '✔️' : '❌'; ?></td>
                            <td><?php echo $row['total_products']; ?></td>
                            <td>
                                <a href="javascript:void(0);" onclick="showEditCategoryModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['group_name']); ?>', <?php echo $row['vat_rate']; ?>, <?php echo $row['show_on_fast_menu']; ?>);" class="edit">✏️</a>
                                <a href="javascript:void(0);" onclick="confirmDelete('<?php echo htmlspecialchars($row['group_name']); ?>', <?php echo $row['id']; ?>);" class="delete">🗑</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Kayıt bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php $userConn->close(); ?>
    </div>

    <div id="generalModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle"></h2>
            <p id="modalMessage"></p>
            <button id="confirmButton" class="button-green">Evet</button>
            <button type="button" class="button-red" onclick="closeModal()">Hayır</button>
        </div>
    </div>

    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCategoryModal()">&times;</span>
            <h2 id="categoryModalTitle">Yeni Kategori Ekle</h2>
            <form id="addCategoryForm">
                <input type="hidden" id="group_id" name="group_id">
                <label for="group_name">Kategori Adı:</label>
                <input type="text" id="group_name" name="group_name" required><br><br>
                <label for="vat_rate">KDV Oranı:</label>
                <select id="vat_rate" name="vat_rate" required>
                    <option value="0">0%</option>
                    <option value="1">1%</option>
                    <option value="10">10%</option>
                    <option value="20">20%</option>
                </select><br><br>
                <label for="show_on_fast_menu">Hızlı Menüde Gösterilsin Mi?:</label>
                <label class="switch">
                    <input type="checkbox" id="show_on_fast_menu" name="show_on_fast_menu" value="1">
                    <span class="slider"></span>
                </label><br><br>
                <button type="button" class="button-green" onclick="addCategory(true)">Kaydet/Yeni Ekle</button>
                <button type="button" class="button-green" onclick="addCategory(false)">Kaydet/Kapat</button>
            </form>
        </div>
    </div>
</body>
</html>
