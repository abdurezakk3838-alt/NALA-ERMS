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
    window.updateReceiverBadge = function(selectEl, badgeId) {
        const badge = document.getElementById(badgeId);
        if (!selectEl || !badge) return;
        const opt = selectEl.options[selectEl.selectedIndex];
        const email = opt ? (opt.getAttribute('data-email') || '') : '';
        badge.innerHTML = `<i class="fas fa-envelope mr-1"></i> Email: ${email}`;
    };

    document.querySelectorAll('select[data-email-badge]').forEach(select => {
        select.addEventListener('change', function() {
            window.updateReceiverBadge(this, this.getAttribute('data-email-badge'));
        });
    });

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

        const languageSelector = document.getElementById('languageSelector');
        languageSelector?.addEventListener('change', async function() {
            const selectedLanguage = this.value;
            this.disabled = true;
            try {
                const response = await fetch('actions/set_language.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ language: selectedLanguage }).toString()
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Language could not be changed.');
                // Reload so PHP renders the selected language/session everywhere.
                window.location.assign(window.location.href);
            } catch (error) {
                this.disabled = false;
                showToast('Error', error.message || 'Language could not be changed.', 'error');
            }
        });
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

    window.openLetterQr = function(info) {
        if (!info || typeof QRCode === 'undefined') {
            showToast('QR unavailable', 'The QR code library could not be loaded.', 'error');
            return;
        }
        const qr = document.getElementById('largeQrCode');
        qr.innerHTML = '';
        const qrText = `Reference: ${info.ref}\nTitle: ${info.title}\nSender: ${info.sender}\nReceiver: ${info.receiver}\nStatus: ${info.status}`;
        new QRCode(qr, { text: qrText, width: 240, height: 240, colorDark: '#0a1e3a', colorLight: '#ffffff' });
        document.getElementById('qrInfoRef').textContent = info.ref || '-';
        document.getElementById('qrInfoTitle').textContent = info.title || '-';
        document.getElementById('qrInfoSender').textContent = info.sender || '-';
        document.getElementById('qrInfoReceiver').textContent = info.receiver || '-';
        document.getElementById('qrInfoStatus').textContent = info.status || '-';
        document.getElementById('qrLetterModal')?.classList.add('active');
    };

    window.viewArchivedLetter = function(id) {
        fetch(`api/get_archived_letter.php?id=${encodeURIComponent(id)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Archived document not found.');
                populateViewModal(data.letter, data.type);
                document.getElementById('viewModal')?.classList.add('active');
            })
            .catch(error => showToast('Error', error.message || 'Failed to load archived document.', 'error'));
    };

    window.deleteArchivedLetter = function(id, ref) {
        if (!window.confirm(`Permanently delete the archived copy of ${ref}? The original letter outside the archive will not be changed.`)) return;

        fetch('actions/delete_archive_record.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id }).toString()
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Archived document could not be deleted.');
                showToast('Archive copy deleted', data.message || `Archived copy ${ref} was deleted.`);
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(error => showToast('Error', error.message || 'Archived document could not be deleted.', 'error'));
    };

    function populateViewModal(letter, type) {
        document.getElementById('viewRef').textContent = letter.ref_number || '';
        document.getElementById('viewTitle').textContent = letter.title || '';
        document.getElementById('viewSender').textContent = letter.sender_name || '';
        document.getElementById('viewReceiver').textContent = letter.receiver_name || '';
        document.getElementById('viewReceiverEmail').textContent = letter.receiver_email || 'N/A';
        document.getElementById('viewClassification').textContent = letter.classification || '1.01 General Administration';
        document.getElementById('viewSecurityLevel').textContent = letter.security_level || 'Official';
        document.getElementById('viewStatus').innerHTML = getStatusPillHTML(letter.status || 'pending');
        document.getElementById('viewDate').textContent = letter.received_date || letter.date || '';
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
        if (letter.digital_signature && ['approved', 'signed', 'dispatched'].includes(letter.status)) {
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

    // ─── FORM VALIDATION ────────────────────────────────
    document.getElementById('incomingForm')?.addEventListener('submit', function(e) {
        const refNumber = document.getElementById('refNumber')?.value?.trim();
        if (!refNumber) {
            e.preventDefault();
            showToast('Reference required', 'Please enter a letter reference number.', 'error');
            return;
        }
    });

    document.getElementById('outgoingForm')?.addEventListener('submit', function(e) {
        const refNumber = document.getElementById('outgoingRefNumber')?.value?.trim();
        if (!refNumber) {
            e.preventDefault();
            showToast('Reference required', 'Please enter an outgoing reference number.', 'error');
            return;
        }
    });

    document.getElementById('internalForm')?.addEventListener('submit', function(e) {
        const refNumber = document.getElementById('internalRefNumber')?.value?.trim();
        if (!refNumber) {
            e.preventDefault();
            showToast('Reference required', 'Please enter an internal memo reference number.', 'error');
            return;
        }
    });

    // ─── REVIEW MODALS ───────────────────────────────────────
    function populateReviewModal(letter, type) {
        if (!letter) return;
        document.getElementById('reviewRef').value = letter.ref_number || '';
        document.getElementById('reviewType').value = type;
        document.getElementById('reviewRemark').value = letter.receiver_remark || letter.manager_remark || '';
        document.getElementById('reviewSignature').value = '';
        document.getElementById('systemSignaturePreview')?.classList.add('hidden');
        const reviewCanvas = document.getElementById('reviewSignatureCanvas');
        if (reviewCanvas) reviewCanvas.getContext('2d').clearRect(0, 0, reviewCanvas.width, reviewCanvas.height);
        const signatureButton = document.getElementById('generateSystemSignatureBtn');
        if (signatureButton) {
            signatureButton.innerHTML = '<i class="fas fa-signature mr-1"></i> Approved Signature';
            signatureButton.classList.remove('bg-emerald-700');
            delete signatureButton.dataset.signatureGenerated;
        }

        const summary = document.getElementById('reviewSummary');
        summary.innerHTML = `
            <div><span class="text-slate-500 block">Reference</span><span class="font-semibold text-slate-800">${letter.ref_number || '-'}</span></div>
            <div><span class="text-slate-500 block">Title</span><span class="font-semibold text-slate-800">${letter.title || '-'}</span></div>
            <div><span class="text-slate-500 block">Sender</span><span class="font-semibold text-slate-800">${letter.sender_name || '-'}</span></div>
            <div><span class="text-slate-500 block">Receiver</span><span class="font-semibold text-slate-800">${letter.receiver_name || '-'}</span></div>
            <div><span class="text-slate-500 block">Email</span><span class="font-semibold text-slate-800">${letter.receiver_email || '-'}</span></div>
            <div><span class="text-slate-500 block">Date</span><span class="font-semibold text-slate-800">${letter.received_date || letter.date || '-'}</span></div>
            <div><span class="text-slate-500 block">Classification</span><span class="font-semibold text-slate-800">${letter.classification || '1.01 General Administration'}</span></div>
            <div><span class="text-slate-500 block">Keywords</span><span class="font-semibold text-slate-800">${letter.keywords || '-'}</span></div>
            <div class="md:col-span-2"><span class="text-slate-500 block">Description</span><span class="font-semibold text-slate-800">${letter.description || 'No description provided.'}</span></div>
            <div class="md:col-span-2"><span class="text-slate-500 block">Status</span><span class="font-semibold text-slate-800">${letter.status || 'pending'}</span></div>
        `;

        const attachmentSection = document.getElementById('reviewAttachmentSection');
        const attachmentLink = document.getElementById('reviewAttachmentLink');
        const attachmentMeta = document.getElementById('reviewAttachmentMeta');
        const previewFrame = document.getElementById('reviewAttachmentPreview');
        const previewImage = document.getElementById('reviewAttachmentImage');
        if (letter.file_name) {
            attachmentSection.classList.remove('hidden');
            attachmentMeta.textContent = letter.file_name;
            const ext = (letter.file_name.split('.').pop() || '').toLowerCase();
            const attachmentUrl = `api/view_attachment.php?ref=${encodeURIComponent(letter.ref_number)}&type=${encodeURIComponent(type)}`;
            if (ext === 'pdf') {
                attachmentLink.href = attachmentUrl;
                attachmentLink.textContent = 'Open PDF';
                previewFrame.src = attachmentUrl;
                previewFrame.classList.remove('hidden');
                previewImage.classList.add('hidden');
            } else if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) {
                attachmentLink.href = attachmentUrl;
                attachmentLink.textContent = 'Open image';
                previewImage.src = attachmentUrl;
                previewImage.classList.remove('hidden');
                previewFrame.classList.add('hidden');
            } else {
                attachmentLink.href = attachmentUrl;
                attachmentLink.download = letter.file_name;
                attachmentLink.textContent = 'Download attachment';
                previewFrame.classList.add('hidden');
                previewImage.classList.add('hidden');
            }
        } else {
            attachmentSection.classList.add('hidden');
            previewFrame.src = '';
            previewImage.src = '';
        }

        document.getElementById('reviewModal').classList.add('active');
    }

    window.openReviewIncoming = function(ref) {
        fetch('api/get_letter.php?ref=' + encodeURIComponent(ref) + '&type=incoming')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateReviewModal(data.letter, 'incoming');
                } else {
                    showToast('Error', data.message || 'Letter not found.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error', 'Unable to load letter details.', 'error');
            });
    };

    window.openReviewOutgoing = function(ref) {
        fetch('api/get_letter.php?ref=' + encodeURIComponent(ref) + '&type=outgoing')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateReviewModal(data.letter, 'outgoing');
                } else {
                    showToast('Error', data.message || 'Letter not found.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error', 'Unable to load letter details.', 'error');
            });
    };

    window.openReviewInternal = function(ref) {
        fetch('api/get_letter.php?ref=' + encodeURIComponent(ref) + '&type=internal')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateReviewModal(data.letter, 'internal');
                } else {
                    showToast('Error', data.message || 'Letter not found.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error', 'Unable to load letter details.', 'error');
            });
    };

    window.submitReview = function(action) {
        const ref = document.getElementById('reviewRef').value;
        const type = document.getElementById('reviewType').value;
        const remark = document.getElementById('reviewRemark').value;
        let signature = document.getElementById('reviewSignature').value;
        const approveButton = document.getElementById('reviewApproveButton');
        const rejectButton = document.getElementById('reviewRejectButton');

        if (!ref) {
            showToast('Error', 'Reference is required.', 'error');
            return;
        }

        if (action === 'reject' && !remark.trim()) {
            showToast('Rejection reason required', 'Please enter a reason before rejecting this document.', 'error');
            return;
        }

        const confirmation = action === 'approve'
            ? `Approve document ${ref}? This will complete its approval workflow.`
            : `Reject document ${ref}? It will be marked rejected and saved to the archive.`;
        if (!window.confirm(confirmation)) return;

        if (!signature && action === 'approve') {
            signature = generateSystemReviewSignature(ref);
            document.getElementById('reviewSignature').value = signature;
        }

        approveButton.disabled = true;
        rejectButton.disabled = true;
        const endpoint = action === 'approve' ? 'actions/approve_letter.php' : 'actions/reject_letter.php';
        const formData = new URLSearchParams({
            ref,
            type,
            remark,
            signature
        });

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const title = data.already_processed
                    ? 'Already completed'
                    : (action === 'approve' ? 'Document approved' : 'Document rejected');
                showToast(title, `${data.message || 'Document updated successfully.'} (Ref: ${ref})`);
                document.getElementById('reviewModal').classList.remove('active');
                setTimeout(() => location.reload(), 1800);
            } else {
                showToast('Error', data.message || 'Could not update document.', 'error');
                approveButton.disabled = false;
                rejectButton.disabled = false;
            }
        })
        .catch(err => {
            showToast('Error', 'An unexpected error occurred.', 'error');
            approveButton.disabled = false;
            rejectButton.disabled = false;
            console.error(err);
        });
    };

    function generateSystemReviewSignature(ref) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="640" height="150"><rect width="100%" height="100%" fill="white"/><path d="M25 105 C80 20 105 135 155 55 S235 80 270 45" fill="none" stroke="#0a1e3a" stroke-width="5" stroke-linecap="round"/><text x="295" y="65" font-family="cursive" font-size="28" fill="#0a1e3a">NALA ERMS</text><text x="295" y="95" font-family="Arial" font-size="14" fill="#475569">System digital signature • ${ref}</text></svg>`;
        return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));
    }

    window.generateApprovedSignature = function() {
        const button = document.getElementById('generateSystemSignatureBtn');
        if (button?.dataset.signatureGenerated === '1') return;
        const ref = document.getElementById('reviewRef')?.value || 'document';
        const field = document.getElementById('reviewSignature');
        // Always use the same deterministic professional system signature.
        // Save the generated image directly instead of capturing an image
        // from the canvas before its asynchronous load has completed.
        const signature = generateSystemReviewSignature(ref);
        if (field) field.value = signature;
        const preview = document.getElementById('systemSignaturePreview');
        if (preview) { preview.src = signature; preview.classList.remove('hidden'); }
        if (button) {
            button.dataset.signatureGenerated = '1';
            button.innerHTML = '<i class="fas fa-check mr-1"></i> Approved Signature Generated';
            button.classList.add('bg-emerald-700');
        }
        if (typeof showToast === 'function') showToast('Signature ready', 'Click Approve to save this signature.', 'success');
    };

    function isCanvasBlank(canvas) {
        const context = canvas.getContext('2d');
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        for (let i = 3; i < pixels.length; i += 4) {
            if (pixels[i] !== 0) return false;
        }
        return true;
    }

    // ─── REVIEW SIGNATURE CANVAS ──────────────────────────────
    const reviewCanvas = document.getElementById('reviewSignatureCanvas');
    if (reviewCanvas) {
        const reviewCtx = reviewCanvas.getContext('2d');
        let drawing = false;
        function resizeReviewCanvas() {
            const rect = reviewCanvas.parentElement.getBoundingClientRect();
            reviewCanvas.width = rect.width - 10 || 500;
            reviewCanvas.height = 140;
            reviewCtx.strokeStyle = '#0a1e3a';
            reviewCtx.lineWidth = 2.5;
            reviewCtx.lineCap = 'round';
            reviewCtx.lineJoin = 'round';
        }
        resizeReviewCanvas();
        const penColor = document.getElementById('signaturePenColor');
        const penSize = document.getElementById('signaturePenSize');
        const penSizeValue = document.getElementById('signaturePenSizeValue');
        const updatePen = () => {
            reviewCtx.strokeStyle = penColor?.value || '#0a1e3a';
            reviewCtx.lineWidth = Number(penSize?.value || 3);
            if (penSizeValue) penSizeValue.textContent = penSize?.value || '3';
        };
        penColor?.addEventListener('input', updatePen);
        penSize?.addEventListener('input', updatePen);
        updatePen();
        reviewCanvas.addEventListener('mousedown', (e) => {
            drawing = true;
            updatePen();
            const rect = reviewCanvas.getBoundingClientRect();
            const x = (e.clientX - rect.left) * (reviewCanvas.width / rect.width);
            const y = (e.clientY - rect.top) * (reviewCanvas.height / rect.height);
            reviewCtx.beginPath();
            reviewCtx.moveTo(x, y);
        });
        reviewCanvas.addEventListener('mousemove', (e) => {
            if (!drawing) return;
            const rect = reviewCanvas.getBoundingClientRect();
            const x = (e.clientX - rect.left) * (reviewCanvas.width / rect.width);
            const y = (e.clientY - rect.top) * (reviewCanvas.height / rect.height);
            reviewCtx.lineTo(x, y);
            reviewCtx.stroke();
        });
        reviewCanvas.addEventListener('mouseup', () => {
            drawing = false;
            document.getElementById('reviewSignature').value = reviewCanvas.toDataURL();
        });
        reviewCanvas.addEventListener('mouseleave', () => {
            drawing = false;
            document.getElementById('reviewSignature').value = reviewCanvas.toDataURL();
        });
        document.getElementById('reviewSignatureClearBtn')?.addEventListener('click', () => {
            reviewCtx.clearRect(0, 0, reviewCanvas.width, reviewCanvas.height);
            document.getElementById('reviewSignature').value = '';
            document.getElementById('systemSignaturePreview')?.classList.add('hidden');
            const button = document.getElementById('generateSystemSignatureBtn');
            if (button) {
                button.innerHTML = '<i class="fas fa-signature mr-1"></i> Approved Signature';
                button.classList.remove('bg-emerald-700');
                delete button.dataset.signatureGenerated;
            }
        });
        document.getElementById('generateSystemSignatureBtn')?.addEventListener('click', () => window.generateApprovedSignature());
    }

    // ─── CLOSE MODAL BUTTONS ──────────────────────────────────
    document.querySelectorAll('.modal-overlay .close-btn, .modal-overlay .btn-outline[data-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    // ─── REGISTER AND DISPATCH OUTGOING ───────────────────────
    window.registerOutgoing = function(ref) {
        const registrationReference = prompt('Registration reference (leave blank to generate one):', '');
        if (registrationReference === null) return;
        fetch('actions/register_outgoing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ ref, registration_reference: registrationReference }).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message || 'Letter registered.', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Error', data.message || 'Failed to register letter.', 'error');
            }
        })
        .catch(err => {
            showToast('Error', 'An error occurred while registering the letter.', 'error');
            console.error(err);
        });
    };

    window.dispatchOutgoing = function(ref) {
        const method = prompt('Dispatch method: enter email or physical', 'email');
        if (method === null) return;
        const normalizedMethod = method.trim().toLowerCase();
        if (!['email', 'physical'].includes(normalizedMethod)) {
            showToast('Error', 'Dispatch method must be email or physical.', 'error');
            return;
        }
        const recipient = prompt(
            normalizedMethod === 'email'
                ? 'Recipient email (leave blank to use the receiver email):'
                : 'Courier, postal, or hand-delivery details:',
            ''
        );
        if (recipient === null) return;
        if (normalizedMethod === 'physical' && !recipient.trim()) {
            showToast('Error', 'Delivery details are required for physical dispatch.', 'error');
            return;
        }
        const dispatchReference = prompt('Delivery reference (optional):', '');
        if (dispatchReference === null) return;
        if (confirm('Are you sure you want to dispatch this letter?')) {
            fetch('actions/dispatch_outgoing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    ref,
                    method: normalizedMethod,
                    recipient: recipient.trim(),
                    dispatch_reference: dispatchReference.trim()
                }).toString()
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

    // ─── USER MANAGEMENT ───────────────────────────────────
    window.resetUserPassword = function(username) {
        const newPassword = prompt('Enter the new password for ' + username);
        if (!newPassword) return;

        fetch('actions/reset_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ username, new_password: newPassword }).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Password Updated', data.message || 'Password reset successfully.', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Error', data.message || 'Could not reset password.', 'error');
            }
        })
        .catch(err => {
            showToast('Error', 'Unable to reset password.', 'error');
            console.error(err);
        });
    };

    window.deleteUser = function(username) {
        if (!confirm('Deactivate this user account? Their records will remain intact.')) return;

        fetch('actions/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ username }).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Account Deactivated', data.message || 'User account deactivated.', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Error', data.message || 'Could not deactivate user.', 'error');
            }
        })
        .catch(err => {
            showToast('Error', 'Unable to deactivate account.', 'error');
            console.error(err);
        });
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
