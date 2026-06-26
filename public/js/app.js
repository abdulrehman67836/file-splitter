// Excel / CSV File Splitter Core JS Application
// (Sirf split request ka transport JSON se form-urlencoded kiya gaya hai — yehi bug tha.)

document.addEventListener('DOMContentLoaded', () => {
    let currentJobUuid = null;
    let selectedFile = null;
    let pollingInterval = null;

    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');
    const headerCheckbox = document.getElementById('header-checkbox');
    const uploadSubmitBtn = document.getElementById('upload-submit-btn');

    const configFilename = document.getElementById('config-filename');
    const configTotalRows = document.getElementById('config-total-rows');
    const rec50 = document.getElementById('rec-50-files');
    const rec100 = document.getElementById('rec-100-files');
    const rec200 = document.getElementById('rec-200-files');
    const recommendationCards = document.querySelectorAll('.recommendation-card');
    const chunkSizeInput = document.getElementById('chunk-size-input');
    const chunkSizeError = document.getElementById('chunk-size-error');
    const configBackBtn = document.getElementById('config-back-btn');
    const splitExecuteBtn = document.getElementById('split-execute-btn');

    const progressFill = document.getElementById('progress-fill');
    const processLogConsole = document.getElementById('process-log-console');
    const processTitle = document.getElementById('process-title');
    const processSubtitle = document.getElementById('process-subtitle');

    const resTotalFiles = document.getElementById('result-total-files');
    const resChunkSize = document.getElementById('result-chunk-size');
    const resDownloadBtn = document.getElementById('result-download-btn');
    const resRestartBtn = document.getElementById('result-restart-btn');

    function showState(stateName) {
        document.querySelectorAll('.wizard-state').forEach(el => el.classList.remove('active'));
        document.getElementById(`state-${stateName}`).classList.add('active');
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        let icon = '';
        if (type === 'success') {
            icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
        } else if (type === 'error') {
            icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
        } else {
            icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="12" x2="12" y2="16"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
        }
        toast.innerHTML = `<span class="toast-icon">${icon}</span><span style="font-weight: 500;">${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s reverse cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards';
            toast.addEventListener('animationend', () => toast.remove());
        }, 4000);
    }

    function addLogLine(message, type = 'info') {
        const line = document.createElement('div');
        line.className = `log-line ${type}`;
        line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        processLogConsole.appendChild(line);
        processLogConsole.scrollTop = processLogConsole.scrollHeight;
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    browseBtn.addEventListener('click', (e) => { e.preventDefault(); fileInput.click(); });
    fileInput.addEventListener('change', handleFileSelection);

    dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelection();
        }
    });

    function handleFileSelection() {
        const file = fileInput.files[0];
        if (!file) return;
        const name = file.name;
        const ext = name.split('.').pop().toLowerCase();
        if (ext === 'xls') {
            showToast("Legacy Excel format (.xls) is not supported for streaming. Please save as modern Excel (.xlsx).", "error");
            resetFileSelect(); return;
        }
        if (ext !== 'xlsx' && ext !== 'csv') {
            showToast("Unsupported file type. Please upload a .xlsx or .csv spreadsheet.", "error");
            resetFileSelect(); return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showToast("File size exceeds the 10 MB limit.", "error");
            resetFileSelect(); return;
        }
        selectedFile = file;
        const innerText = dropzone.querySelector('h4');
        const descText = dropzone.querySelector('p');
        innerText.innerHTML = `Ready to upload: <span style="color: var(--accent-cyan); font-weight:700;">${name}</span>`;
        descText.textContent = `Size: ${formatBytes(file.size)}`;
        uploadSubmitBtn.removeAttribute('disabled');
        showToast(`Spreadsheet loaded: ${name}`, "info");
    }

    function resetFileSelect() {
        selectedFile = null;
        fileInput.value = '';
        const innerText = dropzone.querySelector('h4');
        const descText = dropzone.querySelector('p');
        innerText.textContent = "Drag and drop spreadsheet here";
        descText.innerHTML = "Supports <strong>.xlsx</strong> and <strong>.csv</strong> files (Max 10 MB)";
        uploadSubmitBtn.setAttribute('disabled', 'true');
    }

    uploadSubmitBtn.addEventListener('click', () => {
        if (!selectedFile) return;
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('has_header', headerCheckbox.checked ? '1' : '0');
        uploadSubmitBtn.setAttribute('disabled', 'true');
        uploadSubmitBtn.textContent = "Uploading & Analyzing...";

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/uploads', true);
        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                uploadSubmitBtn.textContent = `Uploading: ${percent}%`;
            }
        };
        xhr.onload = () => {
            uploadSubmitBtn.textContent = "Upload and Analyze File";
            let response = {};
            try { response = JSON.parse(xhr.responseText); }
            catch (err) { response = { error: "An unexpected server crash occurred." }; }
            if (xhr.status === 200) {
                currentJobUuid = response.uuid;
                configFilename.textContent = response.filename;
                configTotalRows.textContent = response.total_rows.toLocaleString();
                rec50.textContent = `${response.recommendations['50']} file(s)`;
                rec100.textContent = `${response.recommendations['100']} file(s)`;
                rec200.textContent = `${response.recommendations['200']} file(s)`;
                showToast("File uploaded and analyzed successfully.", "success");
                showState('config');
            } else {
                showToast(response.error || "Failed to process spreadsheet file.", "error");
                uploadSubmitBtn.removeAttribute('disabled');
            }
        };
        xhr.onerror = () => {
            showToast("Network error occurred during spreadsheet upload.", "error");
            uploadSubmitBtn.removeAttribute('disabled');
            uploadSubmitBtn.textContent = "Upload and Analyze File";
        };
        xhr.send(formData);
    });

    recommendationCards.forEach(card => {
        card.addEventListener('click', () => {
            recommendationCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const chunkVal = card.getAttribute('data-chunk');
            chunkSizeInput.value = chunkVal;
            chunkSizeError.style.display = 'none';
        });
    });

    chunkSizeInput.addEventListener('input', () => {
        recommendationCards.forEach(c => c.classList.remove('selected'));
        chunkSizeError.style.display = 'none';
    });

    configBackBtn.addEventListener('click', () => { resetFileSelect(); showState('upload'); });

    splitExecuteBtn.addEventListener('click', () => {
        const chunkSizeVal = chunkSizeInput.value.trim();
        if (!chunkSizeVal || isNaN(chunkSizeVal)) {
            showInputError("Chunk size must be a numeric integer.");
            return;
        }
        const chunkSize = parseInt(chunkSizeVal);
        if (chunkSize <= 0) {
            showInputError("Chunk size must be greater than zero.");
            return;
        }
        chunkSizeError.style.display = 'none';
        processLogConsole.innerHTML = '';
        progressFill.style.width = '0%';
        processTitle.textContent = "Queuing Split Job...";
        processSubtitle.textContent = "Your request is being submitted to the background task queue.";
        addLogLine("Contacting server to initialize split transaction...", "info");
        showState('process');

        // ===== FIX: form-urlencoded bhejein (JSON ki jagah), taake Request::input('chunk_size') ko value mile =====
        fetch(`/jobs/${currentJobUuid}/split`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({ chunk_size: chunkSize })
        })
        // ============================================================================================================
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                showToast(data.error, "error");
                showState('config');
            } else {
                addLogLine("Job queued successfully in background queue.", "success");
                addLogLine("Spawning background worker daemon process...", "info");
                startPollingStatus();
            }
        })
        .catch(err => {
            showToast("Network request failed.", "error");
            showState('config');
        });
    });

    function showInputError(msg) {
        chunkSizeError.textContent = msg;
        chunkSizeError.style.display = 'block';
        chunkSizeInput.focus();
    }

    function startPollingStatus() {
        if (pollingInterval) clearInterval(pollingInterval);
        progressFill.style.width = '10%';
        let pollAttempts = 0;
        let lastStatus = '';
        pollingInterval = setInterval(() => {
            pollAttempts++;
            fetch(`/jobs/${currentJobUuid}/status`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    addLogLine(`Error polling status: ${data.error}`, "error");
                    clearInterval(pollingInterval);
                    return;
                }
                const status = data.status;
                if (status !== lastStatus) {
                    addLogLine(`Job status transitioned: ${lastStatus || 'pending'} -> ${status}`, "info");
                    lastStatus = status;
                }
                if (status === 'pending') {
                    progressFill.style.width = '20%';
                    processTitle.textContent = "Waiting for background worker...";
                } else if (status === 'processing') {
                    progressFill.style.width = '60%';
                    processTitle.textContent = "Streaming spreadsheet records...";
                    processSubtitle.textContent = `Processing records line-by-line using OpenSpout.`;
                    if (pollAttempts % 3 === 0) {
                        addLogLine("Reading source spreadsheet and writing split output chunks...", "info");
                    }
                } else if (status === 'completed') {
                    progressFill.style.width = '100%';
                    addLogLine("All data chunks split successfully.", "success");
                    addLogLine("Packaging chunks into single ZIP archive...", "info");
                    addLogLine(`Complete split completed in background worker.`, "success");
                    clearInterval(pollingInterval);
                    setTimeout(() => {
                        resTotalFiles.textContent = `${data.total_output_files} files`;
                        resChunkSize.textContent = `${data.chunk_size} rows per file`;
                        resDownloadBtn.href = data.download_url;
                        showToast("Spreadsheet split and ZIP archive created successfully!", "success");
                        showState('result');
                    }, 800);
                } else if (status === 'failed') {
                    progressFill.style.width = '100%';
                    progressFill.style.backgroundColor = 'var(--accent-red)';
                    addLogLine(`Split failed. Reason: ${data.error_message}`, "error");
                    clearInterval(pollingInterval);
                    showToast(`Convert Job Failed: ${data.error_message}`, "error");
                    processTitle.textContent = "Processing Failed";
                    processSubtitle.textContent = data.error_message;
                    const retryBtn = document.createElement('button');
                    retryBtn.className = "btn btn-secondary";
                    retryBtn.style.marginTop = "20px";
                    retryBtn.textContent = "Back to Configuration";
                    retryBtn.onclick = () => showState('config');
                    processLogConsole.parentElement.appendChild(retryBtn);
                }
            })
            .catch(err => { addLogLine("Polling status request network error.", "error"); });
        }, 1200);
    }

    resRestartBtn.addEventListener('click', () => {
        resetFileSelect();
        chunkSizeInput.value = '';
        recommendationCards.forEach(c => c.classList.remove('selected'));
        const extraBtn = processLogConsole.parentElement.querySelector('button.btn-secondary');
        if (extraBtn) extraBtn.remove();
        window.location.reload();
    });
});