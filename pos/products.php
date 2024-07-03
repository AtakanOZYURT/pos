<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Hata raporlama için ekledik
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Ürünleri ve kategorileri birleştirerek çekme
$productsQuery = "
    SELECT p.*, g.group_name as category_name, g.vat_rate 
    FROM products p
    LEFT JOIN product_groups g ON p.category_id = g.id
";
$productsResult = $userConn->query($productsQuery);
$products = [];
if ($productsResult) {
    while ($row = $productsResult->fetch_assoc()) {
        // Fiyatları biçimlendir
        $row['purchase_price_excl_vat'] = number_format($row['purchase_price_excl_vat'], 2, ',', '.');
        $row['purchase_price_incl_vat'] = number_format($row['purchase_price_incl_vat'], 2, ',', '.');
        $row['sale_price_incl_vat'] = number_format($row['sale_price_incl_vat'], 2, ',', '.');
        $products[] = $row;
    }
}

// Kategorileri çekme
$categories = $userConn->query("SELECT id, group_name, vat_rate FROM product_groups")->fetch_all(MYSQLI_ASSOC);
foreach ($categories as &$category) {
    $category['vat_rate'] .= '%';
}
unset($category);

// Ürün ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_barcode']) && !isset($_POST['product_id'])) {
    $product_barcode = $_POST['product_barcode'];
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category'];
    $product_unit = $_POST['unit'];
    $purchase_price_excl_vat = str_replace(',', '.', str_replace('.', '', $_POST['purchase_price_excl_vat']));
    $purchase_price_incl_vat = str_replace(',', '.', str_replace('.', '', $_POST['purchase_price_incl_vat']));
    $sale_price_incl_vat = str_replace(',', '.', str_replace('.', '', $_POST['sale_price_incl_vat']));
    $profit_margin = $_POST['profit_margin'];
    $vat_rate = $_POST['vat_rate'];
    $stock_code = !empty($_POST['stock_code']) ? $_POST['stock_code'] : null;
    $fast_product_order = $_POST['fast_product_order'];
    $product_status = $_POST['product_status'];
    $product_description = $_POST['product_description'];
    $last_modified_date = date("Y-m-d H:i:s"); // Current timestamp
    $critical_stock = isset($_POST['critical_stock']) ? $_POST['critical_stock'] : 0;

    // Aynı barkod numarasının olup olmadığını kontrol et
    $barcodeCheckQuery = $userConn->prepare("SELECT COUNT(*) as count FROM products WHERE product_barcode = ?");
    $barcodeCheckQuery->bind_param("s", $product_barcode);
    $barcodeCheckQuery->execute();
    $barcodeCheckResult = $barcodeCheckQuery->get_result()->fetch_assoc();

    if ($barcodeCheckResult['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Bu barkod zaten kayıtlı.']);
        exit();
    }

    // Yeni ürün ekleme işlemi
    $sql = "INSERT INTO products (product_barcode, product_name, category_id, product_unit, purchase_price_excl_vat, purchase_price_incl_vat, sale_price_incl_vat, profit_margin, vat_rate, stock_code, fast_product_order, product_status, product_description, critical_stock, last_modified_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $userConn->prepare($sql);
    $stmt->bind_param("ssissdddddisss", $product_barcode, $product_name, $category_id, $product_unit, $purchase_price_excl_vat, $purchase_price_incl_vat, $sale_price_incl_vat, $profit_margin, $vat_rate, $stock_code, $fast_product_order, $product_status, $product_description, $critical_stock, $last_modified_date);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    exit();
}

// Ürün güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $product_barcode = $_POST['product_barcode'];
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category'];
    $product_unit = $_POST['unit'];
    $purchase_price_excl_vat = str_replace(',', '.', str_replace('.', '', $_POST['purchase_price_excl_vat']));
    $purchase_price_incl_vat = str_replace(',', '.', str_replace('.', '', $_POST['purchase_price_incl_vat']));
    $sale_price_incl_vat = str_replace(',', '.', str_replace('.', '', $_POST['sale_price_incl_vat']));
    $profit_margin = $_POST['profit_margin'];
    $vat_rate = $_POST['vat_rate'];
    $stock_code = !empty($_POST['stock_code']) ? $_POST['stock_code'] : null;
    $fast_product_order = $_POST['fast_product_order'];
    $product_status = $_POST['product_status'];
    $product_description = $_POST['product_description'];
    $last_modified_date = date("Y-m-d H:i:s"); // Current timestamp
    $critical_stock = isset($_POST['critical_stock']) ? $_POST['critical_stock'] : 0;

    // Ürün güncelleme işlemi
    $sql = "UPDATE products SET product_barcode=?, product_name=?, category_id=?, product_unit=?, purchase_price_excl_vat=?, purchase_price_incl_vat=?, sale_price_incl_vat=?, profit_margin=?, vat_rate=?, stock_code=?, fast_product_order=?, product_status=?, product_description=?, critical_stock=?, last_modified_date=? WHERE id=?";
    $stmt = $userConn->prepare($sql);
    $stmt->bind_param("ssissdddddissssi", $product_barcode, $product_name, $category_id, $product_unit, $purchase_price_excl_vat, $purchase_price_incl_vat, $sale_price_incl_vat, $profit_margin, $vat_rate, $stock_code, $fast_product_order, $product_status, $product_description, $critical_stock, $last_modified_date, $product_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    exit();
}

// Ürün bilgilerini AJAX ile çekme
if (isset($_GET['id'])) {
    $productId = $_GET['id'];
    $productQuery = $userConn->prepare("SELECT * FROM products WHERE id = ?");
    $productQuery->bind_param("i", $productId);
    $productQuery->execute();
    $productResult = $productQuery->get_result();
    $product = $productResult->fetch_assoc();
    echo json_encode($product);
    exit();
}

// Ürün silme işlemi
if (isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $productId = $_POST['id'];
    $deleteQuery = $userConn->prepare("DELETE FROM products WHERE id = ?");
    $deleteQuery->bind_param("i", $productId);
    $deleteQuery->execute();
    if ($deleteQuery->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Hızlı ürün sırasını almak için AJAX isteği
if (isset($_GET['action']) && $_GET['action'] == 'get_fast_product_order' && isset($_GET['category_id'])) {
    $categoryId = $_GET['category_id'];
    $fastProductOrderQuery = $userConn->prepare("SELECT MAX(fast_product_order) as max_order FROM products WHERE category_id = ?");
    $fastProductOrderQuery->bind_param("i", $categoryId);
    $fastProductOrderQuery->execute();
    $result = $fastProductOrderQuery->get_result();
    $maxOrder = $result->fetch_assoc()['max_order'];
    if ($maxOrder === null) {
        $maxOrder = 0;
    }
    echo json_encode($maxOrder);
    exit();
}

// Barkod kontrolü için AJAX isteği
if (isset($_GET['action']) && $_GET['action'] == 'check_barcode' && isset($_GET['barcode'])) {
    $barcode = $_GET['barcode'];
    $barcodeCheckQuery = $userConn->prepare("SELECT product_name FROM products WHERE product_barcode = ?");
    $barcodeCheckQuery->bind_param("s", $barcode);
    $barcodeCheckQuery->execute();
    $barcodeCheckResult = $barcodeCheckQuery->get_result();
    $exists = $barcodeCheckResult->num_rows > 0;
    $product_name = $exists ? $barcodeCheckResult->fetch_assoc()['product_name'] : '';
    echo json_encode(['exists' => $exists, 'product_name' => $product_name]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Kartları</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
    <script>
        $(document).ready(function() {
            const table = $('table').DataTable({
                language: {
                    "decimal": "",
                    "emptyTable": "Tabloda veri yok",
                    "info": " _TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                    "infoEmpty": "Gösterilecek kayıt yok",
                    "infoFiltered": "(_MAX_ kayıt içerisinden filtrelendi)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": " _MENU_ kayıt göster",
                    "loadingRecords": "Yükleniyor...",
                    "processing": "İşleniyor...",
                    "search": "Ara:",
                    "zeroRecords": "Eşleşen kayıt bulunamadı",
                    "paginate": {
                        "first": "İlk",
                        "last": "Son",
                        "next": "Sonraki",
                        "previous": "Önceki"
                    },
                    "aria": {
                        "sortAscending": ": artan sütun sıralamasını aktifleştir",
                        "sortDescending": ": azalan sütun sıralamasını aktifleştir"
                    }
                },
                dom: 'lfrtip',
                initComplete: function() {
                    $('#datatable_filter').remove();
                },
                autoWidth: false,
                columnDefs: [
                    { targets: '_all', className: 'dt-body-center' }
                ]
            });

            const columnCheckboxes = $("#columnSelector input[type=checkbox]");
            let editingProductId = null;

            function loadColumnSelections() {
                const selectedColumns = JSON.parse(localStorage.getItem('selectedColumns')) || columnCheckboxes.map(function() {
                    return $(this).data("column");
                }).get();

                columnCheckboxes.each(function() {
                    const column = $(this).data("column");
                    const isChecked = selectedColumns.includes(column);
                    $(this).prop('checked', isChecked);
                    table.column('.' + column).visible(isChecked);
                });
            }

            function saveColumnSelections() {
                const selectedColumns = [];
                columnCheckboxes.each(function() {
                    if ($(this).is(':checked')) {
                        selectedColumns.push($(this).data("column"));
                    }
                });
                localStorage.setItem('selectedColumns', JSON.stringify(selectedColumns));
            }

            loadColumnSelections();

            columnCheckboxes.on("change", function() {
                const column = $(this).data("column");
                table.column('.' + column).visible(this.checked);
                saveColumnSelections();
            });

            $(".close").on("click", function() {
                $(this).closest(".modal").hide();
                editingProductId = null;
                $("#productForm")[0].reset();
                $("#editProductForm")[0].reset();
                $("#error-message").text('');
                $("#product_barcode").css('border-color', '');
            });

            $("#columnSelectorBtn").on("click", function() {
                $("#columnSelectorModal").show();
            });

            $("#addProductBtn").on("click", function() {
                $("#productModal").show();
            });

            $("#addCategoryBtn").on("click", function() {
                window.location.href = 'categories.php';
            });

            $(".btn-back").on("click", function() {
                window.location.href = 'dashboard.php';
            });

            $("#clearSearch").on("click", function() {
                $("#search").val('').trigger('input');
            });

            $(".currency-format").on("input", function() {
                var value = $(this).val().replace(/\D/g, '');
                value = (value / 100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
                $(this).val(value);
            });

            function cleanCurrencyFormat(value) {
                return value ? parseFloat(value.replace(/[.,]/g, '')) / 100 : 0;
            }

            function calculateVATPrices() {
                var exclVat = parseFloat($("#purchase_price_excl_vat").val().replace(/\./g, '').replace(',', '.')) || 0;
                var vatRate = parseFloat($("#vat_rate").val().replace('%', '')) || 0;
                var inclVat = exclVat + (exclVat * vatRate / 100);
                $("#purchase_price_incl_vat").val(inclVat.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
            }

            function calculateExclVATPrices() {
                var inclVat = parseFloat($("#purchase_price_incl_vat").val().replace(/\./g, '').replace(',', '.')) || 0;
                var vatRate = parseFloat($("#vat_rate").val().replace('%', '')) || 0;
                var exclVat = inclVat / (1 + (vatRate / 100));
                $("#purchase_price_excl_vat").val(exclVat.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
            }

            function calculateProfitMargin() {
                var salePrice = parseFloat($("#sale_price_incl_vat").val().replace(/\./g, '').replace(',', '.')) || 0;
                var inclVat = parseFloat($("#purchase_price_incl_vat").val().replace(/\./g, '').replace(',', '.')) || 0;
                var profitMargin = 0;
                if (inclVat > 0) {
                    profitMargin = ((salePrice - inclVat) / inclVat) * 100;
                }
                $("#profit_margin").val(profitMargin.toFixed(2));
            }

            $("#purchase_price_excl_vat").on("input", calculateVATPrices);
            $("#purchase_price_incl_vat").on("input", calculateExclVATPrices);
            $("#sale_price_incl_vat").on("input", calculateProfitMargin);

            $("#profit_margin").on("input", function() {
                var profitMargin = parseFloat($(this).val()) || 0;
                var inclVat = parseFloat($("#purchase_price_incl_vat").val().replace(/\./g, '').replace(',', '.')) || 0;
                var salePrice = inclVat + (inclVat * profitMargin / 100);
                $("#sale_price_incl_vat").val(salePrice.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
            });

            function calculateEditVATPrices() {
                var exclVat = cleanCurrencyFormat($("#edit_purchase_price_excl_vat").val()) || 0;
                var vatRate = parseFloat($("#edit_vat_rate").val().replace('%', '')) || 0;
                var inclVat = exclVat + (exclVat * vatRate / 100);
                $("#edit_purchase_price_incl_vat").val(inclVat.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
            }

            function calculateEditExclVATPrices() {
                var inclVat = cleanCurrencyFormat($("#edit_purchase_price_incl_vat").val()) || 0;
                var vatRate = parseFloat($("#edit_vat_rate").val().replace('%', '')) || 0;
                var exclVat = inclVat / (1 + (vatRate / 100));
                $("#edit_purchase_price_excl_vat").val(exclVat.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
            }

            function calculateEditProfitMargin() {
                var salePrice = cleanCurrencyFormat($("#edit_sale_price_incl_vat").val()) || 0;
                var inclVat = cleanCurrencyFormat($("#edit_purchase_price_incl_vat").val()) || 0;
                var profitMargin = 0;
                if (inclVat > 0) {
                    profitMargin = ((salePrice - inclVat) / inclVat) * 100;
                }
                $("#edit_profit_margin").val(profitMargin.toFixed(2));
            }

            $("#edit_purchase_price_excl_vat, #edit_purchase_price_incl_vat, #edit_sale_price_incl_vat").on("input", function() {
                var value = $(this).val().replace(/\D/g, '');
                value = (value / 100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
                $(this).val(value);

                calculateEditVATPrices();
                calculateEditExclVATPrices();
                calculateEditProfitMargin();
            });

            $("#edit_profit_margin").on("input", function() {
                var profitMargin = parseFloat($(this).val()) || 0;
                var inclVat = cleanCurrencyFormat($("#edit_purchase_price_incl_vat").val()) || 0;
                var salePrice = inclVat + (inclVat * profitMargin / 100);
                $("#edit_sale_price_incl_vat").val(salePrice.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
            });

            $("#product_name").on("input", function() {
                this.value = this.value.toUpperCase();
            });

            $("#stock_code").on("input", function() {
                this.value = this.value.toUpperCase();
            });

            $("#product_barcode").on("input", function() {
                var barcode = $(this).val();
                $.ajax({
                    url: "products.php",
                    method: "GET",
                    data: { action: 'check_barcode', barcode: barcode },
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.exists) {
                            $("#error-message").text('Bu barkod zaten kayıtlı: ' + result.product_name);
                            $("#product_barcode").css('border-color', 'red');
                        } else {
                            $("#error-message").text('');
                            $("#product_barcode").css('border-color', '');
                        }
                    }
                });
            });

            $(document).on("click", ".edit-product", function() {
                const productId = $(this).data("id");
                editingProductId = productId;

                $.ajax({
                    url: "products.php",
                    method: "GET",
                    data: { id: productId },
                    success: function(response) {
                        const product = JSON.parse(response);
                        $("#edit_product_id").val(product.id);
                        $("#edit_product_barcode").val(product.product_barcode);
                        $("#edit_product_name").val(product.product_name);
                        $("#edit_category").val(product.category_id).change();
                        $("#edit_unit").val(product.product_unit);
                        $("#edit_purchase_price_excl_vat").val(currencyFormat(cleanCurrencyFormat(product.purchase_price_excl_vat)));
                        $("#edit_purchase_price_incl_vat").val(currencyFormat(cleanCurrencyFormat(product.purchase_price_incl_vat)));
                        $("#edit_sale_price_incl_vat").val(currencyFormat(cleanCurrencyFormat(product.sale_price_incl_vat)));
                        // Kar marjını güncelle
                        calculateEditProfitMargin();
                        $("#edit_stock_code").val(product.stock_code);
                        $("#edit_fast_product_order").val(product.fast_product_order);
                        $("#edit_product_status").val(product.product_status);
                        $("#edit_product_description").val(product.product_description);
                        $("#edit_critical_stock").val(product.critical_stock);

                        $("#editProductModal").show();
                    }
                });
            });

            $(document).on("click", ".delete-product", function() {
                const productId = $(this).data("id");
                const productName = $(this).closest("tr").find(".name").text();
                $("#deleteProductName").text(productName);
                $("#confirmDeleteBtn").data("id", productId);
                $("#deleteModal").show();
            });

            $("#confirmDeleteBtn").on("click", function() {
                const productId = $(this).data("id");
                $.ajax({
                    url: "products.php",
                    method: "POST",
                    data: { id: productId, action: 'delete' },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            location.reload();
                        } else {
                            alert('Ürün silinirken bir hata oluştu.');
                        }
                    }
                });
            });

            $("#productForm").on("submit", function(event) {
                event.preventDefault();
                if ($("#error-message").text() !== '') {
                    return; // Hata mesajı varsa formu gönderme
                }
                $.ajax({
                    url: "products.php",
                    method: "POST",
                    data: $("#productForm").serialize(),
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    }
                });
            });

            $("#editProductForm").on("submit", function(event) {
                event.preventDefault();
                $.ajax({
                    url: "products.php",
                    method: "POST",
                    data: $("#editProductForm").serialize(),
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    }
                });
            });

            $("#category").on("change", function() {
                var selectedCategory = $(this).find("option:selected");
                var vatRate = selectedCategory.data("vat");
                $("#vat_rate").val(vatRate);
            });

            $("#edit_category").on("change", function() {
                var selectedCategory = $(this).find("option:selected");
                var vatRate = selectedCategory.data("vat");
                $("#edit_vat_rate").val(vatRate);
            });

            $(document).on("click", ".filter-column", function() {
                const column = table.column($(this).parent().index() + ':visible');
                const filterModal = $("#filterModal");
                filterModal.data('column', column);
                
                const offset = $(this).offset();
                filterModal.css({ top: offset.top + $(this).height(), left: offset.left }).show();

                const uniqueValues = column.data().unique().sort();
                const filterList = $("#filterList");
                filterList.empty();
                filterList.append('<label><input type="checkbox" id="selectAll" checked> (Tümünü Seç)</label>');
                uniqueValues.each(function(value) {
                    filterList.append('<label><input type="checkbox" value="' + value + '" checked> ' + value + '</label>');
                });
            });

            $(document).on("change", "#selectAll", function() {
                const checked = this.checked;
                $("#filterList input[type='checkbox']").prop('checked', checked);
            });

            $("#applyFilter").on("click", function() {
                const column = $("#filterModal").data('column');
                const selectedValues = $("#filterList input:checked").not('#selectAll').map(function() {
                    return $(this).val();
                }).get();
                
                column.search(selectedValues.join('|'), true, false).draw();
                $("#filterModal").hide();
            });

            $(document).on("click", function(event) {
                if (!$(event.target).closest("#filterModal, .filter-column").length) {
                    $("#filterModal").hide();
                }
            });

            $("th:not(.actions)").each(function() {
                $(this).append('<span class="filter-column" style="cursor: pointer; margin-left: 10px;">🔽</span>');
            });

            $("#filterSearch").on("input", function() {
                var value = $(this).val().toUpperCase();
                $(this).val(value);
                $("#filterList label").each(function() {
                    if ($(this).text().toUpperCase().indexOf(value) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Fetch the next fast product order when category is changed
            $("#category").on("change", function() {
                const categoryId = $(this).val();
                if (categoryId) {
                    $.ajax({
                        url: "products.php",
                        method: "GET",
                        data: { action: 'get_fast_product_order', category_id: categoryId },
                        success: function(response) {
                            const nextOrder = parseInt(response) + 1;
                            $("#fast_product_order").val(nextOrder);
                        }
                    });
                } else {
                    $("#fast_product_order").val('');
                }
            });
        });
        
        function currencyFormat(value) {
            if (!value) return '';
            value = parseFloat(value).toFixed(2);
            value = value.replace(".", ",");
            return value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
        }
    </script>
</head>
<body>
    <?php include 'dashboardheader.php'; ?>
    <div class="container">
        <a href="dashboard.php" class="btn btn-back">← Geri</a>
        <div class="header">
            <h2>Ürün Kartları</h2>
            <div class="actions">
                <button id="columnSelectorBtn" class="btn btn-red">Sütunları Seç</button>
                <button id="addProductBtn" class="btn btn-primary">Ürün Ekle</button>
                <button id="addCategoryBtn" class="btn btn-yellow">Kategori Ekle</button>
            </div>
        </div>

        <!-- Sütun Seçici Modal -->
        <div id="columnSelectorModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Sütunları Seç</h3>
                <div id="columnSelector" class="column-selector">
                    <label><input type="checkbox" data-column="barcode" checked> Ürün Barkodu</label>
                    <label><input type="checkbox" data-column="name" checked> Ürün Adı</label>
                    <label><input type="checkbox" data-column="category" checked> Kategori</label>
                    <label><input type="checkbox" data-column="unit" checked> Ürün Birimi</label>
                    <label><input type="checkbox" data-column="purchase_price_excl_vat" checked> KDV Hariç Alış Fiyatı</label>
                    <label><input type="checkbox" data-column="purchase_price_incl_vat" checked> KDV Dahil Alış Fiyatı</label>
                    <label><input type="checkbox" data-column="sale_price_incl_vat" checked> Satış Fiyatı (KDV Dahil)</label>
                    <label><input type="checkbox" data-column="profit_margin" checked> Kar Oranı</label>
                    <label><input type="checkbox" data-column="stock_code" checked> Stok Kodu</label>
                    <label><input type="checkbox" data-column="fast_product_order" checked> Hızlı Ürün Sırası</label>
                    <label><input type="checkbox" data-column="status" checked> Ürün Satışa</label>
                    <label><input type="checkbox" data-column="description" checked> Ürün Açıklaması</label>
                    <label><input type="checkbox" data-column="created_at" checked> Oluşturulma Tarihi</label>
                    <label><input type="checkbox" data-column="first_purchase_date" checked> İlk Alış Tarihi</label>
                    <label><input type="checkbox" data-column="last_modified_date" checked> Son Değişiklik Tarihi</label>
                </div>
            </div>
        </div>

        <!-- Filtreleme Modalı -->
        <div id="filterModal" class="filter-modal">
            <input type="text" id="filterSearch" placeholder="Ara">
            <div id="filterList"></div>
            <button id="applyFilter" class="btn btn-primary">Filtrele</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="barcode">Ürün Barkodu</th>
                        <th class="name">Ürün Adı</th>
                        <th class="category">Kategori</th>
                        <th class="unit">Ürün Birimi</th>
                        <th class="purchase_price_excl_vat">KDV Hariç Alış Fiyatı</th>
                        <th class="purchase_price_incl_vat">KDV Dahil Alış Fiyatı</th>
                        <th class="sale_price_incl_vat">Satış Fiyatı (KDV Dahil)</th>
                        <th class="profit_margin">Kar Oranı</th>
                        <th class="stock_code">Stok Kodu</th>
                        <th class="fast_product_order">Hızlı Ürün Sırası</th>
                        <th class="status">Ürün Satışa</th>
                        <th class="description">Ürün Açıklaması</th>
                        <th class="created_at">Oluşturulma Tarihi</th>
                        <th class="first_purchase_date">İlk Alış Tarihi</th>
                        <th class="last_modified_date">Son Değişiklik Tarihi</th>
                        <th class="actions">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="barcode"><?= $product['product_barcode'] ?></td>
                        <td class="name"><?= $product['product_name'] ?></td>
                        <td class="category"><?= isset($product['category_name']) ? $product['category_name'] : 'Kategori Yok' ?></td>
                        <td class="unit"><?= $product['product_unit'] ?></td>
                        <td class="purchase_price_excl_vat"><?= $product['purchase_price_excl_vat'] ?></td>
                        <td class="purchase_price_incl_vat"><?= $product['purchase_price_incl_vat'] ?></td>
                        <td class="sale_price_incl_vat"><?= $product['sale_price_incl_vat'] ?></td>
                        <td class="profit_margin"><?= $product['profit_margin'] ?></td>
                        <td class="stock_code"><?= $product['stock_code'] ?></td>
                        <td class="fast_product_order"><?= $product['fast_product_order'] ?></td>
                        <td class="status"><?= $product['product_status'] ?></td>
                        <td class="description"><?= $product['product_description'] ?></td>
                        <td class="created_at"><?= $product['created_at'] ?></td>
                        <td class="first_purchase_date"><?= $product['first_purchase_date'] ?></td>
                        <td class="last_modified_date"><?= $product['last_modified_date'] ?></td>
                        <td class="actions">
                            <button class="edit-product btn btn-green" data-id="<?= $product['id'] ?>">Düzenle</button>
                            <button class="delete-product btn btn-danger" data-id="<?= $product['id'] ?>">Sil</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ürün Ekle Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Ürün Ekle</h2>
            <form id="productForm" action="products.php" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_barcode">Ürün Barkodu:</label>
                        <input type="number" id="product_barcode" name="product_barcode" min="0" step="1" title="Sadece rakam giriniz" required>
                        <span id="error-message" style="color: red;"></span>
                    </div>
                    <div class="form-group">
                        <label for="product_name">Ürün Adı:</label>
                        <input type="text" id="product_name" name="product_name" oninput="this.value = this.value.toUpperCase();" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Kategori:</label>
                        <select id="category" name="category" required>
                            <option value="">Seçiniz</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" data-vat="<?= $category['vat_rate'] ?>"><?= $category['group_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vat_rate">KDV Oranı:</label>
                        <input type="text" id="vat_rate" name="vat_rate" readonly required> %
                    </div>
                    <div class="form-group">
                        <label for="unit">Ürün Birimi:</label>
                        <select id="unit" name="unit" required>
                            <option value="">Seçiniz</option>
                            <option value="Adet">Adet</option>
                            <option value="Kg">Kg</option>
                            <option value="Metre">Metre</option>
                            <option value="Paket">Paket</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="purchase_price_excl_vat">KDV Hariç Alış Fiyatı:</label>
                        <input type="text" id="purchase_price_excl_vat" name="purchase_price_excl_vat" class="currency-format" required>
                    </div>
                    <div class="form-group">
                        <label for="purchase_price_incl_vat">KDV Dahil Alış Fiyatı:</label>
                        <input type="text" id="purchase_price_incl_vat" name="purchase_price_incl_vat" class="currency-format" required>
                    </div>
                    <div class="form-group">
                        <label for="sale_price_incl_vat">Satış Fiyatı (KDV Dahil):</label>
                        <input type="text" id="sale_price_incl_vat" name="sale_price_incl_vat" class="currency-format" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="profit_margin">Kar Oranı:</label>
                        <input type="number" id="profit_margin" name="profit_margin" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_code">Stok Kodu:</label>
                        <input type="text" id="stock_code" name="stock_code" oninput="this.value = this.value.toUpperCase();">
                    </div>
                    <div class="form-group">
                        <label for="fast_product_order">Hızlı Ürün Sırası:</label>
                        <input type="number" id="fast_product_order" name="fast_product_order" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="critical_stock">Kritik Stok:</label>
                        <input type="number" id="critical_stock" name="critical_stock" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product_status">Ürün Satışa:</label>
                        <select id="product_status" name="product_status" required>
                            <option value="Açık">Açık</option>
                            <option value="Kapalı">Kapalı</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_description">Ürün Açıklaması:</label>
                        <textarea id="product_description" name="product_description" rows="4"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Ürün Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ürün Güncelle Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Ürün Güncelle</h2>
            <form id="editProductForm" action="products.php" method="post">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_product_barcode">Ürün Barkodu:</label>
                        <input type="number" id="edit_product_barcode" name="product_barcode" min="0" step="1" title="Sadece rakam giriniz" required>
                        <span id="edit-error-message" style="color: red;"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit_product_name">Ürün Adı:</label>
                        <input type="text" id="edit_product_name" name="product_name" oninput="this.value = this.value.toUpperCase();" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_category">Kategori:</label>
                        <select id="edit_category" name="category" required>
                            <option value="">Seçiniz</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" data-vat="<?= $category['vat_rate'] ?>"><?= $category['group_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_vat_rate">KDV Oranı:</label>
                        <input type="text" id="edit_vat_rate" name="vat_rate" readonly required> %
                    </div>
                    <div class="form-group">
                        <label for="edit_unit">Ürün Birimi:</label>
                        <select id="edit_unit" name="unit" required>
                            <option value="">Seçiniz</option>
                            <option value="Adet">Adet</option>
                            <option value="Kg">Kg</option>
                            <option value="Metre">Metre</option>
                            <option value="Paket">Paket</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_purchase_price_excl_vat">KDV Hariç Alış Fiyatı:</label>
                        <input type="text" id="edit_purchase_price_excl_vat" name="purchase_price_excl_vat" class="currency-format" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_purchase_price_incl_vat">KDV Dahil Alış Fiyatı:</label>
                        <input type="text" id="edit_purchase_price_incl_vat" name="purchase_price_incl_vat" class="currency-format" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_sale_price_incl_vat">Satış Fiyatı (KDV Dahil):</label>
                        <input type="text" id="edit_sale_price_incl_vat" name="sale_price_incl_vat" class="currency-format" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_profit_margin">Kar Oranı:</label>
                        <input type="number" id="edit_profit_margin" name="profit_margin" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_stock_code">Stok Kodu:</label>
                        <input type="text" id="edit_stock_code" name="stock_code" oninput="this.value = this.value.toUpperCase();">
                    </div>
                    <div class="form-group">
                        <label for="edit_fast_product_order">Hızlı Ürün Sırası:</label>
                        <input type="number" id="edit_fast_product_order" name="fast_product_order" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_critical_stock">Kritik Stok:</label>
                        <input type="number" id="edit_critical_stock" name="critical_stock" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_product_status">Ürün Satışa:</label>
                        <select id="edit_product_status" name="product_status" required>
                            <option value="Açık">Açık</option>
                            <option value="Kapalı">Kapalı</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_product_description">Ürün Açıklaması:</label>
                        <textarea id="edit_product_description" name="product_description" rows="4"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Ürün Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Silme Onay Modalı -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Ürünü Sil</h2>
            <p><strong id="deleteProductName"></strong> Ürün Kartını silmek istediğinize emin misiniz?</p>
            <div class="form-actions">
                <button id="confirmDeleteBtn" class="btn btn-danger">Evet</button>
                <button class="close btn btn-secondary">Hayır</button>
            </div>
        </div>
    </div>
</body>
</html>
