/**
 * Profile & User Management — JavaScript
 */
(function () {
    'use strict';

    const BASE = '';

    // ─── My Profile Form ───
    const profileForm = document.getElementById('profileForm');
    const profileAlert = document.getElementById('profileAlert');

    profileForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const payload = {
            action: 'update',
            id: Number(document.getElementById('profileId').value),
            full_name: document.getElementById('profileFullName').value.trim(),
            username: document.getElementById('profileUsername').value.trim(),
            mobile: document.getElementById('profileMobile').value.trim(),
        };

        apiCall(payload).then(data => {
            if (data.success) {
                showAlert(profileAlert, 'Profile updated successfully!', 'success');
            } else {
                showAlert(profileAlert, data.error || 'Update failed.', 'danger');
            }
        });
    });

    // ─── Change Password Form ───
    const passwordForm = document.getElementById('passwordForm');
    const passwordAlert = document.getElementById('passwordAlert');

    passwordForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const currentPw = document.getElementById('currentPassword').value;
        const newPw = document.getElementById('newPassword').value;
        const confirmPw = document.getElementById('confirmPassword').value;

        if (newPw !== confirmPw) {
            showAlert(passwordAlert, 'New passwords do not match.', 'danger');
            return;
        }

        if (newPw.length < 4) {
            showAlert(passwordAlert, 'Password must be at least 4 characters.', 'danger');
            return;
        }

        const payload = {
            action: 'change_password',
            id: Number(document.getElementById('profileId').value),
            current_password: currentPw,
            new_password: newPw,
        };

        apiCall(payload).then(data => {
            if (data.success) {
                showAlert(passwordAlert, 'Password changed successfully!', 'success');
                passwordForm.reset();
            } else {
                showAlert(passwordAlert, data.error || 'Password change failed.', 'danger');
            }
        });
    });

    // ─── User Modal ───
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const userModalTitle = document.getElementById('userModalTitle');
    const userSubmitBtn = document.getElementById('userSubmitBtn');
    const userAlert = document.getElementById('userAlert');
    const userPasswordGrp = document.getElementById('userPasswordGroup');
    const addUserBtn = document.getElementById('addUserBtn');
    const closeUserModal = document.getElementById('closeUserModal');
    const cancelUserModal = document.getElementById('cancelUserModal');

    let editingUserId = null;

    addUserBtn.addEventListener('click', () => {
        editingUserId = null;
        userModalTitle.textContent = '➕ Create User';
        userSubmitBtn.textContent = '➕ Create';
        userForm.reset();
        userPasswordGrp.style.display = '';
        document.getElementById('userPassword').required = true;
        userAlert.style.display = 'none';
        userModal.style.display = '';
    });

    // Edit user buttons (delegated)
    document.getElementById('usersTableBody').addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-user-btn');
        const delBtn = e.target.closest('.delete-user-btn');

        if (editBtn) {
            editingUserId = editBtn.dataset.id;
            userModalTitle.textContent = '✏️ Edit User';
            userSubmitBtn.textContent = '💾 Save';
            document.getElementById('userId').value = editBtn.dataset.id;
            document.getElementById('userFullName').value = editBtn.dataset.fullname;
            document.getElementById('userUsername').value = editBtn.dataset.username;
            document.getElementById('userMobile').value = editBtn.dataset.mobile || '';
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            userPasswordGrp.style.display = 'none';
            userAlert.style.display = 'none';
            userModal.style.display = '';
        }

        if (delBtn) {
            pendingDeleteId = delBtn.dataset.id;
            document.getElementById('deleteUserMsg').textContent =
                `Delete user "${delBtn.dataset.name}"? This cannot be undone.`;
            document.getElementById('deleteUserModal').style.display = '';
        }
    });

    function closeModal() {
        userModal.style.display = 'none';
        userAlert.style.display = 'none';
    }
    closeUserModal.addEventListener('click', closeModal);
    cancelUserModal.addEventListener('click', closeModal);
    userModal.addEventListener('click', (e) => { if (e.target === userModal) closeModal(); });

    userForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (editingUserId) {
            // Update existing user
            const payload = {
                action: 'update',
                id: Number(editingUserId),
                full_name: document.getElementById('userFullName').value.trim(),
                username: document.getElementById('userUsername').value.trim(),
                mobile: document.getElementById('userMobile').value.trim(),
            };
            apiCall(payload).then(data => {
                if (data.success) {
                    closeModal();
                    location.reload();
                } else {
                    userAlert.textContent = data.error || 'Update failed.';
                    userAlert.style.display = '';
                }
            });
        } else {
            // Create new user
            const pw = document.getElementById('userPassword').value;
            if (!pw || pw.length < 4) {
                userAlert.textContent = 'Password must be at least 4 characters.';
                userAlert.style.display = '';
                return;
            }
            const payload = {
                action: 'create',
                full_name: document.getElementById('userFullName').value.trim(),
                username: document.getElementById('userUsername').value.trim(),
                mobile: document.getElementById('userMobile').value.trim(),
                password: pw,
            };
            apiCall(payload).then(data => {
                if (data.success) {
                    closeModal();
                    location.reload();
                } else {
                    userAlert.textContent = data.error || 'Create failed.';
                    userAlert.style.display = '';
                }
            });
        }
    });

    // ─── Delete User Confirm ───
    let pendingDeleteId = null;
    const deleteUserModal = document.getElementById('deleteUserModal');

    document.getElementById('confirmDeleteUser').addEventListener('click', () => {
        if (!pendingDeleteId) return;
        apiCall({ action: 'delete', id: Number(pendingDeleteId) }).then(data => {
            deleteUserModal.style.display = 'none';
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Delete failed.');
            }
            pendingDeleteId = null;
        });
    });

    document.getElementById('cancelDeleteUser').addEventListener('click', () => {
        deleteUserModal.style.display = 'none';
        pendingDeleteId = null;
    });

    deleteUserModal.addEventListener('click', (e) => {
        if (e.target === deleteUserModal) {
            deleteUserModal.style.display = 'none';
            pendingDeleteId = null;
        }
    });

    // ─── API Helper ───
    function apiCall(payload) {
        return fetch(`${BASE}/api/users.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .catch(err => ({ error: err.message }));
    }

    // ─── Alert Helper ───
    function showAlert(el, message, type) {
        el.textContent = message;
        el.className = `alert alert-${type}`;
        el.style.display = '';
        setTimeout(() => { el.style.display = 'none'; }, 5000);
    }

})();
