<?php
/**
 * Profile & User Management Page
 */
require_once __DIR__ . '/auth/guard.php';

// Only masho can access user management
if (!isset($_SESSION['username']) || strtolower($_SESSION['username']) !== 'masho') {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/config/database.php';

$db = getDB();

// Get current user info
$currentUserId = $_SESSION['user_id'] ?? 0;
$stmt = $db->prepare('SELECT id, full_name, username, mobile FROM users WHERE id = :id');
$stmt->execute([':id' => $currentUserId]);
$currentUser = $stmt->fetch();

// Get all users
$allUsers = $db->query('SELECT id, full_name, username, mobile, created_at FROM users ORDER BY created_at ASC')->fetchAll();

$pageTitle = 'Manage Profile';
$activePage = 'profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>👤 Manage Profile</h1>
</div>

<!-- My Profile Section -->
<div class="glass-card" style="padding:28px 32px; margin-bottom:28px;">
    <h2 style="font-size:1.15rem; font-weight:700; margin-bottom:20px;">My Profile</h2>
    <form id="profileForm">
        <input type="hidden" id="profileId" value="<?php echo (int) $currentUser['id']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="profileFullName">Full Name</label>
                <input type="text" id="profileFullName" class="glass-input"
                    value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="profileUsername">Username</label>
                <input type="text" id="profileUsername" class="glass-input"
                    value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="profileMobile">Mobile</label>
                <input type="text" id="profileMobile" class="glass-input"
                    value="<?php echo htmlspecialchars($currentUser['mobile'] ?? ''); ?>"
                    placeholder="+995 xxx xxx xxx">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%;">💾 Save Profile</button>
            </div>
        </div>
        <div id="profileAlert" class="alert" style="display:none;"></div>
    </form>
</div>

<!-- Change Password Section -->
<div class="glass-card" style="padding:28px 32px; margin-bottom:28px;">
    <h2 style="font-size:1.15rem; font-weight:700; margin-bottom:20px;">🔒 Change Password</h2>
    <form id="passwordForm">
        <div class="form-row">
            <div class="form-group">
                <label for="currentPassword">Current Password</label>
                <input type="password" id="currentPassword" class="glass-input" required>
            </div>
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <input type="password" id="newPassword" class="glass-input" required minlength="4">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <input type="password" id="confirmPassword" class="glass-input" required minlength="4">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%;">🔑 Change Password</button>
            </div>
        </div>
        <div id="passwordAlert" class="alert" style="display:none;"></div>
    </form>
</div>

<!-- User Management Section -->
<div class="glass-card" style="padding:28px 32px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 style="font-size:1.15rem; font-weight:700;">👥 All Users</h2>
        <button class="btn btn-sm btn-primary" id="addUserBtn">➕ Create User</button>
    </div>

    <div class="table-wrapper">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Mobile</th>
                    <th>Created</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($allUsers as $i => $u): ?>
                    <tr data-id="<?php echo $u['id']; ?>">
                        <td>
                            <?php echo $i + 1; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($u['full_name']); ?>
                        </td>
                        <td><span class="qty-badge">
                                <?php echo htmlspecialchars($u['username']); ?>
                            </span></td>
                        <td>
                            <?php echo htmlspecialchars($u['mobile'] ?? ''); ?>
                        </td>
                        <td class="text-muted nowrap">
                            <?php echo date('d/m/Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td class="nowrap">
                            <button class="btn btn-icon btn-sm btn-secondary edit-user-btn" title="Edit"
                                data-id="<?php echo $u['id']; ?>"
                                data-fullname="<?php echo htmlspecialchars($u['full_name']); ?>"
                                data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                data-mobile="<?php echo htmlspecialchars($u['mobile'] ?? ''); ?>">
                                ✏️
                            </button>
                            <?php if ($u['id'] != $currentUserId): ?>
                                <button class="btn btn-icon btn-sm btn-danger delete-user-btn" title="Delete"
                                    data-id="<?php echo $u['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($u['full_name']); ?>">
                                    🗑
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create / Edit User Modal -->
<div class="modal-overlay" id="userModal" style="display:none;">
    <div class="glass-card modal-card">
        <div class="modal-header">
            <h2 id="userModalTitle">➕ Create User</h2>
            <button class="btn btn-icon btn-sm btn-secondary" id="closeUserModal">✕</button>
        </div>
        <form id="userForm">
            <input type="hidden" id="userId" value="">
            <div class="form-row">
                <div class="form-group">
                    <label for="userFullName">Full Name *</label>
                    <input type="text" id="userFullName" class="glass-input" required>
                </div>
                <div class="form-group">
                    <label for="userUsername">Username *</label>
                    <input type="text" id="userUsername" class="glass-input" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="userMobile">Mobile</label>
                    <input type="text" id="userMobile" class="glass-input" placeholder="+995 xxx xxx xxx">
                </div>
                <div class="form-group" id="userPasswordGroup">
                    <label for="userPassword">Password *</label>
                    <input type="password" id="userPassword" class="glass-input" minlength="4">
                </div>
            </div>
            <div id="userAlert" class="alert alert-danger" style="display:none;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="userSubmitBtn">➕ Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="deleteUserModal" style="display:none;">
    <div class="glass-card modal-card modal-sm">
        <div class="modal-header">
            <h2>⚠️ Delete User</h2>
        </div>
        <p id="deleteUserMsg" style="color:var(--text-secondary);margin-bottom:20px;"></p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancelDeleteUser">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteUser">🗑 Delete</button>
        </div>
    </div>
</div>

<script src="/js/profile.js"></script>

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