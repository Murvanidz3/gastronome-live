<?php
/**
 * View Saved Invoice
 */
require_once __DIR__ . '/auth/guard.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id'])) {
    die("Invoice ID required.");
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$db = getDB();

$stmt = $db->prepare("
    SELECT i.*, c.name as company_name, c.company_id_number 
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
foreach($items as $item) {
    if (!empty($item['image_url'])) {
        $hasAnyPhoto = true; break;
    }
}
?>

<div class="page-header no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>🧾 Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h1>
    <a href="/company-profile.php?id=<?php echo $invoice['company_id']; ?>" class="btn btn-secondary btn-sm">⬅ Back to Company</a>
</div>

<style>
@media print {
    @page {
        margin: 0; /* Remove default browser header and footer */
    }
    body {
        margin: 1.5cm; /* Set custom print margins */
        background: white !important;
        color: black !important;
    }
    .no-print {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .glass-card, .invoice-preview-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .table-wrapper {
        border: none !important;
        background: transparent !important;
    }
    .data-table th, .data-table td {
        border-color: #ddd !important;
        background: transparent !important;
        color: black !important;
    }
    * {
        color: black !important;
    }
    .print-header { display: flex !important; margin-bottom: 2rem; }
    #invNumberDisplay { border: none !important; padding:0 !important; font-size: 1.2rem; font-weight:bold; }
    .invoice-table-header { padding: 10px !important; }
}

.invoice-preview-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 30px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

<!-- Invoice Preview (Printable area) -->
<div class="glass-card invoice-preview-card" id="printableInvoice">
    <div class="print-header" style="display:none; justify-content:space-between; align-items:flex-start; margin-bottom:2rem; width:100%;">
        <div>
            <!-- Print Header Content from original design, hidden by default -->
        </div>
    </div>

    <div class="invoice-header-info" style="display:flex; justify-content:space-between; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div>
            <h2 style="margin:0 0 10px 0;font-size:1.8rem;">INVOICE</h2>
            <p style="margin:0;font-size:0.9rem;color:var(--text-muted);">
                Date: <?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?>
            </p>
            <p style="margin:0;font-size:0.9rem;color:var(--text-muted);font-weight:bold;margin-top:5px;">
                No. <span id="invNumberDisplay" style="color:var(--text-primary);"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
            </p>
        </div>
        <div style="text-align:right;">
            <p style="margin:0;font-size:0.85rem;color:var(--text-secondary);text-transform:uppercase;">BILL TO</p>
            <p style="margin:5px 0 0 0;font-size:1.1rem;font-weight:bold;color:var(--text-primary);">
                <?php echo htmlspecialchars($invoice['company_name']); ?>
            </p>
            <p style="margin:2px 0 0 0;font-size:0.9rem;color:var(--text-muted);">
                ID: <?php echo htmlspecialchars($invoice['company_id_number']); ?>
            </p>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-wrapper print-table-wrapper" style="margin-bottom: 30px;">
        <table class="data-table print-table" id="invoiceTable">
            <thead>
                <tr>
                    <?php if($hasAnyPhoto): ?><th class="invoice-table-header" style="width: 50px;">Photo</th><?php endif; ?>
                    <th class="invoice-table-header">Description</th>
                    <th class="invoice-table-header" style="width: 80px;">Qty</th>
                    <th class="invoice-table-header nowrap" style="width: 120px;">Unit Price</th>
                    <th class="invoice-table-header nowrap" style="width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($items)): ?>
                    <tr><td colspan="<?php echo $hasAnyPhoto ? 5 : 4; ?>">No items stored in JSON for this invoice.</td></tr>
                <?php else: ?>
                    <?php foreach($items as $item): ?>
                        <tr>
                            <?php if($hasAnyPhoto): ?>
                            <td>
                                <?php if(!empty($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="" class="product-img" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                    <div class="product-img-placeholder" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border-radius:4px;">🖼</div>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><?php echo (int)($item['quantity'] ?? 1); ?></td>
                            <td class="nowrap"><?php echo $symbol; ?> <?php echo number_format($item['price'] ?? 0, 2); ?></td>
                            <td class="nowrap"><?php echo $symbol; ?> <?php echo number_format(($item['price']??0) * ($item['quantity']??1), 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot id="invoiceSummary" class="print-summary-foot">
                <tr class="print-summary-row">
                    <td colspan="<?php echo $hasAnyPhoto ? 3 : 2; ?>" class="print-summary-cell"></td>
                    <td class="print-summary-cell label" style="font-weight:bold;text-align:right;padding:10px;">Total</td>
                    <td class="print-summary-cell value" style="font-weight:bold;padding:10px;">
                        <?php echo $symbol; ?><?php echo number_format($invoice['total_amount'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer Terms -->
    <div class="print-footer" style="font-size: 0.8rem; color: var(--text-muted);">
        <p>Payment Terms: 70% in advance, 30% after delivery of goods in Tbilisi</p>
        <p>Payment should be done in national currency according to the official exchange rate for the day of payment.</p>
        <p>Estimate time of delivery : After pre-payment 9-10 weeks.</p>
        <br>
        <p>Home Appliances LLC, 418 477 776</p>
        <p>Bank Name: Bank of Georgia | BAGAGE22</p>
        <p>Account Number: GE85BG0000000499134120</p>
        <br>
        <p style="font-weight:bold; font-size:1.05rem; color: var(--text-primary);">THANK YOU FOR YOUR BUSINESS!</p>
    </div>
</div>

<div class="invoice-actions mt-3 no-print" style="display:flex; justify-content:flex-end; gap:10px;">
    <button class="btn btn-primary" onclick="window.print()">🖨 Print Invoice</button>
</div>

</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
