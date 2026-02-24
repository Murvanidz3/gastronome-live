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

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
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
    if (!$invoices) {
        $invoices = [];
    }

    // Parse notes
    $notes_data = [];
    if (!empty($company['notes'])) {
        $decoded = json_decode($company['notes'], true);
        if (is_array($decoded)) {
            $notes_data = $decoded;
            // Sort descending by date
            usort($notes_data, function ($a, $b) {
                return strtotime($b['date'] ?? '0') - strtotime($a['date'] ?? '0');
            });
        } else {
            // Migration case
            $notes_data[] = [
                'text' => $company['notes'],
                'date' => date('Y-m-d\TH:i:sP')
            ];
        }
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}


$pageTitle = 'Company Profile - ' . htmlspecialchars($company['name']);
$activePage = 'companies'; // keep companies nav item active
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="/companies" class="btn btn-icon btn-secondary" title="Back to Companies">
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
                style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); display: flex; flex-direction: column; height: 380px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div
                        style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;">
                        📝 Notes & Status Updates</div>
                    <span id="notesStatus"
                        style="font-size: 0.8rem; color: #4ade80; opacity: 0; transition: opacity 0.3s;">Saved</span>
                </div>
                <textarea id="companyNotes" class="glass-textarea"
                    style="height: 70px; min-height: 70px; resize: none; margin-bottom: 10px; font-size: 0.9rem;"
                    placeholder="Add a new update or note..."></textarea>
                <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                    <button id="saveNotesBtn" class="btn btn-primary btn-sm">Save Note</button>
                </div>

                <!-- Floating Cards List -->
                <div id="notesContainer"
                    style="display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto; padding-right: 5px;">
                    <?php if (empty($notes_data)): ?>
                        <div class="empty-state" style="padding: 10px;">
                            <p style="font-size: 0.85rem; margin: 0;">No updates yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notes_data as $note):
                            $nId = $note['id'] ?? uniqid('n_');
                            ?>
                            <div class="note-card hover-glow"
                                style="position: relative; padding: 10px 12px; padding-left: 16px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; cursor: pointer; border: 1px solid rgba(255, 255, 255, 0.05); transition: background 0.2s;"
                                onclick="openNoteModal(this)">
                                <!-- Thin shape linear gradient -->
                                <div
                                    style="position: absolute; left: -1px; top: -1px; bottom: -1px; width: 4px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px 0 0 8px;">
                                </div>

                                <!-- Action buttons -->
                                <div style="position: absolute; right: 8px; top: 10px; display: flex; gap: 6px;">
                                    <button class="btn btn-icon btn-sm btn-secondary"
                                        style="width: 24px; height: 24px; padding: 0; font-size: 0.7rem;"
                                        onclick="editNote('<?php echo $nId; ?>', this, event)" title="Edit">✏️</button>
                                    <button class="btn btn-icon btn-sm btn-danger"
                                        style="width: 24px; height: 24px; padding: 0; font-size: 0.7rem;"
                                        onclick="deleteNote('<?php echo $nId; ?>', event)" title="Delete">🗑</button>
                                </div>

                                <div style="display: none;" class="full-note-text">
                                    <?php echo htmlspecialchars($note['text']); ?>
                                </div>
                                <div style="display: none;" class="note-id"><?php echo $nId; ?></div>

                                <div
                                    style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; padding-right: 50px;">
                                    <?php echo date('M j, Y, H:i', strtotime($note['date'] ?? 'now')); ?>
                                </div>
                                <div
                                    style="font-size: 0.9rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-height: 20px; padding-right: 50px;">
                                    <?php echo htmlspecialchars($note['text']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Invoice History Card -->
            <div
                style="padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div
                        style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;">
                        🧾 Invoice History
                    </div>
                    <a href="/invoice.php?company_id=<?php echo $company['id']; ?>" class="btn btn-primary btn-sm"
                        style="display: flex; align-items: center; gap: 6px;">
                        ➕ Create Invoice
                    </a>
                </div>

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
                                    <th style="padding: 10px; text-align: center; width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td style="padding: 10px;"><?php echo date('d/m/Y', strtotime($inv['created_at'])); ?>
                                        </td>
                                        <td style="padding: 10px;">
                                            <a href="/view_invoice.php?id=<?php echo $inv['id']; ?>"
                                                style="color: var(--primary-color); text-decoration: none; font-weight: bold; transition: color 0.2s;"
                                                onmouseover="this.style.color='var(--primary-hover)'"
                                                onmouseout="this.style.color='var(--primary-color)'">
                                                #<?php echo htmlspecialchars($inv['invoice_number']); ?>
                                            </a>
                                        </td>
                                        <td style="padding: 10px; text-align: right; font-weight: bold;">
                                            ₾<?php echo number_format($inv['total_amount'], 2); ?></td>
                                        <td
                                            style="padding: 10px; text-align: center; display: flex; gap: 4px; justify-content: center;">
                                            <button class="btn btn-icon btn-sm btn-danger no-print"
                                                onclick="deleteInvoice(<?php echo $inv['id']; ?>, event)" title="Delete Invoice"
                                                style="font-size: 0.75rem; width: 24px; height: 24px; padding: 0;">🗑</button>
                                        </td>
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

<!-- Note Modal -->
<div class="modal-overlay" id="noteModal" style="display:none; z-index: 1000;">
    <div class="glass-card modal-card" style="max-width: 500px;">
        <div class="modal-header">
            <h2>📝 Note Update</h2>
            <button class="btn btn-icon btn-sm btn-secondary" onclick="closeNoteModal()">✕</button>
        </div>
        <div id="noteModalContent"
            style="padding: 10px 0; color: var(--text-primary); font-size: 1rem; line-height: 1.6; white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
        </div>
        <div class="modal-actions" style="margin-top: 20px;">
            <button class="btn btn-secondary" onclick="closeNoteModal()">Close</button>
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

    let editingNoteId = null;

    if (saveNotesBtn && companyNotes) {
        saveNotesBtn.addEventListener('click', async () => {
            const newNote = companyNotes.value.trim();
            if (!newNote) return; // Prevent empty saves

            saveNotesBtn.disabled = true;
            saveNotesBtn.textContent = 'Saving...';

            const payload = {
                id: <?php echo $company['id']; ?>,
                notes: newNote,
                action: editingNoteId ? 'edit' : 'add'
            };

            if (editingNoteId) {
                payload.note_id = editingNoteId;
            }

            try {
                const res = await fetch('/api/company_notes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) throw new Error('Failed to save note');

                notesStatus.style.opacity = '1';
                setTimeout(() => {
                    notesStatus.style.opacity = '0';
                    window.location.reload(true); // reload to fetch new notes history
                }, 500);
            } catch (err) {
                alert('Error saving note.');
                saveNotesBtn.disabled = false;
                saveNotesBtn.textContent = editingNoteId ? 'Update Note' : 'Save Note';
            }
        });
    }

    // Edit/Delete Logic
    function editNote(id, btnEl, e) {
        e.stopPropagation();
        const card = btnEl.closest('.note-card');
        const text = card.querySelector('.full-note-text').textContent;

        companyNotes.value = text;
        companyNotes.focus();
        editingNoteId = id;
        saveNotesBtn.textContent = 'Update Note';

        // Let's scroll to the textarea
        companyNotes.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    async function deleteNote(id, e) {
        e.stopPropagation();
        if (!confirm('Are you sure you want to delete this note?')) return;

        try {
            const res = await fetch('/api/company_notes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: <?php echo $company['id']; ?>,
                    action: 'delete',
                    note_id: id
                })
            });

            if (!res.ok) throw new Error('Failed to delete note');
            window.location.reload(true);
        } catch (err) {
            alert('Error deleting note.');
        }
    }

    // Modal Logic
    function openNoteModal(cardEl) {
        const fullText = cardEl.querySelector('.full-note-text').textContent;
        document.getElementById('noteModalContent').textContent = fullText;
        document.getElementById('noteModal').style.display = 'flex';
    }

    function closeNoteModal() {
        document.getElementById('noteModal').style.display = 'none';
    }

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        const modal = document.getElementById('noteModal');
        if (e.target === modal) {
            closeNoteModal();
        }
    });

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