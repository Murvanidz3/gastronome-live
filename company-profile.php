<?php
/**
 * Company Profile Page
 * Displays details for a single company
 */
require_once __DIR__ . '/auth/guard.php';
require_once __DIR__ . '/config/database.php';

$company_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$company_id) {
    header('Location: /companies.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM companies WHERE id = :id AND user_id = :user_id');
$stmt->execute([':id' => $company_id, ':user_id' => $user_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    // Company not found or does not belong to user
    header('Location: /companies.php');
    exit;
}

$pageTitle = 'Company Profile - ' . htmlspecialchars($company['name']);
$activePage = 'companies'; // keep companies nav item active
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="/companies.php" class="btn btn-icon btn-secondary" title="Back to Companies">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
            </a>
            <h1>🏢
                <?php echo htmlspecialchars($company['name']); ?>
            </h1>
        </div>
    </div>
</div>

<div class="glass-card" style="padding: 32px; max-width: 800px;">
    <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">

        <div
            style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
            <div
                style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                Company Name</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: var(--text-primary);">
                <?php echo htmlspecialchars($company['name']); ?>
            </div>
        </div>

        <div
            style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
            <div
                style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                Company ID (ს/კ)</div>
            <div style="font-size: 1.15rem; color: var(--text-primary); font-family: monospace;">
                <?php echo htmlspecialchars($company['company_id_number']); ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div
                style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
                <div
                    style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Phone Number</div>
                <div style="font-size: 1.1rem; color: var(--text-primary);">
                    <?php echo $company['phone'] ? htmlspecialchars($company['phone']) : '<span style="color:var(--text-muted); font-style: italic;">Not provided</span>'; ?>
                </div>
            </div>

            <div
                style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
                <div
                    style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Added On</div>
                <div style="font-size: 1.1rem; color: var(--text-primary);">
                    <?php echo date('F j, Y, g:i a', strtotime($company['created_at'])); ?>
                </div>
            </div>
        </div>

        <div
            style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
            <div
                style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                Address</div>
            <div style="font-size: 1.1rem; color: var(--text-primary); line-height: 1.6;">
                <?php echo $company['address'] ? nl2br(htmlspecialchars($company['address'])) : '<span style="color:var(--text-muted); font-style: italic;">No address provided</span>'; ?>
            </div>
        </div>

    </div>
</div>

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