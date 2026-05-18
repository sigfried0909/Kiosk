// ========== ALERT POPUP ==========
function showAlert(message, success = true, icon = null) {
    const alertBox = document.createElement("div");
    alertBox.className = "custom-alert";
    alertBox.style.background = success
        ? "linear-gradient(90deg, #1d56a3, #2878d4)"
        : "linear-gradient(90deg, #e74c3c, #f17a5d)";
    alertBox.innerHTML = `
        <div style="display:flex;align-items:center;gap:10px;">
            ${icon ? `<i class="${icon}" style="font-size:1.3rem;"></i>` : ""}
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(alertBox);

    setTimeout(() => {
        alertBox.style.opacity = 0;
        alertBox.style.transform = "translateY(20px)";
        setTimeout(() => alertBox.remove(), 400);
    }, 2500);
}

// report total
function updateReportTotals(data) {
    let served = 0;
    let skipped = 0;

    data.forEach(r => {
        if (r.Status === "Served") served++;
        if (r.Status === "Skipped") skipped++;
    });

    let total = data.length;

    document.getElementById("reportTotals").textContent =
        `Total Served: ${served} | Total Skipped: ${skipped} | Overall: ${total}`;

    // Return values for exports
    return { served, skipped, total };
}

// ========== MAIN ADMIN DASHBOARD ==========
document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".nav-link");
    const sections = document.querySelectorAll(".page-section");

    // ===== NAVIGATION =====
    navLinks.forEach(link => {
        link.addEventListener("click", () => {
            navLinks.forEach(l => l.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));
            link.classList.add("active");
            document.getElementById(link.dataset.target).classList.add("active");
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    });

    // ===== DASHBOARD STATS =====
    fetch(`../../model/admin/fetch_dashboard_data.php?department=${department}`)
        .then(res => res.json())
        .then(data => {
            const animateNumber = (id, target) => {
                const el = document.getElementById(id);
                if (!el) return;
                let count = 0;
                const step = target / 50;
                const interval = setInterval(() => {
                    count += step;
                    el.textContent = Math.floor(count);
                    if (count >= target) {
                        el.textContent = target;
                        clearInterval(interval);
                    }
                }, 20);
            };

            animateNumber("totalQueue", data.total);
            animateNumber("queueServed", data.served);
            animateNumber("totalSkipped", data.skipped);
        })
        .catch(() => showAlert("Failed to load dashboard data", false));
    
    // ===== REPORTS SECTION =====
    document.getElementById("generateReport").addEventListener("click", () => {
    const start = document.getElementById("startDate").value;
    const end = document.getElementById("endDate").value;
    const statusFilter = document.getElementById("reportStatus").value;

    if (!start || !end) {
        showAlert("Please select a start and end date", false);
        return;
    }

    fetch(`../../model/admin/fetch_reports.php?department=${department}&start=${start}&end=${end}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                document.querySelector("#reportTable tbody").innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            <i class="bi bi-info-circle me-1"></i> No records found.
                        </td>
                    </tr>`;
                    
                    // Reset totals
                    document.getElementById("reportTotals").textContent =
                        "Total Served: 0 | Total Skipped: 0 | Overall: 0";
                return showAlert("No records found.", false);
            }

            // ===== Filter by Status =====
            let filteredData = data;
            if (statusFilter !== "All") {
                filteredData = data.filter(r => r.Status === statusFilter);
            }

            const tbody = document.querySelector("#reportTable tbody");
            tbody.innerHTML = filteredData.map(r => `
                <tr>
                    <td>${r.Date}</td>
                    <td>${r.Ticket_ID}</td>
                    <td>${r.Name}</td>
                    <td>${formatTime(r.Time_Queue)}</td>
                    <td>${r.Time_Served ? formatTime(r.Time_Served) : 'N/A'}</td>
                    <td>${r.Status}</td>
                    <td>${r.Remarks || ''}</td>
                </tr>
            `).join("");
            
            window.reportTotals = updateReportTotals(data);

            showAlert("Reports Generated!");
        })
        .catch(() => showAlert("Failed to generate report", false));
    });
    
    // ===== Helper to format time only =====
    function formatTime(dateStr) {
        const date = new Date(dateStr);
        if (isNaN(date)) return '';
        let hours = date.getHours();
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }


    // ===== EXPORT TO EXCEL =====
    document.getElementById("exportExcel").addEventListener("click", e => {
        e.preventDefault();
        const table = document.getElementById("reportTable");
        const tbody = table.querySelector("tbody");
        const rows = tbody.querySelectorAll("tr");

        if (rows.length === 0) {
            showAlert("No report data to export.", false, "bi bi-exclamation-triangle-fill");
            return;
        }

        const wb = XLSX.utils.book_new();
        const wsData = [];

        const headers = Array.from(table.querySelectorAll("thead th")).map(th => th.textContent.trim());
        wsData.push(headers);

        rows.forEach(row => {
            const rowData = Array.from(row.children).map((td, idx) => {
                let text = td.textContent.trim();
                const header = headers[idx].toLowerCase();
                if ((header.includes("time queue") || header.includes("time served")) && text && text !== "N/A") {
                    const dateObj = new Date(text);
                    if (!isNaN(dateObj)) {
                        const hours = dateObj.getHours();
                        const minutes = dateObj.getMinutes().toString().padStart(2, "0");
                        const ampm = hours >= 12 ? "PM" : "AM";
                        const h12 = hours % 12 || 12;
                        text = `${h12}:${minutes} ${ampm}`;
                    }
                }
                return text;
            });
            wsData.push(rowData);
        });
        
        // INSERT TOTALS AS FOOTER SECTION
            wsData.push([]);
            wsData.push([`Total Served: ${window.reportTotals.served}`]);
            wsData.push([`Total Skipped: ${window.reportTotals.skipped}`]);
            wsData.push([`Total: ${window.reportTotals.total}`]);

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const range = XLSX.utils.decode_range(ws["!ref"]);

        // Style cells
        for (let R = range.s.r; R <= range.e.r; ++R) {
            for (let C = range.s.c; C <= range.e.c; ++C) {
                const cellRef = XLSX.utils.encode_cell({ r: R, c: C });
                if (!ws[cellRef]) continue;
                ws[cellRef].s = {
                    alignment: { horizontal: "center", vertical: "center", wrapText: true },
                    border: {
                        top: { style: "thin", color: { rgb: "999999" } },
                        bottom: { style: "thin", color: { rgb: "999999" } },
                        left: { style: "thin", color: { rgb: "999999" } },
                        right: { style: "thin", color: { rgb: "999999" } }
                    },
                    font: { name: "Calibri", sz: 11 }
                };
            }
        }

        // Header style
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const headerRef = XLSX.utils.encode_cell({ r: 0, c: C });
            if (!ws[headerRef]) continue;
            ws[headerRef].s = {
                font: { bold: true, color: { rgb: "FFFFFF" }, sz: 12 },
                fill: { fgColor: { rgb: "1D56A3" } },
                alignment: { horizontal: "center", vertical: "center" },
                border: {
                    top: { style: "thin", color: { rgb: "999999" } },
                    bottom: { style: "thin", color: { rgb: "999999" } },
                    left: { style: "thin", color: { rgb: "999999" } },
                    right: { style: "thin", color: { rgb: "999999" } }
                }
            };
        }

        ws["!cols"] = headers.map(h => ({ wch: h.length + 4 }));
        ws["!freeze"] = { xSplit: 0, ySplit: 1 };

        XLSX.utils.book_append_sheet(wb, ws, "ZKiosk_Report");
        XLSX.writeFile(wb, "ZKiosk_Reports.xlsx");

        showAlert("Excel exported successfully!", true, "bi bi-file-earmark-excel-fill");
    });

    // ===== EXPORT TO PDF =====
    document.getElementById("exportPDF").addEventListener("click", e => {
        e.preventDefault();

        const table = document.getElementById("reportTable");
        const tbody = table.querySelector("tbody");
        const rows = tbody.querySelectorAll("tr");

        if (rows.length === 0) {
            showAlert("No report data to export.", false, "bi bi-exclamation-triangle-fill");
            return;
        }

        const { jsPDF } = window.jspdf;
        if (!jsPDF) {
            showAlert("PDF export libraries missing.", false, "bi bi-bug-fill");
            return;
        }

        const doc = new jsPDF("l", "pt", "a4");
        const pageWidth = doc.internal.pageSize.getWidth();

        doc.setFont("helvetica", "bold");
        doc.setFontSize(16);
        doc.text("Z-Kiosk Pro Report", pageWidth / 2, 40, { align: "center" });

        const dateStr = new Date().toLocaleString();
        doc.setFontSize(10);
        doc.text(`Generated: ${dateStr}`, pageWidth - 160, 58);

        const headers = Array.from(table.querySelectorAll("thead th")).map(th => th.textContent.trim());
        const data = Array.from(rows).map(row =>
            Array.from(row.children).map((td, idx) => {
                let text = td.textContent.trim();
                const header = headers[idx].toLowerCase();
                if ((header.includes("time queue") || header.includes("time served")) && text && text !== "N/A") {
                    const dateObj = new Date(text);
                    if (!isNaN(dateObj)) {
                        const hours = dateObj.getHours();
                        const minutes = dateObj.getMinutes().toString().padStart(2, "0");
                        const ampm = hours >= 12 ? "PM" : "AM";
                        const h12 = hours % 12 || 12;
                        text = `${h12}:${minutes} ${ampm}`;
                    }
                }
                return text;
            })
        );

        doc.autoTable({
            head: [headers],
            body: data,
            startY: 100, // push table down for header space
            theme: "grid",
            styles: { fontSize: 9, cellPadding: 4, halign: "center", valign: "middle" },
            headStyles: { fillColor: [29, 86, 163], textColor: 255, fontStyle: "bold" },
            alternateRowStyles: { fillColor: [245, 247, 250] },
            margin: { left: 30, right: 30 },
            didDrawPage: function (data) {
                const totalLine = `Total Served: ${window.reportTotals.served} | Total Skipped: ${window.reportTotals.skipped} | Overall: ${window.reportTotals.total}`;
                doc.setFontSize(11);
                doc.setFont("helvetica", "bold");
                doc.text(totalLine, doc.internal.pageSize.width / 2, 75, { align: "center" });
            }
        });

        doc.save("ZKiosk_Reports.pdf");
        showAlert("PDF exported successfully!", true, "bi bi-file-earmark-pdf-fill");
    });

    // ===== ACCOUNT MANAGEMENT =====
    function loadAccounts() {
        fetch("../../model/admin/fetch_accounts.php")
            .then(res => res.json())
            .then(data => {
                const tbody = document.querySelector("#accountTable tbody");
                tbody.innerHTML = data.map(a => `
                    <tr data-id="${a.Teller_ID}">
                        <td>${a.FName} ${a.LName}</td>
                        <td>${a.Username}</td>
                        <td>********</td>
                        <td><button class="btn btn-primary btn-sm view-account">Edit</button></td>
                    </tr>
                `).join("");
            })
            .catch(() => showAlert("Failed to load accounts", false));
    }

    loadAccounts();

    document.addEventListener("click", e => {
        if (e.target.classList.contains("view-account")) {
            const row = e.target.closest("tr");
            const id = row.dataset.id;
            const fullName = row.children[0].textContent.trim().split(" ");
            const firstName = fullName.slice(0, -1).join(" ") || fullName[0];
            const lastName = fullName.slice(-1).join(" ");
            const username = row.children[1].textContent.trim();

            document.getElementById("accountId").value = id;
            document.getElementById("accFname").value = firstName;
            document.getElementById("accLname").value = lastName;
            document.getElementById("accUsername").value = username;
            document.getElementById("accPassword").value = "";

            new bootstrap.Modal(document.getElementById("accountModal")).show();
        }
    });

    document.getElementById("saveAccountBtn").addEventListener("click", () => {
        const form = document.getElementById("accountForm");
        const formData = new FormData(form);
        const id = formData.get("id");
        const fname = formData.get("first_name").trim();
        const lname = formData.get("last_name").trim();
        const username = formData.get("username").trim();
        const password = formData.get("password").trim();
        const rowId = document.getElementById("accountId").value;

        if (!formData.has("Teller_ID")) formData.append("Teller_ID", rowId);

        if (!fname || !lname || !username) {
            showAlert("Please fill in all required fields.", false, "bi bi-exclamation-triangle-fill");
            return;
        }

        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            const originalName = row.children[0].textContent.trim();
            const originalUser = row.children[1].textContent.trim();
            const currentFullName = `${fname} ${lname}`;
            if (originalName === currentFullName && originalUser === username && !password) {
                showAlert("No changes were made.", false, "bi bi-info-circle-fill");
                return;
            }
        }

        fetch("../../model/admin/update_teller_account.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(resp => {
                if (resp.includes("Success")) {
                    showAlert("Account Updated Successfully!", true, "bi bi-check-circle-fill");
                    form.reset();
                    document.querySelector("#accountModal .btn-close").click();
                    loadAccounts();
                } else {
                    showAlert(resp || "Update failed.", false, "bi bi-exclamation-octagon-fill");
                }
            })
            .catch(() => showAlert("Failed to update account.", false, "bi bi-bug-fill"));
    });

    document.getElementById("deleteAccountBtn")?.addEventListener("click", () => {
        const id = document.getElementById("accountId").value;
        if (!id) return showAlert("Invalid Account ID.", false, "bi bi-exclamation-triangle-fill");

        if (!confirm("Are you sure you want to delete this account?")) return;

        fetch("../../model/admin/delete_teller.php", {
            method: "POST",
            body: new URLSearchParams({ id })
        })
            .then(res => res.text())
            .then(resp => {
                if (resp.includes("Success")) {
                    showAlert("Account Deleted Successfully!", true, "bi bi-trash-fill");
                    document.querySelector("#accountModal .btn-close").click();
                    loadAccounts();
                } else {
                    showAlert(resp || "Delete failed.", false, "bi bi-exclamation-octagon-fill");
                }
            })
            .catch(() => showAlert("Error deleting account.", false, "bi bi-bug-fill"));
    });

    // ===== ADMIN PROFILE =====
    function loadAdminProfile() {
        fetch("../../model/admin/fetch_profile.php")
            .then(res => res.json())
            .then(data => {
                if (data.error) return showAlert("Failed to fetch profile.", false, "bi bi-x-circle-fill");
    
                document.getElementById("profFname").value = data.FName;
                document.getElementById("profLname").value = data.LName;
                document.getElementById("profUsername").value = data.Username; // <-- added
            })
            .catch(() => showAlert("Error loading profile.", false, "bi bi-x-circle-fill"));
    }
    
    loadAdminProfile();

    document.getElementById("adminProfileForm").addEventListener("submit", e => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch("../../model/admin/update_profile.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(resp => {
                if (resp.includes("nochange")) {
                    showAlert("No changes were made.", false, "bi bi-info-circle-fill");
                } else if (resp.includes("Success")) {
                    showAlert("Profile Updated Successfully!", true, "bi bi-check-circle-fill");
                    form.reset();
                    loadAdminProfile();
                } else if (resp.includes("incorrect")) {
                    showAlert("Old password incorrect.", false, "bi bi-exclamation-triangle-fill");
                } else if (resp.includes("same")) {
                    showAlert("New password cannot be same.", false, "bi bi-info-circle-fill");
                } else if (resp.includes("username_exists")) {
                    showAlert("Username already taken!", false, "bi bi-exclamation-octagon-fill");
                } else {
                    showAlert(resp || "Update failed.", false, "bi bi-exclamation-octagon-fill");
                }
            })
            .catch(() => showAlert("Failed to update profile.", false, "bi bi-bug-fill"));
    });
});
