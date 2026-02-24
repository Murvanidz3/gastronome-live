<?php
/**
 * Invoice Generator Page
 */
require_once __DIR__ . '/auth/guard.php';
require_once __DIR__ . '/config/database.php';

$prefilled_company = null;
if (isset($_GET['company_id'])) {
    $cid = (int) $_GET['company_id'];
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, company_id_number FROM companies WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $cid, ':user_id' => $_SESSION['user_id']]);
    $prefilled_company = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch user's invoice history
$db = getDB();
$stmtInv = $db->prepare("
    SELECT i.*, c.name as company_name 
    FROM invoices i
    JOIN companies c ON i.company_id = c.id
    WHERE i.user_id = :user_id
    ORDER BY i.created_at DESC
");
$stmtInv->execute([':user_id' => $_SESSION['user_id']]);
$user_invoices = $stmtInv->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Invoice Generator';
$activePage = 'invoice';
require_once __DIR__ . '/includes/header.php';
?>
<script>
    window.PREFILLED_COMPANY = <?php echo $prefilled_company ? json_encode($prefilled_company) : 'null'; ?>;
</script>

<div class="page-header" style="display: none;">
    <h1>🧾 Invoice Generator</h1>
</div>

<!-- Selection Controls -->
<style>
    @media print {
        @page {
            margin: 0;
            /* Remove default browser header and footer */
        }

        body {
            margin: 1cm;
            /* Add some margin back to the page so content isn't cut off */
        }

        .invoice-controls-card {
            display: none !important;
            /* Hide the selection controls entirely when printing */
        }
    }
</style>
<div class="glass-card invoice-controls-card no-print">
    <div class="control-group">
        <label>Add products to invoice</label>
        <div class="invoice-search-wrapper">
            <input type="text" id="invoiceSearch" class="glass-input no-print"
                placeholder="Search product by name or barcode…" autocomplete="off">
            <div class="autocomplete-list" id="autocompleteList"></div>
        </div>
    </div>

    <div class="control-group">
        <label>Select Company</label>
        <div class="invoice-search-wrapper" style="position: relative;">
            <input type="text" id="companySearch" class="glass-input no-print"
                placeholder="Search company by name or ID…" autocomplete="off">
            <div id="selectedCompanyBadge"
                style="display: none; position: absolute; top: 50%; left: 12px; transform: translateY(-50%); background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; color: #fff; align-items: center; gap: 8px;">
                <span id="selectedCompanyNameText"></span>
                <span id="clearCompanyBtn"
                    style="cursor: pointer; color: #fca5a5; font-weight: bold; padding-left: 5px; border-left: 1px solid rgba(255,255,255,0.2);"
                    title="Clear Selection">✕</span>
            </div>
            <div class="autocomplete-list" id="companyAutocompleteList"></div>
        </div>
    </div>
</div>

<!-- Invoice Table -->
<div class="glass-card">
    <!-- Print Header (visible only in print or dynamically stylized) -->
    <div class="print-header">
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
                    Invoice <span id="invNumberDisplay"></span>
                </div>
            </div>
        </div>

        <div class="print-bill-to">
            <div class="bill-to-date bg-box"><?php echo date('d/m/Y'); ?></div>
            <div class="bill-to-title bg-box">BILL TO</div>
            <div class="bill-to-inputs">
                <input type="text" id="billToCompany" placeholder="Company Name" class="print-input"
                    style="font-weight: bold; font-family: 'BpgGEL', sans-serif;">
                <input type="text" id="billToId" placeholder="Company ID (ს/კ)" class="print-input"
                    style="font-family: 'BpgGEL', sans-serif;">
            </div>
        </div>
    </div>

    <div class="table-wrapper print-table-wrapper">
        <table class="data-table print-table" id="invoiceTable">
            <thead>
                <tr>
                    <th style="width: 80px;" id="th-invoice-photo">Photo</th>
                    <th>Description</th>
                    <th style="width: 60px;">Qty</th>
                    <th style="width: 120px;">Price</th>
                    <th style="width: 120px;">Total</th>
                    <th class="no-print" style="width:50px;"></th>
                </tr>
            </thead>
            <tbody id="invoiceBody">
                <tr id="invoiceEmptyRow">
                    <td colspan="6" id="emptyRowColspan">
                        <div class="empty-state">
                            <div class="icon">🧾</div>
                            <p>Search and add products above to build your invoice.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot id="invoiceSummary" style="display:none;" class="print-summary-foot">
                <tr class="print-summary-row">
                    <td colspan="3" class="print-summary-cell" id="summaryColspan"></td>
                    <td class="print-summary-cell label">Total</td>
                    <td class="print-summary-cell value" id="invoiceGrandTotal">₾0.00</td>
                    <td class="print-summary-cell no-print"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer Terms -->
    <div class="print-footer" id="printFooter" style="display:none;">
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

<!-- Actions -->
<div class="invoice-actions mt-3" id="invoiceActions" style="display:none;">
    <button class="btn btn-secondary" id="clearInvoiceBtn">🗑 Clear All</button>
    <button class="btn btn-primary" id="printInvoiceBtn">🖨 Print Invoice</button>
</div>

<!-- Global Invoice History Section -->
<div class="glass-card mt-4 no-print" style="margin-top: 40px; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 1.25rem; margin: 0; color: var(--text-primary);">🗂 Recent Invoices</h2>
    </div>

    <?php if (empty($user_invoices)): ?>
        <div class="empty-state" style="padding: 30px 10px;">
            <div class="icon" style="font-size: 2rem;">📭</div>
            <p style="font-size: 0.9rem;">You haven't generated any invoices yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper" style="overflow-x: auto;">
            <table class="data-table" style="font-size: 0.95rem;">
                <thead>
                    <tr>
                        <th style="padding: 12px; text-align: left;">Date</th>
                        <th style="padding: 12px; text-align: left;">Invoice #</th>
                        <th style="padding: 12px; text-align: left;">Bill To</th>
                        <th style="padding: 12px; text-align: right;">Total Amount</th>
                        <th style="padding: 12px; text-align: center; width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_invoices as $inv): ?>
                        <tr class="hover-glow" style="cursor: default;">
                            <td style="padding: 12px; color: var(--text-secondary);">
                                <?php echo date('d/m/Y H:i', strtotime($inv['created_at'])); ?>
                            </td>
                            <td style="padding: 12px;">
                                <a href="/view_invoice.php?id=<?php echo $inv['id']; ?>"
                                    style="color: var(--primary-color); text-decoration: none; font-weight: bold; transition: color 0.2s;"
                                    onmouseover="this.style.color='var(--primary-hover)'"
                                    onmouseout="this.style.color='var(--primary-color)'">
                                    #<?php echo htmlspecialchars($inv['invoice_number']); ?>
                                </a>
                            </td>
                            <td style="padding: 12px; color: var(--text-primary); font-weight: 500;">
                                <?php echo htmlspecialchars($inv['company_name']); ?>
                            </td>
                            <td style="padding: 12px; text-align: right; font-weight: bold; color: var(--text-primary);">
                                ₾<?php echo number_format($inv['total_amount'], 2); ?>
                            </td>
                            <td style="padding: 12px; text-align: center; display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <a href="/view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn btn-secondary btn-sm"
                                    style="font-size: 0.75rem; padding: 4px 10px;">
                                    View
                                </a>
                                <button class="btn btn-icon btn-sm btn-danger no-print" onclick="deleteInvoice(<?php echo $inv['id']; ?>, event)" title="Delete Invoice" style="font-size: 0.8rem; width: 26px; height: 26px; padding: 0;">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="/js/invoice.js?v=<?php echo time(); ?>"></script>

</main>
<script>
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

    async function deleteInvoice(id, event) {
        if (event) event.stopPropagation();
        if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) return;

        try {
            const res = await fetch('/api/delete_invoice.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            if (!res.ok) throw new Error('Failed to delete invoice');
            
            // Reload page to update the table
            window.location.reload(true);
        } catch (err) {
            alert('Error deleting invoice.');
            console.error(err);
        }
    }
</script>
</body>

</html>