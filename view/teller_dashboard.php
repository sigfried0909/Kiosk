<?php
session_start();
if (!isset($_SESSION['Username']) || $_SESSION['UserType'] !== 'teller') {
    header("Location: ../index.php");
    exit();
}
$department = $_SESSION['Department'] ?? 'GC';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($department) ?> | Teller Dashboard</title>
    <link rel="icon" type="image" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/dashboard.css">
</head>

<style>
    /* Skipped panel header */
    .skipped-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 0.5rem;
        font-weight: bold;
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 1px solid #ccc;
    }
    
    /* Column widths */
    .col-ticket { width: 15%; min-width: 80px; }
    .col-name   { width: 25%; min-width: 100px; }
    .col-contact { width: 20%; min-width: 80px; }
    .col-remarks { width: 40%; min-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .col-action  { width: 10%; text-align: right; }
    
    .skipped-table-container {
    max-height: 240px; /* ~5 items */
    overflow-y: auto;
    }
    .sticky-top {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 2;
    }
</style>

<body>
    <!-- TOPBAR -->
    <header class="topbar d-flex justify-content-between align-items-center px-4 py-3 bg-primary">
        <div class="d-flex align-items-center gap-2">
            <img src="../assets/images/logo.jpg" class="logo" alt="Logo" style="height: 40px;">
            <?php
            $deptFullNames = [
                "GC" => "General Consultation",
                "MCHS" => "Maternal & Child Health Services",
                "LS" => "Laboratory Services",
                "DS" => "Dental Services",
                "PDA" => "Pharmacy & Dispensing Area",
                "ABTC" => "Animal Bite Treatment Center",
                "MR" => "Medical Records"
            ];

            $displayDept = $deptFullNames[$department] ?? $department;
            ?>
            <h1 class="fs-4 text-white m-2">Z-Kiosk Pro | <?= htmlspecialchars($displayDept) ?></h1>
        </div>
        <!-- USER DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['Username']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <h6 class="dropdown-header"><?= htmlspecialchars($displayDept) ?> Department</h6>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item text-danger fw-semibold" href="../model/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a></li>
            </ul>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container-fluid p-5">
        <div class="row g-4">
            <!-- LEFT PANEL -->
            <div class="col-lg-6 col-md-12">
                <div class="card left-panel shadow-sm">
                    <div class="card-body">
                        <h5 class="text-muted mb-2">Now Serving</h5>
                        <h1 class="display-2 fw-bold text-primary" id="nowServing">--</h1>
                        <hr>
                        <div class="queue-info mt-3">
                            <div class="mb-3">
                                <h5 class="text-muted">Waiting</h5>
                                <div id="waitingList" class="fw-bold text-warning fs-5">--</div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Pending</h6>
                                    <h4 class="fw-bold text-danger" id="pendingNum">0</h4>
                                </div>
                                <div>
                                    <h6 class="text-muted">Served</h6>
                                    <h4 class="fw-bold text-success" id="servedNum">0</h4>
                                </div>
                                <div>
                                    <h6 class="text-muted">Skipped</h6>
                                    <h4 class="fw-bold text-secondary" id="skippedNum">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SKIPPED PANEL -->
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="text-muted mb-3">Skipped Numbers</h5>
                        
                        <!-- Search Bar -->
                        <input type="text" id="skippedSearch" class="form-control mb-2" placeholder="Search Ticket / Name / Contact...">
                
                        <!-- Table container -->
                        <div class="skipped-table-container">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="skippedTableBody">
                                    <tr><td colspan="5" class="text-center text-muted">--</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="col-lg-6 col-md-12">
                <div class="card right-panel shadow-sm">
                    <div class="card-body">
                        <div class="btn-group d-flex justify-content-between mb-4">
                            <button class="btn btn-primary btn-lg flex-fill mx-1 action-btn"
                                data-action="Page">Page</button>
                            <button class="btn btn-secondary btn-lg flex-fill mx-1 action-btn"
                                data-action="Skip">Skip</button>
                            <button class="btn btn-info btn-lg flex-fill mx-1 action-btn"
                                data-action="Forward">Forward</button>
                            <button class="btn btn-success btn-lg flex-fill mx-1 action-btn"
                                data-action="Next">Next</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Patient Name</label>
                            <p class="fs-5 fw-bold text-dark bg-light p-3 rounded border" id="patientName">--</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contact Number</label>
                            <p class="fs-5 fw-bold text-dark bg-light p-3 rounded border" id="contactNumber">--</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control form-control-lg" id="remarks" rows="3"
                                placeholder="Enter remarks here..."></textarea>
                        </div>
                        <div class="alert alert-info py-2 small mb-2 fade-in" role="alert" style="font-size: 0.9rem;">
                            💡 <b>Optional:</b> You may add notes or instructions for the next department or
                            reporting purposes. Leave blank if not needed.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center text-muted small py-3">
        Powered By: ZionTech
    </footer>

    <!-- Forward Modal -->
    <div class="modal fade" id="forwardModal" tabindex="-1" aria-labelledby="forwardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-info text-white rounded-top-4">
                    <h5 class="modal-title fw-semibold" id="forwardModalLabel">Select Department to Forward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label for="forwardDept" class="form-label">Choose a department:</label>
                    <select id="forwardDept" class="form-select form-select-lg rounded-3">
                        <option value="">-- Select Department --</option>
                        <option value="GC">General Consultation</option>
                        <option value="MCHS">Maternal & Child Health Services</option>
                        <option value="LS">Laboratory Services</option>
                        <option value="DS">Dental Services</option>
                        <option value="PDA">Pharmacy & Dispensing Area</option>
                        <option value="ABTC">Animal Bite Treatment Center</option>
                        <option value="MR">Medical Records</option>
                    </select>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info px-4 text-white" id="confirmForward">Forward</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg text-center">
                <div class="modal-header-confirm mt-3">
                    <div id="confirmIcon" class="modal-icon text-warning">⚠️</div>
                    <h5 id="confirmTitle" class="fw-semibold"></h5>
                </div>
                <div class="modal-body" id="confirmText"></div>
                <div class="modal-footer-confirm mb-3 d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" id="confirmYes">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <div id="customToast" class="toast align-items-center text-bg-primary border-0 fade" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold" id="toastMessage">Action completed successfully.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const department = "<?= $department ?>";

        // === Stable Fetch Wrapper ===
        async function safeFetch(url, options = {}) {
            try {
                const res = await fetch(url, { cache: "no-store", ...options });
                const raw = await res.text();

                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                // Try to parse as JSON if possible
                try {
                    return JSON.parse(raw);
                } catch {
                    // Return as plain text if JSON parsing fails
                    return { message: raw.trim() };
                }
            } catch (err) {
                console.warn("Fetch failed:", url, err.message);
                showToast("Connection lost. Please retry.", "error");
                return null;
            }
        }

        // Toast Function
        function showToast(message, type = 'info') {
            const toastEl = document.getElementById('customToast');
            const toastBody = document.getElementById('toastMessage');
            toastEl.className = 'toast align-items-center border-0 fade show';
            switch (type) {
                case 'success': toastEl.classList.add('toast-success'); break;
                case 'error': toastEl.classList.add('toast-error'); break;
                case 'warning': toastEl.classList.add('toast-warning'); break;
                default: toastEl.classList.add('toast-info'); break;
            }
            toastBody.textContent = message;
            new bootstrap.Toast(toastEl, { delay: 3000 }).show();
        }

        // Load queue data
        async function loadQueueData() {
            const data = await safeFetch(`../model/get_queue_data.php?department=${department}`);
            if (!data) return;

            document.getElementById('nowServing').textContent = data.nowServing ?? '--';
            const waitingListDiv = document.getElementById('waitingList');
            if (data.waitingTickets?.length > 0) {
                waitingListDiv.innerHTML = data.waitingTickets.map(t => `<span class="badge bg-warning text-dark me-1">${t}</span>`).join(' ');
            } else {
                waitingListDiv.textContent = '--';
            }
            document.getElementById('pendingNum').textContent = data.pendingCount ?? 0;
            document.getElementById('servedNum').textContent = data.servedCount ?? 0;
            document.getElementById('skippedNum').textContent = data.skippedCount ?? 0;
            document.getElementById('patientName').textContent = data.name ?? '--';
            document.getElementById('contactNumber').textContent = data.contact ?? '--';

            const remarkBox = document.getElementById('remarks');
            const currentRemarks = remarkBox.value.trim();
            const incomingRemarks = data.remarks ?? '';
            if ((!remarkBox.matches(':focus') && !currentRemarks) || currentRemarks === incomingRemarks) {
                remarkBox.value = incomingRemarks;
            }
            
            // Skipped list
            const skippedDiv = document.getElementById('skippedList');

            if (data.skippedTickets?.length > 0) {
                skippedDiv.innerHTML = `
                    <div class="skipped-header">
                        <div class="col-ticket">Ticket #</div>
                        <div class="col-name">Name</div>
                        <div class="col-contact">Contact</div>
                        <div class="col-remarks">Remarks</div>
                        <div class="col-action"></div>
                    </div>
                    <div class="skipped-body">
                        ${data.skippedTickets.map(s => `
                            <div class="skipped-item d-flex align-items-center py-1 border-bottom">
                                <div class="col-ticket">${s.ticket}</div>
                                <div class="col-name">${s.name ?? '--'}</div>
                                <div class="col-contact">${s.contact ?? '--'}</div>
                                <div class="col-remarks">${s.remarks ?? '--'}</div>
                                <div class="col-action ms-auto">
                                    <button 
                                        class="btn btn-sm btn-outline-primary recall-btn"
                                        data-id="${s.id}">
                                        Recall
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            
                // Scrollable if more than 5 items
                if (data.skippedTickets.length > 5) {
                    skippedDiv.classList.add('scrollable-skipped');
                } else {
                    skippedDiv.classList.remove('scrollable-skipped');
                }
            } else {
                skippedDiv.innerHTML = `<div class="text-muted small">--</div>`;
                skippedDiv.classList.remove('scrollable-skipped');
            }
        }

        loadQueueData();
        setInterval(loadQueueData, 8000);

        // Confirmation modal helper
        function confirmAction(title, message, icon = "⚠️") {
            return new Promise((resolve) => {
                document.getElementById('confirmTitle').textContent = title;
                document.getElementById('confirmText').textContent = message;
                document.getElementById('confirmIcon').textContent = icon;
                const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
                modal.show();
                document.getElementById('confirmYes').onclick = () => {
                    modal.hide();
                    resolve(true);
                };
            });
        }

        // Process action
        async function processAction(department, action, remarks, targetDept) {
            if (action === 'Page') {
                const audio = new Audio('../assets/sounds/beep.mp3');
                audio.play();
                await safeFetch('../model/page_ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ department })
                });
                showToast("Paging ticket for " + department + " department...", 'success');
                return;
            }
        
            if (['Skip', 'Next', 'Forward'].includes(action)) {
                document.getElementById('remarks').value = '';
            }
        
            const result = await safeFetch('../model/update_queue_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ department, action, remarks, targetDept })
            });
        
            if (!result) return;
            if (result.error) {
                showToast(result.error, 'error');
                return;
            }
        
            showToast(result.message ?? "Action completed successfully.", 'success');
        
            // lways refresh right panel
            loadQueueData();
        
            // NLY refresh skipped panel if action was SKIP
            if (action === 'Skip') {
                loadSkippedTickets();
            }
        }
        
        // Action Buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const action = btn.dataset.action;
                const remarks = document.getElementById('remarks').value.trim();

                if (action === 'Forward') {
                    const data = await safeFetch(`../model/check_departments.php?department=${department}`);
                    if (!data) return;
                    if (data.hasMultiple && data.isLast) {
                        showToast("All departments are served. Nothing to forward.", 'error');
                        return;
                    }
                    if (data.hasMultiple && !data.isLast) {
                        const confirm = await confirmAction("Forward Patient", "Forward to next department?", "➡️");
                        if (!confirm) return;
                        await processAction(department, action, remarks, null);
                        return;
                    }
                    const modal = new bootstrap.Modal(document.getElementById('forwardModal'));
                    modal.show();
                    document.getElementById('confirmForward').onclick = async () => {
                        const targetDept = document.getElementById('forwardDept').value;
                        if (!targetDept) return showToast("Select a department to forward to.", 'warning');
                        modal.hide();
                        await processAction(department, action, remarks, targetDept);
                    };
                    return;
                }

                if (['Skip', 'Next'].includes(action)) {
                    const confirm = await confirmAction(
                        action === 'Skip' ? "Skip Patient" : "Next Patient",
                        action === 'Skip'
                            ? "Are you sure you want to skip this patient?"
                            : "Proceed to next patient?",
                        action === 'Skip' ? "⏭️" : "✅"
                    );
                    if (!confirm) return;
                }
                await processAction(department, action, remarks, null);
            });
        });
        
        document.addEventListener('click', async (e) => {
            if (!e.target.classList.contains('recall-btn')) return;
        
            const id = e.target.dataset.id;
        
            const confirm = await confirmAction(
                "Recall Skipped Number",
                "Recall this skipped number back to queue?",
                "🔁"
            );
            if (!confirm) return;
        
            const res = await safeFetch('../model/recall_skipped.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ department, id })
            });
        
            if (res?.error) {
                showToast(res.error, 'error');
                return;
            }
        
            if (res.success) {
                // lways update remarks panel
                document.getElementById('remarks').value = res.remarks ?? '';
        
                // pdate Now Serving ONLY if promoted
                if (res.newStatus === 'Serving') {
                    document.getElementById('nowServing').textContent = res.ticket ?? '--';
                    document.getElementById('patientName').textContent = res.name ?? '--';
                    document.getElementById('contactNumber').textContent = res.contact ?? '--';
                }
        
                showToast("Skipped number recalled.", 'success');
        
                // EFRESH skipped panel ONLY HERE
                loadSkippedTickets();
        
                // pdate right panel state
                loadQueueData();
            }
        });
        
        async function loadSkippedTickets() {
            const data = await safeFetch(`../model/get_queue_data.php?department=${department}`);
            if (!data || !data.skippedTickets) return;
        
            const tbody = document.getElementById('skippedTableBody');
            if (data.skippedTickets.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">--</td></tr>`;
                return;
            }
        
            tbody.innerHTML = data.skippedTickets.map(s => `
                <tr data-ticket="${s.ticket}" data-name="${s.name}" data-contact="${s.contact}" data-remarks="${s.remarks}">
                    <td>${s.ticket}</td>
                    <td>${s.name}</td>
                    <td>${s.contact}</td>
                    <td>${s.remarks}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary recall-btn" data-id="${s.id}">Recall</button>
                    </td>
                </tr>
            `).join('');
        }
        
        loadSkippedTickets();
        
        // Search functionality
        document.getElementById('skippedSearch').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#skippedTableBody tr').forEach(row => {
                const ticket = row.dataset.ticket.toLowerCase();
                const name = row.dataset.name.toLowerCase();
                const contact = row.dataset.contact.toLowerCase();
                const remarks = row.dataset.remarks.toLowerCase();
        
                row.style.display = (ticket.includes(query) || name.includes(query) || contact.includes(query))
                    ? ''
                    : 'none';
            });
        });
    </script>
</body>

</html>