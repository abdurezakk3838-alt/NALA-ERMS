// assets/js/script.js
(function() {
    'use strict';

    // ─── TOAST ────────────────────────────────────────────────
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastSubMessage = document.getElementById('toastSubMessage');

    window.showToast = function(msg, subMsg = '', type = 'success') {
        toastMessage.textContent = msg;
        toastSubMessage.textContent = subMsg;
        toast.className = 'toast show';
        if (type === 'error') {
            toast.querySelector('i').className = 'fas fa-exclamation-circle text-red-400';
        } else {
            toast.querySelector('i').className = 'fas fa-check-circle text-green-400';
        }
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    };

    // ─── THEME ─────────────────────────────────────────────────
    let darkMode = localStorage.getItem('darkMode') === 'true';
    if (darkMode) {
        document.body.classList.add('dark-mode');
        document.getElementById('themeToggleIcon')?.classList.replace('fa-moon', 'fa-sun');
    }

    document.getElementById('themeToggleBtn')?.addEventListener('click', function() {
        darkMode = !darkMode;
        document.body.classList.toggle('dark-mode', darkMode);
        localStorage.setItem('darkMode', darkMode);
        const icon = document.getElementById('themeToggleIcon');
        if (icon) {
            icon.className = darkMode ? 'fas fa-sun' : 'fas fa-moon';
        }
    });

    // ─── SIDEBAR ──────────────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');

    menuToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    // Close sidebar on click outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });

    // ─── MODAL HELPERS ─────────────────────────────────────────
    window.openModal = function(id) {
        document.getElementById(id).classList.add('active');
    };

    window.closeModal = function(id) {
        document.getElementById(id).classList.remove('active');
    };

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
        }
    });

    // ─── RECEIVER EMAIL BADGE ─────────────────────────────────
    const receiverSelect = document.getElementById('receiverSelect');
    const receiverEmailBadge = document.getElementById('receiverEmailBadge');

    if (receiverSelect && receiverEmailBadge) {
        receiverSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const email = opt.getAttribute('data-email') || '';
            receiverEmailBadge.innerHTML = `<i class="fas fa-envelope mr-1"></i> Email: ${email}`;
        });
    }

    // ─── FILE DROP ZONES ──────────────────────────────────────
    function setupDropZone(dropZoneId, fileInputId, contentId, previewId, statusId) {
        const dropZone = document.getElementById(dropZoneId);
        const fileInput = document.getElementById(fileInputId);
        const content = document.getElementById(contentId);
        const preview = document.getElementById(previewId);
        const status = document.getElementById(statusId);

        if (!dropZone || !fileInput) return;

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFileSelection(e.dataTransfer.files[0], content, preview, status, fileInput);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                handleFileSelection(fileInput.files[0], content, preview, status, fileInput);
            }
        });
    }

    function handleFileSelection(file, content, preview, status, fileInput) {
        const reader = new FileReader();
        reader.onload = function(e) {
            content.style.display = 'none';
            preview.style.display = 'flex';
            preview.innerHTML = `
                <i class="fas fa-file-pdf text-red-500 text-2xl"></i>
                <span class="file-name">${file.name}</span>
                <span class="text-xs text-gray-400">(${(file.size / 1024).toFixed(1)} KB)</span>
                <button type="button" class="text-xs text-red-500 hover:text-red-700 ml-2" onclick="removeFile('${preview.id}', '${content.id}', '${status.id}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            status.textContent = 'Document attached!';
        };
        reader.readAsDataURL(file);
    }

    window.removeFile = function(previewId, contentId, statusId) {
        document.getElementById(previewId).style.display = 'none';
        document.getElementById(contentId).style.display = 'block';
        document.getElementById(statusId).textContent = '';
    };

    // Setup drop zones
    document.addEventListener('DOMContentLoaded', function() {
        setupDropZone('dropZone', 'fileInput', 'dropZoneContent', 'filePreview', 'uploadStatus');
        setupDropZone('internalDropZone', 'internalFileInput', 'internalDropZoneContent', 'internalFilePreview', 'internalUploadStatus');
        setupDropZone('outDropZone', 'outFileInput', 'outDropZoneContent', 'outFilePreview', 'outUploadStatus');
    });

    // ─── VIEW LETTER (AJAX) ───────────────────────────────────
    window.viewLetter = function(ref, type) {
        fetch(`api/get_letter.php?ref=${encodeURIComponent(ref)}&type=${encodeURIComponent(type)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateViewModal(data.letter, type);
                    document.getElementById('viewModal').classList.add('active');
                } else {
                    showToast('Error', data.message || 'Letter not found.', 'error');
                }
            })
            .catch(err => {
                showToast('Error', 'Failed to load letter details.', 'error');
                console.error(err);
            });
    };

    function populateViewModal(letter, type) {
        document.getElementById('viewRef').textContent = letter.ref_number || '';
        document.getElementById('viewTitle').textContent = letter.title || '';
        document.getElementById('viewSender').textContent = letter.sender_name || '';
        document.getElementById('viewReceiver').textContent = letter.receiver_name || '';
        document.getElementById('viewReceiverEmail').textContent = letter.receiver_email || 'N/A';
        document.getElementById('viewStatus').innerHTML = getStatusPillHTML(letter.status || 'pending');
        document.getElementById('viewDate').textContent = letter.received_date || letter.date || '';
        document.getElementById('viewClassification').textContent = letter.classification || '1.01 General Administration';
        document.getElementById('viewKeywords').textContent = letter.keywords || 'No keywords set';
        document.getElementById('viewDesc').textContent = letter.description || 'No description provided.';

        // File
        const fileWrapper = document.getElementById('viewFileWrapper');
        if (letter.file_name) {
            fileWrapper.classList.remove('hidden');
            document.getElementById('viewFileName').textContent = letter.file_name;
        } else {
            fileWrapper.classList.add('hidden');
        }

        // Remarks
        const remarkWrapper = document.getElementById('viewRemarkWrapper');
        const remarkField = document.getElementById('viewRemark');
        if (letter.receiver_remark || letter.manager_remark) {
            remarkWrapper.classList.remove('hidden');
            remarkField.textContent = letter.receiver_remark || letter.manager_remark || '';
        } else {
            remarkWrapper.classList.add('hidden');
        }

        // Signature
        const sigWrapper = document.getElementById('viewSigWrapper');
        if (letter.digital_signature && (letter.status === 'approved' || letter.status === 'signed')) {
            sigWrapper.classList.remove('hidden');
            document.getElementById('viewSigImg').src = letter.digital_signature;
        } else {
            sigWrapper.classList.add('hidden');
        }
    }

    function getStatusPillHTML(status) {
        const classes = {
            'pending': 'pending',
            'pending_approval': 'pending_approval',
            'approved': 'approved',
            'rejected': 'rejected',
            'in-process': 'in-process',
            'archived': 'archived',
            'dispatched': 'dispatched'
        };
        const cls = classes[status] || 'pending';
        return `<span class="status-pill ${cls}">${status.replace('_', ' ')}</span>`;
    }

    // ─── INLINE SIGNATURE CANVAS ──────────────────────────────
    let signatureData = null;

    function setupSignatureCanvas(canvasId, clearBtnId, previewId, previewImgId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0, lastY = 0;

        function resizeCanvas() {
            const rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width - 10 || 500;
            canvas.height = 110;
            ctx.strokeStyle = '#0a1e3a';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            if (signatureData) {
                const img = new Image();
                img.onload = function() {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                };
                img.src = signatureData;
            }
        }

        resizeCanvas();

        function startDraw(e) {
            e.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const x = (clientX - rect.left) * (canvas.width / rect.width);
            const y = (clientY - rect.top) * (canvas.height / rect.height);
            isDrawing = true;
            lastX = x;
            lastY = y;
            ctx.beginPath();
            ctx.moveTo(x, y);
            canvas.classList.add('active');
        }

        function draw(e) {
            e.preventDefault();
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const x = (clientX - rect.left) * (canvas.width / rect.width);
            const y = (clientY - rect.top) * (canvas.height / rect.height);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x;
            lastY = y;
        }

        function endDraw(e) {
            e.preventDefault();
            isDrawing = false;
            canvas.classList.remove('active');
            signatureData = canvas.toDataURL();
            updateSignaturePreview(previewId, previewImgId);
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', endDraw, { passive: false });

        // Clear button
        const clearBtn = document.getElementById(clearBtnId);
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                signatureData = null;
                updateSignaturePreview(previewId, previewImgId);
            });
        }

        window.addEventListener('resize', resizeCanvas);

        return {
            getSignature: function() { return signatureData || canvas.toDataURL(); },
            clear: function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                signatureData = null;
                updateSignaturePreview(previewId, previewImgId);
            }
        };
    }

    function updateSignaturePreview(previewId, previewImgId) {
        const preview = document.getElementById(previewId);
        const previewImg = document.getElementById(previewImgId);
        if (preview && previewImg) {
            if (signatureData && signatureData !== 'data:image/png;base64,') {
                preview.style.display = 'block';
                previewImg.src = signatureData;
            } else {
                preview.style.display = 'none';
            }
        }
    }

    // ─── INCOMING FORM SUBMIT (AJAX) ──────────────────────────
    document.getElementById('incomingForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('actions/add_incoming.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message || 'Letter saved successfully.');
                document.getElementById('registerModal').classList.remove('active');
                location.reload();
            } else {
                showToast('Error', data.message || 'Failed to save letter.', 'error');
            }
        })
        .catch(err => {
            showToast('Error', 'An error occurred. Please try again.', 'error');
            console.error(err);
        });
    });

    // ─── CLOSE MODAL BUTTONS ──────────────────────────────────
    document.querySelectorAll('.modal-overlay .close-btn, .modal-overlay .btn-outline[data-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    // ─── DISPATCH OUTGOING ────────────────────────────────────
    window.dispatchOutgoing = function(ref) {
        if (confirm('Are you sure you want to dispatch this letter?')) {
            fetch('actions/dispatch_outgoing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ref=' + encodeURIComponent(ref)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', data.message || 'Letter dispatched.');
                    location.reload();
                } else {
                    showToast('Error', data.message || 'Failed to dispatch.', 'error');
                }
            })
            .catch(err => {
                showToast('Error', 'An error occurred.', 'error');
                console.error(err);
            });
        }
    };

    // ─── GENERATE REPORT ──────────────────────────────────────
    window.generateReport = function(type) {
        fetch('actions/generate_report.php?type=' + encodeURIComponent(type))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const preview = document.getElementById('reportPreview');
                    if (preview) {
                        preview.innerHTML = data.html;
                    }
                    showToast('Report Generated', 'Report is ready.', 'success');
                } else {
                    showToast('Error', data.message || 'Failed to generate report.', 'error');
                }
            })
            .catch(err => {
                showToast('Error', 'Failed to generate report.', 'error');
                console.error(err);
            });
    };

    console.log('📚 ILARMS v2.0.0 (PHP Version) loaded successfully.');
    console.log('💡 Demo users: head_office / head123, admin / admin123');
})();
