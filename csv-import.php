<?php
/**
 * CSV Import Page
 */
require_once __DIR__ . '/auth/guard.php';

$pageTitle = 'CSV Import';
$activePage = 'csv-import';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>📥 CSV Import</h1>
</div>

<div class="glass-card" style="padding: 32px;">
    <!-- Upload Area -->
    <div class="upload-area" id="uploadArea">
        <div class="icon">📄</div>
        <div class="label">Drag & drop your CSV file here</div>
        <div class="hint">or click to choose a file · <strong>.csv only</strong></div>
        <input type="file" id="csvFileInput" accept=".csv" style="display:none;">
    </div>

    <!-- File Name Display -->
    <div id="fileInfo" class="hidden mt-2" style="display:none;">
        <div class="gap-row">
            <span id="fileName" style="color:var(--text-primary); font-weight:500;"></span>
            <button class="btn btn-sm btn-secondary" id="clearFileBtn">✕ Clear</button>
        </div>
    </div>

    <!-- Progress -->
    <div id="importProgress" class="hidden">
        <div class="progress-bar-wrapper">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <p class="text-muted text-center" style="font-size:0.85rem;">Importing…</p>
    </div>

    <!-- Action Button -->
    <div class="mt-3 text-center">
        <button class="btn btn-primary" id="importBtn" disabled>
            ⬆ Import Products
        </button>
    </div>

    <!-- Results -->
    <div id="importResults" class="import-results hidden"></div>
</div>

<!-- CSV Format Info -->
<div class="glass-card csv-info mt-3">
    <h3>📋 Expected CSV Format</h3>
    <p class="text-muted" style="font-size:0.88rem;">
        Your CSV file must include a header row. Required columns: <strong>barcode</strong>, <strong>name</strong>.
        Optional: image_url, quantity, price, comment.
    </p>
    <code>image_url,barcode,name,quantity,price,comment</code>
    <p class="text-muted mt-2" style="font-size:0.82rem;">
        → Existing products (matched by barcode) will be <strong>updated</strong>.<br>
        → New barcodes will be <strong>inserted</strong>.
    </p>
</div>

<script src="/js/csv.js"></script>

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