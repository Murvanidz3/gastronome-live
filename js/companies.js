/**
 * Companies Dashboard Logic
 * Handles inline edit modal, multi-select, and API calls.
 */
(function () {
    'use strict';

    // ─── DOM Elements ───
    const tableBody = document.getElementById('companiesTableBody');
    const selectAllCb = document.getElementById('selectAll');
    const rowCbs = document.querySelectorAll('.row-select');
    const actionBar = document.getElementById('actionBar');
    const selectedCount = document.getElementById('selectedCount');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const addCompanyBtn = document.getElementById('addCompanyBtn');

    const editModal = document.getElementById('editModal');
    const closeModal = document.getElementById('closeModal');
    const cancelEdit = document.getElementById('cancelEdit');
    const editForm = document.getElementById('editForm');
    const modalTitle = document.getElementById('modalTitle');
    const editError = document.getElementById('editError');

    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const cancelDelete = document.getElementById('cancelDelete');
    const confirmDeleteBtn = document.getElementById('confirmDelete');

    let itemToDelete = null; // null or array of IDs

    if (!tableBody) return;

    // ─── Selection Logic ───
    function updateSelection() {
        const checked = document.querySelectorAll('.row-select:checked');
        const count = checked.length;
        if (count > 0) {
            actionBar.style.display = 'flex';
            selectedCount.textContent = count;
            selectAllCb.checked = (count === document.querySelectorAll('.row-select').length);
        } else {
            actionBar.style.display = 'none';
            selectAllCb.checked = false;
        }
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            const isChecked = this.checked;
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = isChecked);
            updateSelection();
        });
    }

    // Since rows might be dynamic (if we added client side rendering), attach to body
    tableBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('row-select')) {
            updateSelection();
        }
    });

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = false);
            updateSelection();
        });
    }

    // ─── Search Filter ───
    const companiesSearch = document.getElementById('companiesSearch');
    if (companiesSearch) {
        companiesSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr:not(#emptyRow)');
            let hasVisible = false;

            rows.forEach(row => {
                const name = row.children[1].textContent.toLowerCase();
                const idNum = row.children[2].textContent.toLowerCase();

                if (name.includes(q) || idNum.includes(q)) {
                    row.style.display = '';
                    hasVisible = true;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) {
                emptyRow.style.display = hasVisible ? 'none' : '';
            } else if (!hasVisible && rows.length > 0) {
                // If there's no emptyRow but we hid everything, create a temporary one
                const newEmpty = document.createElement('tr');
                newEmpty.id = 'emptyRow';
                newEmpty.innerHTML = `<td colspan="6"><div class="empty-state"><div class="icon">📭</div><p>No companies found matching "${q}".</p></div></td>`;
                tableBody.appendChild(newEmpty);
            }
        });
    }

    // ─── Add/Edit Modal ───
    function openEditModal(company = null) {
        editError.style.display = 'none';
        if (company) {
            modalTitle.textContent = '✏️ Edit Company';
            document.getElementById('editId').value = company.id;
            document.getElementById('editName').value = company.name;
            document.getElementById('editCompanyId').value = company.company_id;
            document.getElementById('editAddress').value = company.address;
            document.getElementById('editPhone').value = company.phone;
        } else {
            modalTitle.textContent = '➕ Add Company';
            editForm.reset();
            document.getElementById('editId').value = '';
        }
        editModal.style.display = 'flex';
        document.getElementById('editName').focus();
    }

    function hideEditModal() {
        editModal.style.display = 'none';
    }

    if (addCompanyBtn) addCompanyBtn.addEventListener('click', () => openEditModal());
    if (closeModal) closeModal.addEventListener('click', hideEditModal);
    if (cancelEdit) cancelEdit.addEventListener('click', hideEditModal);

    // Dynamic buttons
    tableBody.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            openEditModal({
                id: editBtn.dataset.id,
                name: editBtn.dataset.name,
                company_id: editBtn.dataset.companyId,
                address: editBtn.dataset.address,
                phone: editBtn.dataset.phone
            });
            return;
        }

        const deleteBtn = e.target.closest('.delete-one-btn');
        if (deleteBtn) {
            itemToDelete = [deleteBtn.dataset.id];
            confirmMessage.textContent = 'Are you sure you want to delete this company?';
            confirmModal.style.display = 'flex';
        }
    });

    // ─── Save Changes (API calls) ───
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                id: document.getElementById('editId').value,
                name: document.getElementById('editName').value.trim(),
                company_id_number: document.getElementById('editCompanyId').value.trim(),
                address: document.getElementById('editAddress').value.trim(),
                phone: document.getElementById('editPhone').value.trim()
            };

            const btn = editForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            editError.style.display = 'none';

            try {
                const res = await fetch('/api/companies.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                location.reload(); // Quick refresh to show changes
            } catch (err) {
                editError.textContent = err.message || 'Save failed';
                editError.style.display = '';
                btn.disabled = false;
                btn.textContent = '💾 Save';
            }
        });
    }

    // ─── Deletion ───
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', () => {
            const checked = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
            if (!checked.length) return;
            itemToDelete = checked;
            confirmMessage.textContent = `Delete ${checked.length} selected companies?`;
            confirmModal.style.display = 'flex';
        });
    }

    if (cancelDelete) cancelDelete.addEventListener('click', () => confirmModal.style.display = 'none');

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            if (!itemToDelete) return;

            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Deleting...';

            try {
                const res = await fetch('/api/companies.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: itemToDelete })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                location.reload();
            } catch (err) {
                alert(err.message || 'Delete failed');
                confirmModal.style.display = 'none';
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = '🗑 Delete';
            }
        });
    }

})();
