<?php
/**
 * Dashboard — Product List with CRUD
 */
require_once __DIR__ . '/auth/guard.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare('SELECT * FROM products WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute([':user_id' => $user_id]);
$products = $stmt->fetchAll();

$pageTitle = 'Products';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>📦 Products</h1>
    </div>
    <div class="gap-row">
        <span class="badge" id="productCount"><?php echo count($products); ?> items</span>
        <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" class="glass-input" placeholder="Search by barcode or name…">
        </div>
    </div>
</div>

<!-- Action Bar (visible when items selected) -->
<div class="action-bar glass-card" id="actionBar" style="display:none;">
    <div class="action-bar-left">
        <span id="selectedCount">0</span> selected
    </div>
    <div class="action-bar-right">
        <button class="btn btn-sm btn-secondary" id="deselectAllBtn">✕ Deselect</button>
        <button class="btn btn-sm btn-danger" id="deleteSelectedBtn">🗑 Delete Selected</button>
    </div>
</div>

<!-- Clear All Button -->
<div class="gap-row mb-2" style="justify-content:flex-end;">
    <button class="btn btn-sm btn-danger" id="clearAllBtn" <?php echo empty($products) ? 'style="display:none;"' : ''; ?>>
        🗑 Clear All Products
    </button>
</div>

<div class="glass-card">
    <div class="table-wrapper">
        <table class="data-table" id="productTable">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <label class="custom-checkbox">
                            <input type="checkbox" id="selectAll">
                            <span class="checkmark"></span>
                        </label>
                    </th>
                    <th>Image</th>
                    <th>Barcode</th>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Comment</th>
                    <th style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php if (empty($products)): ?>
                    <tr id="emptyRow">
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="icon">📭</div>
                                <p>No products yet. Import a CSV to get started.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr data-id="<?php echo $p['id']; ?>">
                            <td>
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="row-select" value="<?php echo $p['id']; ?>">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td>
                                <?php if (!empty($p['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($p['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($p['name']); ?>" class="product-img lightbox-img"
                                        loading="lazy" data-full="<?php echo htmlspecialchars($p['image_url']); ?>"
                                        onerror="this.outerHTML='<div class=\'product-img-placeholder\'>🖼</div>'">
                                <?php else: ?>
                                    <div class="product-img-placeholder">🖼</div>
                                <?php endif; ?>
                            </td>
                            <td class="nowrap"><?php echo htmlspecialchars($p['barcode']); ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><span class="qty-badge"><?php echo (int) $p['quantity']; ?></span></td>
                            <td class="nowrap"><span class="price-tag"><?php
                            $sym = ['GEL' => '₾', 'EUR' => '€', 'USD' => '$'][$p['currency'] ?? 'GEL'] ?? '₾';
                            echo $sym . number_format($p['price'], 2);
                            ?></span></td>
                            <td class="text-muted"><?php echo htmlspecialchars($p['comment'] ?? ''); ?></td>
                            <td class="nowrap">
                                <button class="btn btn-icon btn-sm btn-secondary edit-btn" title="Edit"
                                    data-id="<?php echo $p['id']; ?>"
                                    data-image="<?php echo htmlspecialchars($p['image_url'] ?? ''); ?>"
                                    data-barcode="<?php echo htmlspecialchars($p['barcode']); ?>"
                                    data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                    data-quantity="<?php echo (int) $p['quantity']; ?>"
                                    data-price="<?php echo number_format($p['price'], 2, '.', ''); ?>"
                                    data-currency="<?php echo htmlspecialchars($p['currency'] ?? 'GEL'); ?>"
                                    data-comment="<?php echo htmlspecialchars($p['comment'] ?? ''); ?>">
                                    ✏️
                                </button>
                                <button class="btn btn-icon btn-sm btn-danger delete-one-btn" title="Delete"
                                    data-id="<?php echo $p['id']; ?>">
                                    🗑
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal" style="display:none;">
    <div class="glass-card modal-card">
        <div class="modal-header">
            <h2>✏️ Edit Product</h2>
            <button class="btn btn-icon btn-sm btn-secondary" id="closeModal">✕</button>
        </div>
        <form id="editForm">
            <input type="hidden" id="editId">
            <div class="form-group">
                <label for="editImageUrl">Image URL</label>
                <input type="text" id="editImageUrl" class="glass-input" placeholder="https://...">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="editBarcode">Barcode *</label>
                    <input type="text" id="editBarcode" class="glass-input" required>
                </div>
                <div class="form-group">
                    <label for="editName">Product Name *</label>
                    <input type="text" id="editName" class="glass-input" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="editQuantity">Quantity</label>
                    <input type="number" id="editQuantity" class="glass-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="editPrice">Price</label>
                    <input type="number" id="editPrice" class="glass-input" min="0" step="0.01" value="0.00">
                </div>
                <div class="form-group" style="max-width:120px;">
                    <label for="editCurrency">Currency</label>
                    <select id="editCurrency" class="glass-input">
                        <option value="GEL">₾ GEL</option>
                        <option value="EUR">€ EUR</option>
                        <option value="USD">$ USD</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="editComment">Comment</label>
                <textarea id="editComment" class="glass-textarea" rows="3"></textarea>
            </div>
            <div id="editError" class="alert alert-danger" style="display:none;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelEdit">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="confirmModal" style="display:none;">
    <div class="glass-card modal-card modal-sm">
        <div class="modal-header">
            <h2>⚠️ Confirm Delete</h2>
        </div>
        <p id="confirmMessage" style="color:var(--text-secondary);margin-bottom:20px;">Are you sure?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancelDelete">Cancel</button>
            <button class="btn btn-danger" id="confirmDelete">🗑 Delete</button>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div class="lightbox-overlay" id="lightbox" style="display:none;">
    <button class="lightbox-close" id="lightboxClose">✕</button>
    <img src="" alt="" id="lightboxImg" class="lightbox-image">
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<script src="/js/app.js"></script>

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