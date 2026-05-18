<?php
session_start();
if (!isset($_SESSION['Username']) || $_SESSION['UserType'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$department = $_SESSION['Department'] ?? 'GC';
$deptNames = [
    "GC" => "General Consultation",
    "MCHS" => "Maternal & Child Health Services",
    "LS" => "Laboratory Services",
    "DS" => "Dental Services",
    "PDA" => "Pharmacy & Dispensing Area",
    "ABTC" => "Animal Bite Treatment Center",
    "MR" => "Medical Records"
];
$deptFull = $deptNames[$department] ?? $department;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Z-Kiosk Pro | <?= htmlspecialchars($deptFull) ?> Admin</title>

    <link rel="icon" type="image" href="../../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../style/super_admin/admin.css">

    <!-- Export libraries -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <div class="app">
        <!-- ===== TOPBAR ===== -->
        <div class="topbar d-flex justify-content-between align-items-center px-4">
            <div class="brand d-flex align-items-center gap-2">
                <button class="sidebar-toggle btn btn-light me-3">
                    <i class="bi bi-list"></i>
                </button>
                <img src="../../assets/images/logo.jpg" class="logo" alt="Ziontech Logo">
                <span class="fw-semibold text-white fs-5">Z-Kiosk Pro | <?= htmlspecialchars($deptFull) ?> Admin</span>
            </div>
            <div class="dropdown2">
                <button class="btn btn-light dropdown-toggle fw-semibold" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> Administrator
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item text-danger fw-semibold" href="../../model/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <nav>
                <a class="nav-link active" data-target="dashboard">Dashboard</a>
                <a class="nav-link" data-target="reports">Reports</a>
                <a class="nav-link" data-target="accounts">Accounts</a>
                <a class="nav-link" data-target="profile">My Profile</a>
            </nav>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="main-content p-4">

            <!-- DASHBOARD -->
            <section id="dashboard" class="page-section active">
                <h2 class="text-center fw-bold mb-5">DASHBOARD</h2>
                <div class="row g-4 justify-content-center text-center mb-4">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-title">Total Queue</div>
                            <div class="stat-value" id="totalQueue">0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Queue No. Served</div>
                            <div class="stat-value" id="queueServed">0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Total No. Skipped</div>
                            <div class="stat-value" id="totalSkipped">0</div>
                        </div>
                    </div>
                </div>

                <!-- === Live Queue Display (Read Only) === -->
                <div class="row g-4 mt-5">
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
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="card right-panel shadow-sm">
                            <div class="card-body">
                                <div class="mb-3"><label class="form-label fw-semibold">Patient Name</label>
                                    <p class="fs-5 fw-bold text-dark bg-light p-3 rounded border" id="patientName">--
                                    </p>
                                </div>
                                <div class="mb-3"><label class="form-label fw-semibold">Contact Number</label>
                                    <p class="fs-5 fw-bold text-dark bg-light p-3 rounded border" id="contactNumber">--
                                    </p>
                                </div>
                                <div class="mb-3"><label class="form-label fw-semibold">Remarks</label>
                                    <textarea class="form-control form-control-lg" id="remarks" rows="3" readonly
                                        placeholder="No remarks."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- REPORTS -->
            <section id="reports" class="page-section">
                <h2 class="text-center fw-bold mb-4">REPORTS</h2>
                <div class="card p-4 mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label>Status</label>
                            <select id="reportStatus" class="form-select">
                                <option value="All">All</option>
                                <option value="Served">Served</option>
                                <option value="Skipped">Skipped</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Start</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>End</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button class="btn btn-primary flex-fill" id="generateReport">
                                <i class="bi bi-funnel-fill"></i> Generate
                            </button>
                            <div class="dropdown flex-fill">
                                <button class="btn btn-success dropdown-toggle w-100" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-download"></i> Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" id="exportExcel"><i
                                                class="bi bi-file-earmark-excel-fill text-success"></i> Excel</a></li>
                                    <li><a class="dropdown-item" href="#" id="exportPDF"><i
                                                class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap">
                        <!-- Search -->
                        <div class="mb-2" style="max-width: 400px;">
                            <label for="searchQuery" class="form-label fw-bold">Search</label>
                            <div class="input-group shadow-sm rounded">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchQuery" class="form-control border-start-0" placeholder="Search Ticket or Name">
                            </div>
                        </div>
                    
                        <!-- Totals -->
                        <div id="reportTotals" class="fw-bold ms-auto mb-2 text-end">
                            Total Served: 0 | Total Skipped: 0 | Overall: 0
                        </div>
                    </div>
                    <table class="table table-bordered text-center align-middle" id="reportTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Queue</th>
                                <th>Name</th>
                                <th>Time Queue</th>
                                <th>Time Served</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <i class="bi bi-info-circle me-1"></i> No records yet. Please generate a report.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ACCOUNTS -->
            <section id="accounts" class="page-section">
                <h2 class="text-center fw-bold mb-4">TELLER ACCOUNTS</h2>
                <div class="card p-3">
                    <table class="table table-bordered text-center align-middle" id="accountTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <!-- PROFILE -->
            <section id="profile" class="page-section">
                <h2 class="text-center fw-bold mb-4">MY PROFILE</h2>
                <div class="card p-5 mx-auto" style="max-width:600px;">
                    <form id="adminProfileForm">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" id="profUsername" class="form-control">
                        </div>
                        <div class="mb-3"><label>First Name</label><input type="text" name="fname" id="profFname"
                                class="form-control"></div>
                        <div class="mb-3"><label>Last Name</label><input type="text" name="lname" id="profLname"
                                class="form-control"></div>
                        <div class="mb-3"><label>Old Password</label><input type="password" name="old_password"
                                id="profOldPass" class="form-control"></div>
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="profNewPass" class="form-control">
                            <small class="text-muted fst-italic">Choose a password that is easy for you to remember but
                                hard for others to guess.</small>
                        </div>
                        <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.9rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            All fields are optional. Update only the information you wish to change.
                        </div>
                        <button class="btn btn-success w-100 mt-3">Update Profile</button>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <!-- ACCOUNT MODAL -->
    <div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content account-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="accountForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="accountId">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">First Name</label><input type="text"
                                    class="form-control" name="first_name" id="accFname"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Last Name</label><input type="text"
                                    class="form-control" name="last_name" id="accLname"></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Username</label><input type="text"
                                class="form-control" name="username" id="accUsername"></div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="password"
                                class="form-control" name="password" id="accPassword" placeholder="••••••">
                            <small class="text-muted fst-italic">
                                Choose a password that is easy for you to remember but hard for others to guess.
                            </small>
                        </div>
                        <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.9rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>All fields are optional. Update only what you
                            wish to change.
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                        <button type="button" class="btn btn-success" id="saveAccountBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../controller/admin/admin_dashboard.js"></script>
    <script>
        const department = "<?= $department ?>";
        async function loadQueueData() {
            try {
                const res = await fetch(`../../model/get_queue_data.php?department=${department}`);
                const data = await res.json();
                if (!data) return;
                document.getElementById('nowServing').textContent = data.nowServing ?? '--';
                const waiting = document.getElementById('waitingList');
                waiting.innerHTML = data.waitingTickets?.length
                    ? data.waitingTickets.map(t => `<span class="badge bg-warning text-dark me-1">${t}</span>`).join(' ')
                    : '--';
                document.getElementById('pendingNum').textContent = data.pendingCount ?? 0;
                document.getElementById('servedNum').textContent = data.servedCount ?? 0;
                document.getElementById('skippedNum').textContent = data.skippedCount ?? 0;
                document.getElementById('patientName').textContent = data.name ?? '--';
                document.getElementById('contactNumber').textContent = data.contact ?? '--';
                document.getElementById('remarks').value = data.remarks ?? '';
            } catch (err) { console.error('Queue data fetch failed:', err); }
        }
        setInterval(loadQueueData, 3000);
        loadQueueData();
        
        document.querySelector(".sidebar-toggle").addEventListener("click", function () {
        const sidebar = document.querySelector(".sidebar");
        const content = document.querySelector(".main-content");
    
        // MOBILE (slide in/out)
        if (window.innerWidth < 992) {
            sidebar.classList.toggle("active");
            return;
        }
    
        // DESKTOP (collapse/expand)
        sidebar.classList.toggle("collapsed");
        content.classList.toggle("expanded");
        });
        
        const searchInput = document.getElementById("searchQuery");

        searchInput.addEventListener("input", () => {
            const query = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll("#reportTable tbody tr");
        
            rows.forEach(row => {
                // Ignore placeholder rows (like "No records found")
                if (row.children.length < 3) return;
        
                const ticket = row.children[1].textContent.toLowerCase();
                const name = row.children[2].textContent.toLowerCase();
        
                // Show row if either ticket or name matches query
                row.style.display = ticket.includes(query) || name.includes(query) ? "" : "none";
            });
        });
    </script>
</body>

</html>