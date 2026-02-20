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

// Fetch invoice history
$invStmt = $db->prepare('SELECT * FROM invoices WHERE company_id = :cid ORDER BY created_at DESC');
$invStmt->execute([':cid' => $company_id]);
$invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

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

<div class="glass-card" style="padding: 32px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">

        <!-- Left Side: Company Details -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
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

        <!-- Right Side: Notes and Invoice History -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Notes Card -->
            <div
                style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); display: flex; flex-direction: column; height: 350px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div
                        style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;">
                        📝 Notes & Status Updates</div>
                    <span id="notesStatus"
                        style="font-size: 0.8rem; color: #4ade80; opacity: 0; transition: opacity 0.3s;">Saved</span>
                </div>
                <textarea id="companyNotes" class="glass-textarea" style="flex: 1; resize: none; margin-bottom: 12px;"
                    placeholder="Add comments, status updates, or everyday changes here..."><?php echo htmlspecialchars($company['notes'] ?? ''); ?></textarea>
                <div style="display: flex; justify-content: flex-end;">
                    <button id="saveNotesBtn" class="btn btn-primary btn-sm">Save Notes</button>
                </div>
            </div>

            <!-- Invoice History Card -->
            <div
                style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); flex: 1;">
                <div
                    style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; margin-bottom: 12px;">
                    🧾 Invoice History</div>

                <?php if (empty($invoices)): ?>
                    <div class="empty-state" style="padding: 30px 10px;">
                        <div class="icon" style="font-size: 2rem;">📭</div>
                        <p style="font-size: 0.9rem;">No invoices generated for this company yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper" style="max-height: 250px; overflow-y: auto;">
                        <table class="data-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 10px;">Date</th>
                                    <th style="padding: 10px;">Invoice #</th>
                                    <th style="padding: 10px; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td style="padding: 10px;"><?php echo date('d/m/Y', strtotime($inv['created_at'])); ?>
                                        </td>
                                        <td style="padding: 10px;">#<?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                        <td style="padding: 10px; text-align: right; font-weight: bold;">
                                            ₾<?php echo number_format($inv['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

    // Notes Saving Logic
    const saveNotesBtn = document.getElementById('saveNotesBtn');
    const companyNotes = document.getElementById('companyNotes');
    const notesStatus = document.getElementById('notesStatus');

    if (saveNotesBtn && companyNotes) {
        saveNotesBtn.addEventListener('click', async () => {
            saveNotesBtn.disabled = true;
            saveNotesBtn.textContent = 'Saving...';

            try {
                const res = await fetch('/api/company_notes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: <?php echo $company['id']; ?>,
                        notes: companyNotes.value
                    })
                });

                if (!res.ok) throw new Error('Failed to save notes');

                notesStatus.style.opacity = '1';
                setTimeout(() => notesStatus.style.opacity = '0', 2000);
            } catch (err) {
                alert('Error saving notes.');
            } finally {
                saveNotesBtn.disabled = false;
                saveNotesBtn.textContent = 'Save Notes';
            }
        });
    }
</script>
</body>

</html>