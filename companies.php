<?php
/**
 * Dashboard — Companies List with CRUD
 */
require_once __DIR__ . '/auth/guard.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare('SELECT * FROM companies WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute([':user_id' => $user_id]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Companies';
$activePage = 'companies';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>🏢 Companies</h1>
    </div>
    <div class="gap-row">
        <span class="badge" id="companyCount">
            <?php echo count($companies); ?> companies
        </span>
        <button class="btn btn-primary" id="addCompanyBtn">➕ Add Company</button>
    </div>
</div>

<!-- Search Bar -->
<div class="search-wrapper" style="margin-bottom: 24px;">
    <input type="text" id="companiesSearch" class="glass-input" placeholder="Search companies by name or ID..."
        autocomplete="off">
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

<div class="glass-card">
    <div class="table-wrapper">
        <table class="data-table" id="companiesTable">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <label class="custom-checkbox">
                            <input type="checkbox" id="selectAll">
                            <span class="checkmark"></span>
                        </label>
                    </th>
                    <th>Company Name</th>
                    <th>Company ID (ს/კ)</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody id="companiesTableBody">
                <?php if (empty($companies)): ?>
                    <tr id="emptyRow">
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="icon">📭</div>
                                <p>No companies yet. Add one to get started.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($companies as $c): ?>
                        <tr data-id="<?php echo $c['id']; ?>">
                            <td>
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="row-select" value="<?php echo $c['id']; ?>">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td><strong>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </strong></td>
                            <td class="nowrap">
                                <?php echo htmlspecialchars($c['company_id_number']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($c['address']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($c['phone']); ?>
                            </td>
                            <td class="nowrap">
                                <button class="btn btn-icon btn-sm btn-secondary edit-btn" title="Edit"
                                    data-id="<?php echo $c['id']; ?>" data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                    data-company-id="<?php echo htmlspecialchars($c['company_id_number']); ?>"
                                    data-address="<?php echo htmlspecialchars($c['address']); ?>"
                                    data-phone="<?php echo htmlspecialchars($c['phone']); ?>">
                                    ✏️
                                </button>
                                <button class="btn btn-icon btn-sm btn-danger delete-one-btn" title="Delete"
                                    data-id="<?php echo $c['id']; ?>">
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

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="editModal" style="display:none;">
    <div class="glass-card modal-card">
        <div class="modal-header">
            <h2 id="modalTitle">➕ Add Company</h2>
            <button class="btn btn-icon btn-sm btn-secondary" id="closeModal">✕</button>
        </div>
        <form id="editForm">
            <input type="hidden" id="editId">
            <div class="form-group">
                <label for="editName">Company Name *</label>
                <input type="text" id="editName" class="glass-input" required>
            </div>
            <div class="form-group">
                <label for="editCompanyId">Company ID (ს/კ) *</label>
                <input type="text" id="editCompanyId" class="glass-input" required>
            </div>
            <div class="form-group">
                <label for="editAddress">Address</label>
                <textarea id="editAddress" class="glass-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="editPhone">Phone</label>
                <input type="text" id="editPhone" class="glass-input">
            </div>
            <div id="editError" class="alert alert-danger" style="display:none;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelEdit">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save</button>
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

<script src="/js/companies.js"></script>

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