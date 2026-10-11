<!-- includes/modals.php -->
<!-- MODAL: Register Incoming Letter -->
<div id="registerModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-6" id="modalTitle">
            <i class="fas fa-file-import text-blue-600 mr-2"></i>Register Incoming Letter
        </h2>
        <form id="incomingForm" action="actions/add_incoming.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="editRefNumber" name="edit_ref_number" value="" />
            <div class="form-grid">
                <div>
                    <label class="form-label">Reference Number</label>
                    <input type="text" id="refNumber" name="ref_number" class="form-control" placeholder="Enter letter reference number" required />
                </div>
                <div>
                    <label class="form-label">Sender Unique Number</label>
                    <input type="text" id="senderUniqueNum" name="sender_unique_num" class="form-control" placeholder="e.g. MOF/REQ/2026/99" required />
                </div>
                <div class="full-width">
                    <label class="form-label">Title / Subject</label>
                    <input type="text" id="letterTitle" name="title" class="form-control" placeholder="e.g. Project Funding Request" required />
                </div>
                <div class="full-width">
                    <label class="form-label">Letter Description</label>
                    <textarea id="letterDesc" name="description" class="form-control" rows="2" placeholder="Brief description of the letter content"></textarea>
                </div>
                <div>
                    <label class="form-label">Sender Name / Org</label>
                    <input type="text" id="senderName" name="sender_name" class="form-control" placeholder="Organization or person" required />
                </div>
                <div>
                    <label class="form-label">Receiver Name</label>
                    <select id="receiverSelect" name="receiver_name" class="form-control" data-email-badge="receiverEmailBadge" required>
                        <?php foreach (getUsers() as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['department']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="receiverEmailBadge" class="mt-1 text-xs text-blue-600 font-medium">
                        <i class="fas fa-envelope mr-1"></i> Email: 
                    </div>
                </div>
                <div>
                    <label class="form-label">Receiver Email</label>
                    <input type="email" id="receiverEmail" name="receiver_email" class="form-control" placeholder="receiver@example.com" />
                </div>
                <div>
                    <label class="form-label">Letter Classification</label>
                    <select id="letterClassification" name="classification" class="form-control">
                        <optgroup label="1. Administration">
                            <option value="1.01 General Administration">1.01 General Administration</option>
                            <option value="1.02 Meetings">1.02 Meetings</option>
                            <option value="1.03 Correspondence">1.03 Correspondence</option>
                        </optgroup>
                        <optgroup label="2. Human Resources">
                            <option value="2.01 Recruitment">2.01 Recruitment</option>
                            <option value="2.02 Training">2.02 Training</option>
                            <option value="2.03 Employee Affairs">2.03 Employee Affairs</option>
                        </optgroup>
                        <optgroup label="3. Finance">
                            <option value="3.01 Budget">3.01 Budget</option>
                            <option value="3.02 Payment">3.02 Payment</option>
                            <option value="3.03 Procurement">3.03 Procurement</option>
                        </optgroup>
                        <optgroup label="4. Records Management">
                            <option value="4.01 Incoming Correspondence">4.01 Incoming Correspondence</option>
                            <option value="4.02 Outgoing Correspondence">4.02 Outgoing Correspondence</option>
                            <option value="4.03 Archiving">4.03 Archiving</option>
                        </optgroup>
                        <optgroup label="5. Transport / Vehicles">
                            <option value="5.01 Vehicle-related interaction">5.01 Vehicle-related interaction</option>
                            <option value="5.02 Vehicle Maintenance">5.02 Vehicle Maintenance</option>
                            <option value="5.03 Vehicle Registration">5.03 Vehicle Registration</option>
                            <option value="5.04 Fuel Management">5.04 Fuel Management</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="form-label">Security Level</label>
                    <select id="letterSecurityLevel" name="security_level" class="form-control">
                        <option value="Public">Public</option>
                        <option value="Official" selected>Official</option>
                        <option value="Confidential">Confidential</option>
                        <option value="Restricted">Restricted</option>
                        <option value="Top Secret">Top Secret</option>
                    </select>
                </div>
                <div class="full-width">
                    <label class="form-label">Keywords</label>
                    <input type="text" id="letterKeywords" name="keywords" class="form-control" placeholder="e.g. finance, procurement, audit" />
                </div>
                <div>
                    <label class="form-label">Date Received</label>
                    <input type="date" id="letterDate" name="received_date" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">Time Received</label>
                    <input type="time" id="letterTime" name="received_time" class="form-control" required />
                    <small class="text-xs text-gray-500">Defaults to the current system time; adjust if needed.</small>
                </div>
                <div>
                    <label class="form-label">Attach Document</label>
                    <div id="dropZone" class="drop-zone">
                        <div id="dropZoneContent">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                            <p class="text-sm text-gray-500 mt-2">Drag &amp; drop a file here, or click to browse</p>
                            <input type="file" id="fileInput" name="attached_file" style="display:none;" />
                        </div>
                        <div id="filePreview" style="display:none;" class="file-preview"></div>
                    </div>
                    <div id="uploadStatus" class="text-xs text-gray-400 mt-1"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" class="btn-outline" data-close>Cancel</button>
                <button type="submit" class="btn-success"><i class="fas fa-paper-plane"></i> Save &amp; Send</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: View Letter -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-file-alt text-blue-600"></i> Letter Details
        </h2>
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-4 text-sm space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-500">Reference:</span>
                <span id="viewRef" class="font-mono font-bold text-blue-600"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Title:</span>
                <span id="viewTitle" class="font-semibold text-gray-800"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Sender:</span>
                <span id="viewSender" class="text-gray-700"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Receiver:</span>
                <span id="viewReceiver" class="text-gray-700"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Receiver Email:</span>
                <span id="viewReceiverEmail" class="text-blue-600 font-mono text-xs"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Classification:</span>
                <span id="viewClassification" class="text-gray-700"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Security Level:</span>
                <span id="viewSecurityLevel" class="text-gray-700"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Keywords:</span>
                <span id="viewKeywords" class="text-gray-700"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Status:</span>
                <span id="viewStatus"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Date:</span>
                <span id="viewDate" class="text-gray-700"></span>
            </div>
            <div>
                <span class="text-gray-500 block">Description:</span>
                <p id="viewDesc" class="text-xs text-gray-600 italic bg-white p-2 rounded border mt-1"></p>
            </div>
            <div id="viewFileWrapper" class="hidden">
                <span class="text-gray-500 block">Attached File:</span>
                <span id="viewFileName" class="text-xs font-mono text-blue-600"></span>
            </div>
            <div id="viewRemarkWrapper" class="hidden">
                <span class="text-gray-500 block">Remarks:</span>
                <div id="viewRemark" class="remark-box"></div>
            </div>
            <div id="viewSigWrapper" class="hidden">
                <span class="text-gray-500 block">Digital Signature:</span>
                <img id="viewSigImg" src="" alt="Digital Signature" class="sig-preview-img" />
            </div>
        </div>
        <div class="flex justify-end"><button type="button" class="btn-primary" data-close>Close</button></div>
    </div>
</div>

<!-- MODAL: Enlarged QR document information -->
<div id="qrLetterModal" class="modal-overlay">
    <div class="modal-box max-w-lg text-center">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><i class="fas fa-qrcode text-blue-600 mr-2"></i>Document QR Code</h2>
        <p class="text-sm text-slate-500 mb-4">Scan this code with a phone to read the document summary.</p>
        <div id="largeQrCode" class="mx-auto w-fit rounded-xl border border-slate-200 bg-white p-4 shadow-sm"></div>
        <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4 text-left text-sm space-y-2">
            <div><span class="text-slate-500">Reference:</span> <strong id="qrInfoRef"></strong></div>
            <div><span class="text-slate-500">Title:</span> <strong id="qrInfoTitle"></strong></div>
            <div><span class="text-slate-500">Sender:</span> <strong id="qrInfoSender"></strong></div>
            <div><span class="text-slate-500">Receiver:</span> <strong id="qrInfoReceiver"></strong></div>
            <div><span class="text-slate-500">Status:</span> <strong id="qrInfoStatus"></strong></div>
        </div>
        <div class="flex justify-end mt-4"><button type="button" class="btn-primary" data-close>Close</button></div>
    </div>
</div>

<!-- MODAL: Register Outgoing Letter -->
<div id="registerOutgoingModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-paper-plane text-emerald-600 mr-2"></i>Register Outgoing Letter
        </h2>
        <form id="outgoingForm" action="actions/add_outgoing.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="outgoingEditRef" name="edit_out_ref" value="" />
            <div class="form-grid">
                <div>
                    <label class="form-label">Reference Number</label>
                    <input type="text" id="outgoingRefNumber" name="ref_number" class="form-control" placeholder="Enter reference number" required />
                </div>
                <div>
                    <label class="form-label">Classification</label>
                    <select id="outgoingClassification" name="classification" class="form-control">
                        <optgroup label="1. Administration">
                            <option value="1.01 General Administration">1.01 General Administration</option>
                            <option value="1.02 Meetings">1.02 Meetings</option>
                            <option value="1.03 Correspondence">1.03 Correspondence</option>
                        </optgroup>
                        <optgroup label="2. Human Resources">
                            <option value="2.01 Recruitment">2.01 Recruitment</option>
                            <option value="2.02 Training">2.02 Training</option>
                            <option value="2.03 Employee Affairs">2.03 Employee Affairs</option>
                        </optgroup>
                        <optgroup label="3. Finance">
                            <option value="3.01 Budget">3.01 Budget</option>
                            <option value="3.02 Payment">3.02 Payment</option>
                            <option value="3.03 Procurement">3.03 Procurement</option>
                        </optgroup>
                        <optgroup label="4. Records Management">
                            <option value="4.01 Incoming Correspondence">4.01 Incoming Correspondence</option>
                            <option value="4.02 Outgoing Correspondence">4.02 Outgoing Correspondence</option>
                            <option value="4.03 Archiving">4.03 Archiving</option>
                        </optgroup>
                        <optgroup label="5. Transport / Vehicles">
                            <option value="5.01 Vehicle-related interaction">5.01 Vehicle-related interaction</option>
                            <option value="5.02 Vehicle Maintenance">5.02 Vehicle Maintenance</option>
                            <option value="5.03 Vehicle Registration">5.03 Vehicle Registration</option>
                            <option value="5.04 Fuel Management">5.04 Fuel Management</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="form-label">Security Level</label>
                    <select id="outgoingSecurityLevel" name="security_level" class="form-control">
                        <option value="Public">Public</option>
                        <option value="Official" selected>Official</option>
                        <option value="Confidential">Confidential</option>
                        <option value="Restricted">Restricted</option>
                        <option value="Top Secret">Top Secret</option>
                    </select>
                </div>
                <div class="full-width">
                    <label class="form-label">Title / Subject</label>
                    <input type="text" id="outgoingTitle" name="title" class="form-control" placeholder="e.g. Budget Approval Request" required />
                </div>
                <div class="full-width">
                    <label class="form-label">Letter Description</label>
                    <textarea id="outgoingDesc" name="description" class="form-control" rows="2"></textarea>
                </div>
                <div>
                    <label class="form-label">Sender Name</label>
                    <input type="text" id="outgoingSenderName" name="sender_name" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">Sender Unique Number</label>
                    <input type="text" id="outgoingSenderUniqueNum" name="sender_unique_num" class="form-control" placeholder="Enter sender unique number" required />
                </div>
                <div>
                    <label class="form-label">Receiver Name</label>
                    <select id="outgoingReceiverSelect" name="receiver_name" class="form-control" data-email-badge="outgoingReceiverEmailBadge" required>
                        <option value="">Select receiver</option>
                        <?php foreach (getUsers() as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['full_name']); ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['department']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="outgoingReceiverEmailBadge" class="mt-1 text-xs text-emerald-600 font-medium">
                        <i class="fas fa-envelope mr-1"></i> Email:
                    </div>
                </div>
                <div>
                    <label class="form-label">Receiver Email</label>
                    <input type="email" id="outgoingReceiverEmail" name="receiver_email" class="form-control" placeholder="receiver@example.com" />
                </div>
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" id="outgoingDate" name="date" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">Attach Document</label>
                    <div id="outDropZone" class="drop-zone">
                        <div id="outDropZoneContent">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                            <p class="text-sm text-gray-500 mt-2">Drag &amp; drop a file here, or click to browse</p>
                            <input type="file" id="outFileInput" name="attached_file" style="display:none;" />
                        </div>
                        <div id="outFilePreview" style="display:none;" class="file-preview"></div>
                    </div>
                    <div id="outUploadStatus" class="text-xs text-gray-400 mt-1"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" class="btn-outline" data-close>Cancel</button>
                <button type="submit" class="btn-success"><i class="fas fa-paper-plane"></i> Save &amp; Send</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Register Internal Memo -->
<div id="registerInternalModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-envelope text-violet-600 mr-2"></i>Register Internal Memo
        </h2>
        <form id="internalForm" action="actions/add_internal.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="internalEditRef" name="edit_internal_ref" value="" />
            <div class="form-grid">
                <div>
                    <label class="form-label">Reference Number</label>
                    <input type="text" id="internalRefNumber" name="ref_number" class="form-control" placeholder="Enter reference number" required />
                </div>
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" id="internalDate" name="date" class="form-control" required />
                </div>
                <div class="full-width">
                    <label class="form-label">Title / Subject</label>
                    <input type="text" id="internalTitle" name="title" class="form-control" placeholder="e.g. Staff Meeting Reminder" required />
                </div>
                <div class="full-width">
                    <label class="form-label">Memo Description</label>
                    <textarea id="internalDesc" name="description" class="form-control" rows="2"></textarea>
                </div>
                <div>
                    <label class="form-label">Sender Name</label>
                    <input type="text" id="internalSenderName" name="sender_name" class="form-control" required />
                </div>
                <div>
                    <label class="form-label">Receiver Name</label>
                    <select id="internalReceiverSelect" name="receiver_name" class="form-control" data-email-badge="internalReceiverEmailBadge" required>
                        <option value="">Select receiver</option>
                        <?php foreach (getUsers() as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['full_name']); ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['department']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="internalReceiverEmailBadge" class="mt-1 text-xs text-violet-600 font-medium">
                        <i class="fas fa-envelope mr-1"></i> Email:
                    </div>
                </div>
                <div>
                    <label class="form-label">Receiver Email</label>
                    <input type="email" id="internalReceiverEmail" name="receiver_email" class="form-control" placeholder="receiver@example.com" />
                </div>
                <div>
                    <label class="form-label">Attach Document</label>
                    <div id="internalDropZone" class="drop-zone">
                        <div id="internalDropZoneContent">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                            <p class="text-sm text-gray-500 mt-2">Drag &amp; drop a file here, or click to browse</p>
                            <input type="file" id="internalFileInput" name="attached_file" style="display:none;" />
                        </div>
                        <div id="internalFilePreview" style="display:none;" class="file-preview"></div>
                    </div>
                    <div id="internalUploadStatus" class="text-xs text-gray-400 mt-1"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" class="btn-outline" data-close>Cancel</button>
                <button type="submit" class="btn-success"><i class="fas fa-paper-plane"></i> Save &amp; Send</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Review Document -->
<div id="reviewModal" class="modal-overlay">
    <div class="modal-box max-w-3xl">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            <i class="fas fa-signature text-emerald-600 mr-2"></i>Review Document
        </h2>
        <form id="reviewForm">
            <input type="hidden" id="reviewRef" name="ref" value="" />
            <input type="hidden" id="reviewType" name="type" value="incoming" />
            <input type="hidden" id="reviewSignature" name="signature" value="" />
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-semibold text-slate-700 mb-3">Document Details</div>
                    <div id="reviewSummary" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm"></div>
                </div>
                <div id="reviewAttachmentSection" class="rounded-xl border border-slate-200 bg-white p-4 hidden">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <div class="text-sm font-semibold text-slate-700">Attached Document</div>
                            <div id="reviewAttachmentMeta" class="text-xs text-slate-500"></div>
                        </div>
                        <a id="reviewAttachmentLink" href="#" target="_blank" class="text-sm text-blue-600 font-medium">Open attachment</a>
                    </div>
                    <iframe id="reviewAttachmentPreview" class="w-full h-96 mt-3 rounded-lg border border-slate-200 hidden"></iframe>
                    <img id="reviewAttachmentImage" class="w-full max-h-[420px] object-contain mt-3 rounded-lg border border-slate-200 hidden" alt="Attached document" />
                </div>
                <div class="full-width">
                    <label class="form-label">Review Remark</label>
                    <textarea id="reviewRemark" name="remark" class="form-control" rows="3" placeholder="Add approval or rejection comments"></textarea>
                </div>
                <div class="full-width">
                    <label class="form-label">Digital Signature</label>
                    <div class="rounded-xl border border-slate-200 p-3 bg-slate-50">
                        <canvas id="reviewSignatureCanvas" class="w-full rounded-lg border border-slate-300 bg-white" style="height:140px;cursor:crosshair;"></canvas>
                        <img id="systemSignaturePreview" class="hidden mt-3 w-full rounded-lg border border-emerald-200 bg-white" alt="Generated approved signature" />
                        <div class="flex items-end gap-3 mt-3 flex-wrap">
                            <label class="text-sm text-slate-600">Pen color
                                <input type="color" id="signaturePenColor" value="#0a1e3a" class="block mt-1 h-9 w-14 cursor-pointer rounded border border-slate-300 p-1" />
                            </label>
                            <label class="text-sm text-slate-600">Pen size <span id="signaturePenSizeValue">3</span>px
                                <input type="range" id="signaturePenSize" min="1" max="10" step="0.5" value="3" class="block mt-2 w-32 cursor-pointer" />
                            </label>
                        </div>
                        <div class="flex gap-2 mt-3 flex-wrap">
                            <button type="button" id="generateSystemSignatureBtn" class="btn-primary" onclick="window.generateApprovedSignature && window.generateApprovedSignature()"><i class="fas fa-signature mr-1"></i> Approved Signature</button>
                            <button type="button" id="reviewSignatureClearBtn" class="btn-outline">Clear Signature</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" id="reviewRejectButton" class="btn-outline" onclick="submitReview('reject')">Reject</button>
                <button type="button" id="reviewApproveButton" class="btn-success" onclick="submitReview('approve')"><i class="fas fa-check"></i> Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Add User -->
<div id="addUserModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-btn" data-close>&times;</button>
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-user-plus text-emerald-600"></i> Add New User
        </h2>
        <form id="addUserForm" action="actions/add_user.php" method="POST">
            <div class="form-grid">
                <div>
                    <label class="form-label">First Name</label>
                    <input type="text" id="newUserFirstName" name="first_name" class="form-control" placeholder="e.g. Alemayehu" required />
                </div>
                <div>
                    <label class="form-label">Middle Name</label>
                    <input type="text" id="newUserMiddleName" name="middle_name" class="form-control" placeholder="e.g. Girma" />
                </div>
                <div>
                    <label class="form-label">Last Name</label>
                    <input type="text" id="newUserLastName" name="last_name" class="form-control" placeholder="e.g. Bekele" required />
                </div>
                <div>
                    <label class="form-label">Username</label>
                    <input type="text" id="newUserUsername" name="username" class="form-control" placeholder="e.g. alemayehu_bekele" />
                </div>
                <div class="full-width">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="newUserEmail" name="email" class="form-control" placeholder="alemayehu.girma@nala.gov.et" required />
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <select id="newUserRole" name="role" class="form-control">
                        <option value="records_officer">Records Officer</option>
                        <option value="dept_manager">Department Manager</option>
                        <option value="director">Director General</option>
                        <option value="intermediary">Intermediary Officer</option>
                        <option value="archivist">Archivist</option>
                        <option value="staff">Staff Member</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <select id="newUserDept" name="department" class="form-control" required>
                        <option value="">Select department</option>
                        <?php foreach (getDepartments() as $department): ?>
                            <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="text" id="newUserPassword" name="password" class="form-control" placeholder="e.g. alemayehu123" required />
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" class="btn-outline" data-close>Cancel</button>
                <button type="submit" class="btn-success"><i class="fas fa-save"></i> Create User</button>
            </div>
        </form>
    </div>
</div>
