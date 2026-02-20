/**
 * CSV Import Page Logic
 * Drag & drop + file picker + AJAX upload with progress
 */
(function () {
    'use strict';

    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('csvFileInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameEl = document.getElementById('fileName');
    const clearFileBtn = document.getElementById('clearFileBtn');
    const importBtn = document.getElementById('importBtn');
    const progressDiv = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const resultsDiv = document.getElementById('importResults');

    if (!uploadArea) return;

    let selectedFile = null;

    // ─── Click to browse ───
    uploadArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) setFile(fileInput.files[0]);
    });

    // ─── Drag & Drop ───
    uploadArea.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
    });

    // ─── Clear file ───
    clearFileBtn.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        fileInfo.style.display = 'none';
        fileInfo.classList.add('hidden');
        uploadArea.style.display = '';
        importBtn.disabled = true;
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
    });

    // ─── Set file ───
    function setFile(file) {
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showResult('error', 'Please select a .csv file.');
            return;
        }
        selectedFile = file;
        fileNameEl.textContent = `📄 ${file.name} (${formatSize(file.size)})`;
        fileInfo.style.display = '';
        fileInfo.classList.remove('hidden');
        uploadArea.style.display = 'none';
        importBtn.disabled = false;
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
    }

    // ─── Import ───
    importBtn.addEventListener('click', () => {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('csv_file', selectedFile);

        importBtn.disabled = true;
        progressDiv.classList.remove('hidden');
        progressBar.style.width = '30%';
        resultsDiv.classList.add('hidden');

        fetch('/api/import.php', {
            method: 'POST',
            body: formData,
        })
            .then(res => {
                progressBar.style.width = '80%';
                return res.json();
            })
            .then(data => {
                progressBar.style.width = '100%';
                setTimeout(() => {
                    progressDiv.classList.add('hidden');
                    progressBar.style.width = '0%';

                    if (data.error) {
                        showResult('error', data.error);
                    } else {
                        showImportResults(data);
                    }
                    importBtn.disabled = false;
                }, 400);
            })
            .catch(err => {
                progressDiv.classList.add('hidden');
                progressBar.style.width = '0%';
                showResult('error', 'Upload failed: ' + err.message);
                importBtn.disabled = false;
            });
    });

    // ─── Show results ───
    function showImportResults(data) {
        resultsDiv.classList.remove('hidden');
        resultsDiv.innerHTML = `
            <div class="alert alert-success">✅ Import completed successfully!</div>
            <div class="import-stat">
                <span class="stat-label">📥 Inserted</span>
                <span class="stat-value inserted">${data.inserted}</span>
            </div>
            <div class="import-stat">
                <span class="stat-label">🔄 Updated</span>
                <span class="stat-value updated">${data.updated}</span>
            </div>
            <div class="import-stat">
                <span class="stat-label">❌ Errors</span>
                <span class="stat-value errors">${data.errors}</span>
            </div>
            ${data.messages && data.messages.length
                ? `<div class="mt-2" style="font-size:0.82rem; color:var(--text-muted);">
                      ${data.messages.map(m => `<div>⚠ ${escHtml(m)}</div>`).join('')}
                   </div>`
                : ''}
        `;
    }

    function showResult(type, msg) {
        resultsDiv.classList.remove('hidden');
        const cls = type === 'error' ? 'alert-danger' : 'alert-success';
        resultsDiv.innerHTML = `<div class="alert ${cls}">${escHtml(msg)}</div>`;
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

})();
