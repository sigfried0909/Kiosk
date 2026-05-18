<?php
session_start();
require_once("../../model/db_connect.php");
if (!isset($_SESSION['Username']) || $_SESSION['UserType'] !== 'super_admin') {
    header("Location: ../../index.php");
    exit();
}

$department = $_SESSION['Department'] ?? 'GC';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Z-Kiosk Pro | Super Admin Dashboard</title>
    <link rel="icon" type="image" href="../../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../style/super_admin/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        .chart-card {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #333;
        }
        
        .chart-card canvas {
            width: 100% !important;
            height: auto !important;
        }
    </script>
    
</head>
<body>
    <div class="app">
        <!-- ===== TOPBAR ===== -->
        <div class="topbar d-flex justify-content-between align-items-center px-4">
            <div class="brand d-flex align-items-center gap-4">
                <button class="sidebar-toggle btn btn-light me-3">
                    <i class="bi bi-list"></i>
                </button>

                <img src="../../assets/images/logo.jpg" class="logo" alt="Ziontech Logo">
                <span class="fw-semibold text-white fs-5">Z-Kiosk Pro | Super Admin</span>
            </div>
            <!-- USER DROPDOWN -->
            <div class="dropdown2">
                <button class="btn btn-light dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i> Administrator
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <button id="openResetModal" class="dropdown-item text-primary fw-semibold">
                            <i class="bi bi-gear me-2"></i> System Reset
                        </button>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger fw-semibold" href="../../model/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <nav>
                <a class="nav-link active" data-target="dashboard">Dashboard</a>
                <a class="nav-link" data-target="reports">Reports</a>
                <a class="nav-link" data-target="accounts">Accounts</a>
                <a class="nav-link" data-target="announcement">Advertisement</a>
                <a class="nav-link" data-target="change">My Profile</a>

            </nav>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="main-content p-4">

            <!-- DASHBOARD -->
            <section id="dashboard" class="page-section active">
                <h2 class="text-center fw-bold mb-5">DASHBOARD</h2>
                <!--<div class="row g-4 justify-content-center text-center mb-4">-->
                <!--    <div class="stats-grid">-->
                <!--        <div class="stat-card">-->
                <!--            <div class="stat-title">Total Queue</div>-->
                <!--            <div class="stat-value" id="totalQueue">100</div>-->
                <!--        </div>-->

                <!--        <div class="stat-card">-->
                <!--            <div class="stat-title">Queue No. Served</div>-->
                <!--            <div class="stat-value" id="queueServed">10</div>-->
                <!--        </div>-->

                <!--        <div class="stat-card">-->
                <!--            <div class="stat-title">Total No. Skipped</div>-->
                <!--            <div class="stat-value" id="totalSkipped">1</div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
                <!--<div class="row g-4 text-center" id="deptStats"></div>-->
                
                <div class="row g-4 mt-4">
                <!-- Column Chart -->
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5 class="chart-title">Total Served and Skipped Per Month</h5>
                        <canvas id="columnChart"></canvas>
                        <p class="chart-description text-muted mt-2" style="font-size:0.9rem;">
                            <i class="bi bi-bar-chart-fill me-1"></i>
                            Shows the monthly totals of <strong>served</strong> and <strong>skipped tickets</strong> across all departments, helping track performance trends over time.
                        </p>
                    </div>
                </div>
                
                <!-- Pie Chart: Total Per Departments -->
                <div class="col-md-3">
                    <div class="chart-card">
                        <h5 class="chart-title">Total Per Departments</h5>
                        <canvas id="pieChart2"></canvas>
                        <p class="chart-description text-muted mt-2" style="font-size:0.9rem;">
                            <i class="bi bi-diagram-3-fill me-1"></i>
                            Shows the <strong>all-time total tickets</strong> including served and skipped, for each department. Provides a clear view of departmental workload distribution.
                        </p>
                    </div>
                </div>
            
                <!-- Pie Chart: Total Ticket / Served / Skipped -->
                <div class="col-md-3">
                    <div class="chart-card">
                        <h5 class="chart-title">Total Ticket / Served / Skipped</h5>
                        <canvas id="pieChart1"></canvas>
                        <p class="chart-description text-muted mt-2" style="font-size:0.9rem;">
                            <i class="bi bi-pie-chart-fill me-1"></i>
                            Summarizes the <strong>all-time totals</strong> for the system: total tickets, tickets served, and tickets skipped across all departments. Useful for quick system-wide insights.
                        </p>
                    </div>
                </div>
            </div>
            </section>
            

            <!-- REPORTS -->
            <section id="reports" class="page-section">
                <h2 class="text-center fw-bold mb-4">REPORTS</h2>
                <div class="card p-4 mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label>Department</label>
                            <select id="reportDept" class="form-select">
                                <option value="">All</option>
                                <option value="GC">GC</option>
                                <option value="MCHS">MCHS</option>
                                <option value="LS">LS</option>
                                <option value="DS">DS</option>
                                <option value="PDA">PDA</option>
                                <option value="ABTC">ABTC</option>
                                <option value="MR">MR</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Status</label>
                            <select id="reportStatus" class="form-select">
                                <option value="All">All</option>
                                <option value="Served">Served</option>
                                <option value="Skipped">Skipped</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Start</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>End</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary flex-fill" id="generateReport">
                                <i class="bi bi-funnel-fill"></i> Generate
                            </button>
                            <div class="dropdown flex-fill">
                                <button class="btn btn-success dropdown-toggle w-100" type="button" id="exportDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-download"></i> Export
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
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
                
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle" id="reportTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Queue</th>
                                    <th>Name</th>
                                    <th>Time Queue</th>
                                    <th>Time Served</th>
                                    <th>Dept</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ACCOUNTS -->
            <section id="accounts" class="page-section">
                <h2 class="text-center fw-bold mb-4">TELLERS' ACCOUNTS</h2>
                <div class="card p-3">
                    <table class="table table-bordered text-center align-middle" id="accountTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <br><br>
                <h2 class="text-center fw-bold mb-4">ADMINS' ACCOUNTS</h2>
                <div class="card p-3">
                    <table class="table table-bordered text-center align-middle" id="adminAccountTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <!-- CHANGE SUPER ADMIN INFO -->
            <section id="change" class="page-section">
                <h2 class="text-center fw-bold mb-4">MY PROFILE</h2>
                <div class="card p-5 mx-auto" style="max-width:550px;">
                    <form id="superProfileForm">
                        <div class="mb-1">
                            <label>First Name</label>
                            <input type="text" name="fname" id="profFname" class="form-control">
                        </div>
                        <div class="mb-1">
                            <label>Last Name</label>
                            <input type="text" name="lname" id="profLname" class="form-control">
                        </div>
                        <div class="mb-1">
                            <label>Username</label>
                            <input type="text" name="username" id="profUsername" class="form-control">
                        </div>
                        <div class="mb-1">
                            <label>Email</label>
                            <input type="email" name="email" id="profEmail" class="form-control">
                        </div>
                        <div class="mb-1">
                            <label>Old Password</label>
                            <input type="password" name="old_password" id="profOldPass" class="form-control">
                        </div>
                        <div class="mb-1">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="profNewPass" class="form-control">
                            <small class="text-muted fst-italic">
                                Choose a password that is easy for you to remember but hard for others to guess.
                            </small>
                        </div>
                    
                        <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.9rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            All fields are optional. Update only the information you wish to change.
                        </div>
                    
                        <button class="btn btn-success w-100">Update Profile</button>
                    </form>
                </div>
            </section>

            <!-- ANNOUNCEMENT & VIDEO -->
            <section id="announcement" class="page-section">
                <h2 class="text-center fw-bold mb-4">ADVERTISEMENT</h2>

                <div class="row g-4">
                    <!-- VIDEO SETTINGS -->
                    <div class="col-lg-6">
                        <div class="card p-4">
                            <h5 class="fw-bold mb-3">Video Display</h5>
                            <form id="videoForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="videoFile" class="form-label">Upload New Video</label>
                                    <input type="file" id="videoFile" accept="video/mp4" class="form-control">
                                    <small class="text-muted fst-italic">
                                        Video Format (mp4) only.
                                    </small><br>
                                    <small class="text-muted fst-italic">
                                        * Refresh the Tv Display after saving.
                                    </small><br>
                                    <small class="text-muted fst-italic">
                                        * Max File Upload Size ( 500 MB ).
                                    </small>
                                </div>
                                <button id="uploadBtn" class="btn btn-primary mt-2">Upload Video</button>
                                <div class="progress mt-3" style="height: 20px; display:none;">
                                    <div id="uploadProgress" class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MARQUEE SETTINGS -->
                    <div class="col-lg-6">
                        <div class="card p-4">
                            <h5 class="fw-bold mb-3">Announcements</h5>
                            <form id="marqueeForm">
                                <div id="marqueeList">
                                    <div class="input-group mb-2">
                                        <input type="text" name="marquee[]" class="form-control">
                                        <button type="button" class="btn btn-danger removeMarquee"><i
                                                class="bi bi-trash"></i></button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" name="marquee[]" class="form-control">
                                        <button type="button" class="btn btn-danger removeMarquee"><i
                                                class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                                <button type="button" id="addMarquee" class="btn btn-secondary w-100 mb-2"><i
                                        class="bi bi-plus-circle"></i> Add New</button>
                                <button type="submit" class="btn btn-success w-100">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- ========== ACCOUNT VIEW MODAL ========== -->
    <div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content account-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">View Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="accountForm">
                    <div class="modal-body">
                        <input type="hidden" id="accountId" name="id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="accFname" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="accLname" name="last_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="accUsername" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="accPassword" name="password"
                                placeholder="••••••">
                            <small class="text-muted fst-italic">
                                Choose a password that is easy for you to remember but hard for others to guess.
                            </small>
                        </div>

                        <!-- Reminder Message -->
                        <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 0.9rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            All fields are optional. Update only the information you wish to change.
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-success" id="saveAccountBtn">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SETTINGS MODAL -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title fw-semibold">
              <i class="bi bi-gear-fill me-2"></i> System Settings
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
    
          <div class="modal-body">
            <p class="mb-2 text-danger fw-bold">
              ⚠ WARNING: You are about to reset the entire queueing data.
            </p>
            <p class="mb-0">
              This action will:
              <ul class="mb-0">
                <li>Delete all tickets</li>
                <li>Reset daily counters</li>
                <li>Clear report logs</li>
                <li>Clear page triggers</li>
              </ul>
            </p>
            <p class="mt-2 fw-semibold">Reminder: Please make sure to export all reports before proceeding.</p>
            <p class="mt-2 fw-semibold">This cannot be undone.</p>
          </div>
    
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-danger fw-bold" id="resetConfirmBtn">
              <i class="bi bi-trash3 me-1"></i> Reset System
            </button>
          </div>
    
        </div>
      </div>
    </div>
    
    <!-- ========== ADMIN ACCOUNT VIEW MODAL ========== -->
    <div class="modal fade" id="adminAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content account-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">View Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="adminAccountForm">
                    <div class="modal-body">
                        <input type="hidden" id="accountIdAdmin" name="idAdmin">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="accFnameAdmin" name="first_nameAdmin" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="accLnameAdmin" name="last_nameAdmin" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="accUsernameAdmin" name="usernameAdmin" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="accPasswordAdmin" name="passwordAdmin"
                                placeholder="••••••">
                            <small class="text-muted fst-italic">
                                Choose a password that is easy for you to remember but hard for others to guess.
                            </small>
                        </div>

                        <!-- Reminder Message -->
                        <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 0.9rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            All fields are optional. Update only the information you wish to change.
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-success" id="saveAdminAccountBtn">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Export libraries -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="../../controller/super_admin/admin_dashboard.js"></script>
    <script>
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
    
    // Settings for reset
    // === FIRST PASSWORD BEFORE SHOWING MODAL ===
    document.getElementById("openResetModal").addEventListener("click", async function () {
        let pwd = prompt("Enter your password to proceed:");
    
        if (!pwd) return;
    
        let res = await fetch("../../model/verify_password.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "password=" + encodeURIComponent(pwd)
        });
    
        let result = await res.text();
    
        if (result === "OK") {
            let modal = new bootstrap.Modal(document.getElementById("settingsModal"));
            modal.show();
        } else {
            alert("Incorrect password!");
        }
    });

    
    document.getElementById("resetConfirmBtn").addEventListener("click", async function () {
        if (!confirm("Final confirmation: Reset entire system? This cannot be undone.")) return;
    
        let pwd = prompt("Enter your password again to confirm reset:");
    
        if (!pwd) return;
    
        let verify = await fetch("../../model/verify_password.php", {
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body: "password=" + encodeURIComponent(pwd)
        });
    
        let check = await verify.text();
    
        if (check !== "OK") {
            alert("Incorrect password. Reset aborted.");
            return;
        }
    
        let reset = await fetch("../../model/reset_system.php", { method:"POST" });
        let msg = await reset.text();
        alert(msg);
        location.reload();
    });
    </script>
</body>

</html>