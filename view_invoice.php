<?php
/**
 * View Saved Invoice
 */
require_once __DIR__ . '/auth/guard.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id'])) {
    die("Invoice ID required.");
}

$id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];
$db = getDB();

$stmt = $db->prepare("
    SELECT i.*, c.name as company_name, c.company_id_number, c.address, c.phone 
    FROM invoices i
    JOIN companies c ON i.company_id = c.id
    WHERE i.id = :id AND i.user_id = :user_id
");
$stmt->execute([':id' => $id, ':user_id' => $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found or unauthorized.");
}

$items = [];
if (!empty($invoice['items_json'])) {
    $items = json_decode($invoice['items_json'], true) ?: [];
}

$pageTitle = 'View Invoice #' . htmlspecialchars($invoice['invoice_number']);
$activePage = 'invoice';
require_once __DIR__ . '/includes/header.php';

$invoiceCurrency = count($items) > 0 ? ($items[0]['currency'] ?? 'GEL') : 'GEL';
$currencySymbols = ['GEL' => '₾', 'EUR' => '€', 'USD' => '$'];
$symbol = $currencySymbols[$invoiceCurrency] ?? '₾';

$hasAnyPhoto = false;
foreach ($items as $item) {
    if (!empty($item['image_url'])) {
        $hasAnyPhoto = true;
        break;
    }
}
?>

<div class="page-header no-print"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>🧾 Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h1>
    <a href="/company-profile.php?id=<?php echo $invoice['company_id']; ?>" class="btn btn-secondary btn-sm">⬅ Back to
        Company</a>
</div>

<!-- Invoice Table -->
<div class="glass-card">
    <!-- Print Header -->
    <div class="print-header" style="display: flex !important;">
        <div class="print-header-top">
            <div class="print-logo-sec">
                <img src="/img/invlogo.png" alt="Kitchen & Living By Gastronome" class="print-logo">
                <div class="print-company-info">
                    <strong>Home Appliances LLC</strong><br>
                    VAT: 418 477 776
                </div>
            </div>
            <div class="print-meta-sec">
                <div class="invoice-number-box">
                    Invoice <span><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                </div>
            </div>
        </div>

        <div class="print-bill-to">
            <div class="bill-to-date bg-box">
                <?php echo date('d/m/Y', strtotime($invoice['invoice_date'] ?? $invoice['created_at'])); ?></div>
            <div class="bill-to-title bg-box">BILL TO</div>
            <div class="bill-to-inputs">
                <input type="text" value="<?php echo htmlspecialchars($invoice['company_name']); ?>" class="print-input"
                    style="font-weight: bold; font-family: 'BpgGEL', sans-serif;" readonly>
                <input type="text" value="<?php echo htmlspecialchars($invoice['company_id_number']); ?>"
                    class="print-input" style="font-family: 'BpgGEL', sans-serif;" readonly>
            </div>
        </div>
    </div>

    <div class="table-wrapper print-table-wrapper">
        <table class="data-table print-table" id="invoiceTable">
            <thead>
                <tr>
                    <?php if ($hasAnyPhoto): ?>
                        <th style="width: 80px;">Photo</th><?php endif; ?>
                    <th>Description</th>
                    <th style="width: 60px;">Qty</th>
                    <th style="width: 120px;">Price</th>
                    <th style="width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?php echo $hasAnyPhoto ? 5 : 4; ?>">
                            <div class="empty-state">
                                <p>No items stored in JSON for this invoice.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $subtotal = 0;
                    foreach ($items as $item):
                        $rowTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                        $subtotal += $rowTotal;
                        ?>
                        <tr>
                            <?php if ($hasAnyPhoto): ?>
                                <td>
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="" class="product-img"
                                            onerror="this.outerHTML='<div class=\\'product-img-placeholder\\' style=\\'font-size:0.9rem;\\'>🖼</div>'">
                                    <?php else: ?>
                                        <div class="product-img-placeholder" style="font-size:0.9rem;">🖼</div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><?php echo (int) ($item['quantity'] ?? 1); ?></td>
                            <td class="nowrap"><span class="price-tag"><?php echo $symbol; ?>
                                    <?php echo number_format($item['price'] ?? 0, 2); ?></span></td>
                            <td class="nowrap"><span class="price-tag"><?php echo $symbol; ?>
                                    <?php echo number_format($rowTotal, 2); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="print-summary-foot">
                <tr class="print-summary-row">
                    <td colspan="<?php echo $hasAnyPhoto ? 3 : 2; ?>" class="print-summary-cell"></td>
                    <td class="print-summary-cell label">Total</td>
                    <td class="print-summary-cell value">
                        <?php echo $symbol; ?><?php echo number_format($invoice['total_amount'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer Terms -->
    <div class="print-footer" style="display: block !important;">
        <p>Payment Terms: 70% in advance, 30% after delivery of goods in Tbilisi</p>
        <p>Payment should be done in national currency according to the official exchange rate for the day of payment.
        </p>
        <p>Estimate time of delivery : After pre-payment 9-10 weeks.</p>
        <br>
        <p>Home Appliances LLC, 418 477 776</p>
        <p>Bank Name: Bank of Georgia</p>
        <p>BAGAGE22</p>
        <p>Account Number:</p>
        <p>GE85BG0000000499134120</p>
        <br>
        <p style="font-weight:bold; font-size:1.05rem;">THANK YOU FOR YOUR BUSINESS!</p>
    </div>
</div>

<div class="invoice-actions mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()">🖨 Print Invoice</button>
</div>

<script>
    const originalTitle = document.title;
    window.addEventListener('beforeprint', () => {
        document.title = " ";
    });
    window.addEventListener('afterprint', () => {
        document.title = originalTitle;
    });

    const mobileBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    if (mobileBtn) {
        mobileBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
</script>

</main>
</body>

</html>