// === GLOBAL ERROR PROTECTION ===
window.addEventListener("error", e => console.warn("SuperAdmin JS Error:", e.message));
window.addEventListener("unhandledrejection", e => console.warn("Promise Error:", e.reason));

// === SAFE FETCH WRAPPER ===
async function safeFetch(url, options = {}) {
    try {
        const res = await fetch(url, { cache: "no-store", ...options });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const type = res.headers.get("content-type") || "";
        if (type.includes("application/json")) return await res.json();
        return await res.text();
    } catch (err) {
        console.warn("Fetch failed:", url, err.message);
        return null;
    }
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

// === ALERT POPUP ===
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
    </div>`;
    document.body.appendChild(alertBox);
    setTimeout(() => {
        alertBox.style.opacity = 0;
        alertBox.style.transform = "translateY(20px)";
        setTimeout(() => alertBox.remove(), 400);
    }, 2500);
}

function formatTime(dateString) {
    if (!dateString) return "";
    const d = new Date(dateString);
    if (isNaN(d)) return dateString;

    let h = d.getHours();
    const m = d.getMinutes().toString().padStart(2, "0");
    const ampm = h >= 12 ? "PM" : "AM";
    h = h % 12 || 12;

    return `${h}:${m} ${ampm}`;
}

document.getElementById("uploadBtn").onclick = async (e) => {
    e.preventDefault();

    const file = document.getElementById("videoFile").files[0];
    if (!file) return showAlert("Please choose an mp4 video file.", false);

    const maxSize = Infinity; // 1GB limit
    if (file.size > maxSize) return showAlert("File is too large!", false);

    const chunkSize = 5 * 1024 * 1024; // 5MB
    const totalChunks = Math.ceil(file.size / chunkSize);
    let offset = 0;

    const progressContainer = document.querySelector(".progress");
    const bar = document.getElementById("uploadProgress");

    progressContainer.style.display = "block";
    bar.style.width = "0%";
    bar.textContent = "0%";

    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {

        const chunk = file.slice(offset, offset + chunkSize);

        let formData = new FormData();
        formData.append("chunk", chunk);
        formData.append("name", file.name);
        formData.append("offset", offset);

        // === VERY IMPORTANT: await prevents collisions ===
        let res = await fetch("../../model/super_admin/upload_chunk.php", {
            method: "POST",
            body: formData
        });

        offset += chunkSize;

        let percent = Math.floor((chunkIndex + 1) / totalChunks * 100);
        bar.style.width = percent + "%";
        bar.textContent = percent + "%";
    }

    // === Merge the chunks ===
    await fetch("../../model/super_admin/merge_chunks.php?name=" + encodeURIComponent(file.name));

    bar.style.width = "100%";
    bar.classList.add("bg-success");
    bar.textContent = "Upload Complete!";

    showAlert("Video uploaded successfully!", true);
};

// === MAIN DASHBOARD SCRIPT ===
document.addEventListener("DOMContentLoaded", async () => {
    const navLinks = document.querySelectorAll(".nav-link");
    const sections = document.querySelectorAll(".page-section");

    // ===== NAVIGATION =====
    navLinks.forEach(link => {
        link.addEventListener("click", () => {
            navLinks.forEach(l => l.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));
            link.classList.add("active");
            document.getElementById(link.dataset.target)?.classList.add("active");
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    });
    
    // ====== DRAW CHARTS ======
    function drawCharts(data) {

    // === COLUMN CHART (SERVED & SKIPPED PER MONTH) ===
    const columnCtx = document.getElementById('columnChart');
        new Chart(columnCtx, {
            type: 'bar',
            data: {
                labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
                datasets: [
                    {
                        label: 'Served',
                        data: data.monthly.served,
                        backgroundColor: '#1ABC9C',
                        borderRadius: 6
                    },
                    {
                        label: 'Skipped',
                        data: data.monthly.skipped,
                        backgroundColor: '#F5C518',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    
    
        // === PIE CHART #1 (TOTAL, SERVED, SKIPPED) ===
        const pie1Ctx = document.getElementById('pieChart1');
        const pie1Data = [data.total || 0, data.served || 0, data.skipped || 0];
        
        // If all are zero, fill with placeholder 1 to render a blank chart
        const pie1RenderData = pie1Data.every(val => val === 0) ? [1, 1, 1] : pie1Data;
        
        new Chart(pie1Ctx, {
            type: 'pie',
            data: {
                labels: ["Total Ticket", "Served", "Skipped"],
                datasets: [{
                    data: pie1RenderData,
                    backgroundColor: ['#4B0082', '#1ABC9C', '#F5C518'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = pie1Data[context.dataIndex];
                                return context.label + ": " + (val || 0);
                            }
                        }
                    }
                }
            }
        });
        
        // === PIE CHART #2 (DEPARTMENTS) ===
        const pie2Ctx = document.getElementById('pieChart2');
        let deptLabels = Object.keys(data.departments);
        let deptValues = Object.values(data.departments);
        
        // If no departments or all counts are zero, show placeholder
        if (deptLabels.length === 0 || deptValues.every(v => v === 0)) {
            deptLabels = ['No Data'];
            deptValues = [1];
        }
        
        new Chart(pie2Ctx, {
            type: 'pie',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptValues,
                    backgroundColor: [
                        '#4B0082','#1ABC9C','#F5C518',
                        '#27AE60','#3498DB','#E67E22','#9B59B6','#BDC3C7'
                    ],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = deptValues[context.dataIndex];
                                return context.label + ": " + (val === 1 && deptLabels[0] === 'No Data' ? 0 : val);
                            }
                        }
                    }
                }
            }
        });
    }

    // ===== DASHBOARD STATS =====
    const data = await safeFetch("../../model/super_admin/fetch_dashboard_data.php");
    if (data) {
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
        const container = document.getElementById("deptStats");
        if (container && data.departments) {
            container.innerHTML = "";
            for (const [dept, count] of Object.entries(data.departments)) {
                container.innerHTML += `
          <div class="col-md-2 col-6">
            <div class="card stat-card">
              <p>${dept}</p><h3>${count}</h3>
            </div>
          </div>`;
            }
        }
        drawCharts(data);
    } else showAlert("Failed to load dashboard data", false);

    // ===== REPORTS SECTION =====
    const reportTbody = document.querySelector("#reportTable tbody");
    if (reportTbody) {
        reportTbody.innerHTML = `
    <tr><td colspan="8" class="text-center text-muted py-3">
        <i class="bi bi-info-circle me-1"></i> No records yet. Please generate a report.
    </td></tr>`;

        const genBtn = document.getElementById("generateReport");
        genBtn?.addEventListener("click", async () => {
            const dept = document.getElementById("reportDept").value;
            const status = document.getElementById("reportStatus").value;
            const start = document.getElementById("startDate").value;
            const end = document.getElementById("endDate").value;

            if (!start || !end) return showAlert("Please select a start and end date", false);

            reportTbody.innerHTML = `
        <tr><td colspan="8" class="text-center text-secondary py-3">
            <div class="spinner-border spinner-border-sm me-2"></div> Generating report...
        </td></tr>`;

            const data = await safeFetch(`../../model/super_admin/fetch_reports.php?dept=${dept}&start=${start}&end=${end}&status=${status}`);
            if (!data || data.length === 0) {
                reportTbody.innerHTML = `
            <tr><td colspan="8" class="text-center text-muted py-3">
                <i class="bi bi-info-circle me-1"></i> No records found.
            </td></tr>`;
            
            // Reset totals
            document.getElementById("reportTotals").textContent =
                "Total Served: 0 | Total Skipped: 0 | Overall: 0";
            
                return showAlert("No records found for this range", false);
            }

            reportTbody.innerHTML = data.map(r => `
            <tr>
                <td>${r.Date}</td><td>${r.Ticket_ID}</td><td>${r.Name}</td>
                <td>${formatTime(r.Time_Queue)}</td><td>${formatTime(r.Time_Served) || "N/A"}</td>
                <td>${r.Type}</td><td>${r.Status}</td><td>${r.Remarks || ""}</td>
            </tr>`).join("");
            
            window.reportTotals = updateReportTotals(data);

            showAlert("Reports Generated!", true);
        });

        // ===== EXPORT TO EXCEL =====
        document.getElementById("exportExcel")?.addEventListener("click", e => {
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
        document.getElementById("exportPDF")?.addEventListener("click", e => {
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
    }

    // ===== ACCOUNTS SECTION =====
    async function loadAccounts() {
        const data = await safeFetch("../../model/super_admin/fetch_accounts.php");
        const tbody = document.querySelector("#accountTable tbody");
        if (!tbody) return;
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">
        <i class="bi bi-info-circle me-1"></i> No accounts found.</td></tr>`;
            return;
        }
        const deptMap = {
            GC: "General Consultation",
            MCHS: "Maternal & Child Health Services",
            LS: "Laboratory Services",
            DS: "Dental Services",
            PDA: "Pharmacy & Dispensing Area",
            ABTC: "Animal Bite Treatment Center",
            MR: "Medical Records"
        };
        requestAnimationFrame(() => {
            tbody.innerHTML = data.map(a => `
        <tr data-id="${a.Teller_ID}">
          <td>${a.FName} ${a.LName}</td>
          <td>${a.Username}</td><td>********</td>
          <td>${deptMap[a.Department] || a.Department}</td>
          <td><button class="btn btn-primary btn-sm view-account">Edit</button></td>
        </tr>`).join("");
        });
    }
    loadAccounts();

    // ===== EDIT ACCOUNT =====
    document.addEventListener("click", e => {
        if (e.target.classList.contains("view-account")) {
            const row = e.target.closest("tr");
            const id = row.dataset.id;
            const fullName = row.children[0].textContent.trim().split(" ");
            document.getElementById("accountId").value = id;
            document.getElementById("accFname").value = fullName.slice(0, -1).join(" ");
            document.getElementById("accLname").value = fullName.slice(-1).join(" ");
            document.getElementById("accUsername").value = row.children[1].textContent.trim();
            document.getElementById("accPassword").value = "";
            new bootstrap.Modal(document.getElementById("accountModal")).show();
        }
    });

    // ===== SAVE ACCOUNT =====
    document.getElementById("saveAccountBtn")?.addEventListener("click", async () => {
        const form = document.getElementById("accountForm");
        const formData = new FormData(form);
        const resp = await safeFetch("../../model/super_admin/update_account.php", { method: "POST", body: formData });
        if (!resp) return showAlert("Connection lost", false);
        const txt = typeof resp === "string" ? resp : JSON.stringify(resp);
        if (txt.includes("Success")) {
            showAlert("Account Updated Successfully!", true, "bi bi-check-circle-fill");
            form.reset();
            document.querySelector("#accountModal .btn-close")?.click();
            loadAccounts();
        } else if (txt.includes("nochange")) {
            showAlert("No changes were made.", false, "bi bi-info-circle-fill");
        } else {
            showAlert(txt || "Update failed.", false, "bi bi-exclamation-octagon-fill");
        }
    });
    
    // ==============================================================================================
    
    // ===== ADMIN ACCOUNTS SECTION =====
    async function loadAccounts2() {
        const data = await safeFetch("../../model/super_admin/fetch_account_admin.php");
        const tbody = document.querySelector("#adminAccountTable tbody");
        if (!tbody) return;
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">
        <i class="bi bi-info-circle me-1"></i> No accounts found.</td></tr>`;
            return;
        }
        const deptMap = {
            GC: "General Consultation",
            MCHS: "Maternal & Child Health Services",
            LS: "Laboratory Services",
            DS: "Dental Services",
            PDA: "Pharmacy & Dispensing Area",
            ABTC: "Animal Bite Treatment Center",
            MR: "Medical Records"
        };
        requestAnimationFrame(() => {
            tbody.innerHTML = data.map(a => `
        <tr data-id="${a.ID}">
          <td>${a.FName} ${a.Lname}</td>
          <td>${a.Username}</td><td>********</td>
          <td>${deptMap[a.Department] || a.Department}</td>
          <td><button class="btn btn-primary btn-sm view-AdminAccount">Edit</button></td>
        </tr>`).join("");
        });
    }
    loadAccounts2();
    
    // ===== EDIT ACCOUNT =====
    document.addEventListener("click", e => {
        if (e.target.classList.contains("view-AdminAccount")) {
            const row2 = e.target.closest("tr");
            const id2 = row2.dataset.id;
            const fullName2 = row2.children[0].textContent.trim().split(" ");
            document.getElementById("accountIdAdmin").value = id2;
            document.getElementById("accFnameAdmin").value = fullName2.slice(0, -1).join(" ");
            document.getElementById("accLnameAdmin").value = fullName2.slice(-1).join(" ");
            document.getElementById("accUsernameAdmin").value = row2.children[1].textContent.trim();
            document.getElementById("accPasswordAdmin").value = "";
            new bootstrap.Modal(document.getElementById("adminAccountModal")).show();
        }
    });

    // ===== SAVE ACCOUNT =====
    document.getElementById("saveAdminAccountBtn")?.addEventListener("click", async () => {
        const form = document.getElementById("adminAccountForm");
        const formData = new FormData(form);
        const resp = await safeFetch("../../model/super_admin/update_account_admin.php", { method: "POST", body: formData });
        if (!resp) return showAlert("Connection lost", false);
        const txt = typeof resp === "string" ? resp : JSON.stringify(resp);
        if (txt.includes("Success")) {
            showAlert("Account Updated Successfully!", true, "bi bi-check-circle-fill");
            form.reset();
            document.querySelector("#adminAccountModal .btn-close")?.click();
            loadAccounts2();
        } else if (txt.includes("nochange")) {
            showAlert("No changes were made.", false, "bi bi-info-circle-fill");
        } else {
            showAlert(txt || "Update failed.", false, "bi bi-exclamation-octagon-fill");
        }
    });
    
    // ==============================================================================================

    // ===== SUPER ADMIN PROFILE =====
    async function loadSuperAdminProfile() {
        const data = await safeFetch("../../model/super_admin/fetch_super_admin.php");
        if (!data || data.error) return showAlert("Failed to load profile", false);
    
        document.getElementById("profFname").value = data.FName;
        document.getElementById("profLname").value = data.LName;
        document.getElementById("profUsername").value = data.Username;
        document.getElementById("profEmail").value = data.Email;
    }
    loadSuperAdminProfile();
    
    document.getElementById("superProfileForm")?.addEventListener("submit", async e => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const resp = await safeFetch("../../model/super_admin/update_super_admin_password.php", { method: "POST", body: formData });
        if (!resp) return showAlert("Connection lost", false);
    
        const text = typeof resp === "string" ? resp : JSON.stringify(resp);
    
        if (text.includes("Success")) {
            showAlert("Profile Updated Successfully!", true, "bi bi-check-circle-fill");
            e.target.reset();
            loadSuperAdminProfile();
        } else if (text.includes("incorrect")) {
            showAlert("Old password incorrect", false);
        } else if (text.includes("same")) {
            showAlert("New password cannot be the same", false);
        } else if (text.includes("nochange")) {
            showAlert("No changes were made.", false, "bi bi-info-circle-fill");
        } else if (text.includes("username_exists")) {
            showAlert("Username already taken!", false);
        } else if (text.includes("email_exists")) {
            showAlert("Email already in use!", false);
        } else {
            showAlert("Failed to update profile.", false, "bi bi-exclamation-octagon-fill");
        }
    });


    // === LOAD EXISTING MARQUEE TEXTS (robust) ===
    async function loadMarquee() {
        const container = document.getElementById("marqueeList");
        container.innerHTML = `<p class="text-muted">Loading...</p>`;
    
        // candidate paths to try (order: most-likely first)
        const pathsToTry = [
            "ads/marquee.json",                           // current relative path you used
            "../../ads/marquee.json",                    // two levels up
            "../../model/super_admin/ads/marquee.json",  // if ads lives in model/super_admin
            "/ads/marquee.json"                          // site-root
        ];
    
        let data = null;
        let usedPath = null;
    
        for (const p of pathsToTry) {
            try {
                const res = await safeFetch(p);
                if (!res) { console.warn("marquee fetch returned null for", p); continue; }
    
                // If safeFetch returned an object/array already, accept it
                if (typeof res === "object") {
                    data = res;
                    usedPath = p;
                    break;
                }
    
                // If safeFetch returned text, try to parse it as JSON
                if (typeof res === "string") {
                    try {
                        const parsed = JSON.parse(res);
                        if (Array.isArray(parsed)) {
                            data = parsed;
                            usedPath = p;
                            break;
                        } else {
                            // if parsed but not array, try to normalize (maybe file has {items: []})
                            if (parsed && Array.isArray(parsed.marquee)) {
                                data = parsed.marquee;
                                usedPath = p;
                                break;
                            }
                        }
                    } catch (err) {
                        // not JSON, skip
                        console.warn("Could not parse marquee at", p, err.message);
                        continue;
                    }
                }
            } catch (err) {
                console.warn("Error fetching marquee at", p, err.message);
            }
        }
    
        container.innerHTML = ""; // clear the loading placeholder
    
        // If still nothing found, show one empty input
        if (!data || !Array.isArray(data) || data.length === 0) {
            container.innerHTML = `
                <div class="input-group mb-2">
                    <input type="text" name="marquee[]" class="form-control" placeholder="Enter announcement...">
                    <button type="button" class="btn btn-danger removeMarquee"><i class="bi bi-trash"></i></button>
                </div>`;
            console.warn("No marquees loaded (checked paths):", pathsToTry.join(", "));
            return;
        }
    
        // Build inputs from array
        data.forEach(text => {
            // escape double quotes and new lines for safe insertion
            const safeText = String(text).replace(/"/g, "&quot;").replace(/\n/g, " ");
            container.innerHTML += `
                <div class="input-group mb-2">
                    <input type="text" name="marquee[]" class="form-control" value="${safeText}">
                    <button type="button" class="btn btn-danger removeMarquee"><i class="bi bi-trash"></i></button>
                </div>`;
        });
    
        console.log("Loaded marquee from:", usedPath, "items:", data.length);
    }
    
    // call it (within DOMContentLoaded)
    loadMarquee();

    
    document.addEventListener("click", function(e) {
        if (e.target.closest(".removeMarquee")) {
            e.target.closest(".input-group").remove();
        }
    });
    
    // ===== ANNOUNCEMENTS =====
    document.addEventListener("click", e => {
        if (e.target.id === "addMarquee") {
            const list = document.getElementById("marqueeList");
            const div = document.createElement("div");
            div.className = "input-group mb-2";
            div.innerHTML = `
        <input type="text" name="marquee[]" class="form-control" placeholder="Enter announcement...">
        <button type="button" class="btn btn-danger removeMarquee"><i class="bi bi-trash"></i></button>`;
            list.appendChild(div);
        }
        if (e.target.closest(".removeMarquee")) {
            e.target.closest(".input-group").remove();
        }
    });

    document.getElementById("marqueeForm")?.addEventListener("submit", async e => {
        e.preventDefault();
        const data = await safeFetch("../../model/super_admin/save_marquee.php", { method: "POST", body: new FormData(e.target) });
        if (data && data.message) showAlert(data.message, true);
    });

    document.getElementById("videoForm")?.addEventListener("submit", async e => {
        e.preventDefault();
        const data = await safeFetch("../../model/super_admin/upload_video.php", { method: "POST", body: new FormData(e.target) });
        if (data && data.message) showAlert(data.message, true);
    });
});
