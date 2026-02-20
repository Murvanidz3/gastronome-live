/**
 * Dashboard — Live Search + CRUD Operations
 * Select all, bulk delete, individual delete, edit modal
 */
(function () {
    'use strict';

    const BASE = '';
    const CURRENCY_SYMBOLS = { GEL: '₾', EUR: '€', USD: '$' };

    // DOM Elements
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('productTableBody');
    const selectAllCb = document.getElementById('selectAll');
    const actionBar = document.getElementById('actionBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const deleteSelBtn = document.getElementById('deleteSelectedBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const productCountEl = document.getElementById('productCount');

    // Edit Modal
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    const editId = document.getElementById('editId');
    const editImageUrl = document.getElementById('editImageUrl');
    const editBarcode = document.getElementById('editBarcode');
    const editName = document.getElementById('editName');
    const editQuantity = document.getElementById('editQuantity');
    const editPrice = document.getElementById('editPrice');
    const editComment = document.getElementById('editComment');
    const editCurrency = document.getElementById('editCurrency');
    const editError = document.getElementById('editError');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelEditBtn = document.getElementById('cancelEdit');

    // Confirm Modal
    const confirmModal = document.getElementById('confirmModal');
    const confirmMsg = document.getElementById('confirmMessage');
    const cancelDelBtn = document.getElementById('cancelDelete');
    const confirmDelBtn = document.getElementById('confirmDelete');

    if (!searchInput || !tableBody) return;

    let pendingDeleteAction = null;
    let debounceTimer = null;

    // ─── Live Search ───
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => performSearch(this.value.trim()), 300);
    });

    function performSearch(query) {
        fetch(`${BASE}/api/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(products => {
                renderTable(products);
                updateProductCount(products.length);
            })
            .catch(err => console.error('Search error:', err));
    }

    function renderTable(products) {
        if (!products.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="icon">🔍</div>
                            <p>No products match your search.</p>
                        </div>
                    </td>
                </tr>`;
            updateSelectionUI();
            return;
        }

        tableBody.innerHTML = products.map(p => `
            <tr data-id="${p.id}">
                <td>
                    <label class="custom-checkbox">
                        <input type="checkbox" class="row-select" value="${p.id}">
                        <span class="checkmark"></span>
                    </label>
                </td>
                <td>
                    ${p.image_url
                ? `<img src="${escHtml(p.image_url)}" alt="${escHtml(p.name)}" class="product-img lightbox-img" data-full="${escAttr(p.image_url)}" loading="lazy" onerror="this.outerHTML='<div class=\\'product-img-placeholder\\'>🖼</div>'">`
                : `<div class="product-img-placeholder">🖼</div>`}
                </td>
                <td class="nowrap">${escHtml(p.barcode)}</td>
                <td>${escHtml(p.name)}</td>
                <td><span class="qty-badge">${parseInt(p.quantity)}</span></td>
                <td class="nowrap"><span class="price-tag">${CURRENCY_SYMBOLS[p.currency] || '₾'}${parseFloat(p.price).toFixed(2)}</span></td>
                <td class="text-muted">${escHtml(p.comment || '')}</td>
                <td class="nowrap">
                    <button class="btn btn-icon btn-sm btn-secondary edit-btn" title="Edit"
                        data-id="${p.id}"
                        data-image="${escAttr(p.image_url || '')}"
                        data-barcode="${escAttr(p.barcode)}"
                        data-name="${escAttr(p.name)}"
                        data-quantity="${parseInt(p.quantity)}"
                        data-price="${parseFloat(p.price).toFixed(2)}"
                        data-currency="${escAttr(p.currency || 'GEL')}"
                        data-comment="${escAttr(p.comment || '')}">
                        ✏️
                    </button>
                    <button class="btn btn-icon btn-sm btn-danger delete-one-btn" title="Delete" data-id="${p.id}">
                        🗑
                    </button>
                </td>
            </tr>`).join('');

        bindRowEvents();
        updateSelectionUI();
    }

    // ─── Checkbox Selection ───
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            const checkboxes = tableBody.querySelectorAll('.row-select');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectionUI();
        });
    }

    // Delegate checkbox changes
    tableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-select')) {
            updateSelectionUI();
        }
    });

    function getSelectedIds() {
        return Array.from(tableBody.querySelectorAll('.row-select:checked')).map(cb => cb.value);
    }

    function updateSelectionUI() {
        const ids = getSelectedIds();
        const count = ids.length;
        if (count > 0) {
            actionBar.style.display = '';
            selectedCountEl.textContent = count;
        } else {
            actionBar.style.display = 'none';
        }
        // Update select-all state
        const allCbs = tableBody.querySelectorAll('.row-select');
        if (selectAllCb) {
            selectAllCb.checked = allCbs.length > 0 && count === allCbs.length;
        }
    }

    function updateProductCount(count) {
        if (productCountEl) productCountEl.textContent = count + ' items';
    }

    // ─── Deselect All ───
    deselectAllBtn.addEventListener('click', () => {
        tableBody.querySelectorAll('.row-select').forEach(cb => cb.checked = false);
        if (selectAllCb) selectAllCb.checked = false;
        updateSelectionUI();
    });

    // ─── Delete Selected ───
    deleteSelBtn.addEventListener('click', () => {
        const ids = getSelectedIds();
        if (!ids.length) return;
        showConfirm(`Delete ${ids.length} selected product(s)?`, () => {
            deleteProducts({ ids: ids.map(Number) });
        });
    });

    // ─── Clear All ───
    clearAllBtn.addEventListener('click', () => {
        showConfirm('Delete ALL products? This cannot be undone.', () => {
            deleteProducts({ all: true });
        });
    });

    // ─── Delete Single ───
    function bindRowEvents() {
        tableBody.querySelectorAll('.delete-one-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                showConfirm('Delete this product?', () => {
                    deleteProducts({ ids: [Number(id)] });
                });
            });
        });

        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                openEditModal(this.dataset);
            });
        });
    }

    // Bind events on initial page load rows
    bindRowEvents();

    // ─── Delete API ───
    function deleteProducts(payload) {
        fetch(`${BASE}/api/delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Refresh the table
                    performSearch(searchInput.value.trim());
                    if (selectAllCb) selectAllCb.checked = false;
                    updateSelectionUI();
                    if (payload.all) {
                        clearAllBtn.style.display = 'none';
                    }
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => alert('Delete failed: ' + err.message));
    }

    // ─── Confirm Modal ───
    function showConfirm(message, onConfirm) {
        confirmMsg.textContent = message;
        confirmModal.style.display = '';
        pendingDeleteAction = onConfirm;
    }

    confirmDelBtn.addEventListener('click', () => {
        confirmModal.style.display = 'none';
        if (pendingDeleteAction) {
            pendingDeleteAction();
            pendingDeleteAction = null;
        }
    });

    cancelDelBtn.addEventListener('click', () => {
        confirmModal.style.display = 'none';
        pendingDeleteAction = null;
    });

    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            confirmModal.style.display = 'none';
            pendingDeleteAction = null;
        }
    });

    // ─── Edit Modal ───
    function openEditModal(data) {
        editId.value = data.id;
        editImageUrl.value = data.image || '';
        editBarcode.value = data.barcode || '';
        editName.value = data.name || '';
        editQuantity.value = data.quantity || 0;
        editPrice.value = data.price || '0.00';
        editCurrency.value = data.currency || 'GEL';
        editComment.value = data.comment || '';
        editError.style.display = 'none';
        editModal.style.display = '';
    }

    function closeEdit() {
        editModal.style.display = 'none';
        editError.style.display = 'none';
    }

    closeModalBtn.addEventListener('click', closeEdit);
    cancelEditBtn.addEventListener('click', closeEdit);
    editModal.addEventListener('click', (e) => {
        if (e.target === editModal) closeEdit();
    });

    editForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const payload = {
            id: Number(editId.value),
            image_url: editImageUrl.value.trim(),
            barcode: editBarcode.value.trim(),
            name: editName.value.trim(),
            quantity: parseInt(editQuantity.value) || 0,
            price: parseFloat(editPrice.value) || 0,
            currency: editCurrency.value,
            comment: editComment.value.trim(),
        };

        if (!payload.barcode || !payload.name) {
            editError.textContent = 'Barcode and name are required.';
            editError.style.display = '';
            return;
        }

        fetch(`${BASE}/api/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeEdit();
                    performSearch(searchInput.value.trim());
                } else {
                    editError.textContent = data.error || 'Update failed';
                    editError.style.display = '';
                }
            })
            .catch(err => {
                editError.textContent = 'Update failed: ' + err.message;
                editError.style.display = '';
            });
    });

    // ─── Utilities ───
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ─── Lightbox ───
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCap = document.getElementById('lightboxCaption');
    const lightboxClose = document.getElementById('lightboxClose');

    // Delegated click on any lightbox-img in the table
    tableBody.addEventListener('click', function (e) {
        const img = e.target.closest('.lightbox-img');
        if (img && lightbox) {
            lightboxImg.src = img.dataset.full || img.src;
            lightboxCap.textContent = img.alt || '';
            lightbox.style.display = '';
        }
    });

    function closeLightbox() {
        if (lightbox) {
            lightbox.style.display = 'none';
            lightboxImg.src = '';
        }
    }

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox && lightbox.style.display !== 'none') closeLightbox();
    });

})();
