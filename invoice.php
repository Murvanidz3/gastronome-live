<?php
/**
 * Invoice Generator Page
 */
require_once __DIR__ . '/auth/guard.php';

$pageTitle = 'Invoice Generator';
$activePage = 'invoice';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="display: none;">
    <h1>🧾 Invoice Generator</h1>
</div>

<!-- Selection Controls -->
<div class="glass-card invoice-controls-card">
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
                    <th style="width: 80px;">Photo</th>
                    <th>Description</th>
                    <th style="width: 60px;">Qty</th>
                    <th style="width: 120px;">Price</th>
                    <th style="width: 120px;">Total</th>
                    <th class="no-print" style="width:50px;"></th>
                </tr>
            </thead>
            <tbody id="invoiceBody">
                <tr id="invoiceEmptyRow">
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="icon">🧾</div>
                            <p>Search and add products above to build your invoice.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot id="invoiceSummary" style="display:none;" class="print-summary-foot">
                <tr class="print-summary-row">
                    <td colspan="3" class="print-summary-cell"></td>
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
</script>
</body>

</html>