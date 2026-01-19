document.addEventListener('DOMContentLoaded', () => {
    console.log('Absence System SPA Loaded');

    // --- CLEANUP LEGACY DATA ---
    localStorage.removeItem('absencesData');

    // --- NAVIGATION LOGIC ---
    const navLinks = document.querySelectorAll('.nav-item');
    const pages = document.querySelectorAll('.page-view');

    function navigateTo(pageId) {
        // Hide all pages
        pages.forEach(page => page.classList.remove('active'));
        // Show target
        const target = document.getElementById(`page-${pageId}`);
        if (target) {
            target.classList.add('active');
            
            // Refresh specific page data if needed
            if (pageId === 'dashboard') loadDashboardStats();
            if (pageId === 'absences') renderAbsences(); // Re-render table to ensure fresh data
        }

        // Update Nav
        navLinks.forEach(link => {
            if (link.dataset.page === pageId) link.classList.add('active');
            else link.classList.remove('active');
        });
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const pageId = link.dataset.page;
            if (pageId) navigateTo(pageId);
        });
    });

    // --- MOBILE SIDEBAR ---
    const mobileToggleButtons = document.querySelectorAll('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    mobileToggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });


    // --- UPLOAD LOGIC ---
    const uploadZone = document.querySelector('.upload-zone');
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.csv';

    if (uploadZone) {
        uploadZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => {
            if (e.target.files[0]) processCSV(e.target.files[0]);
        });
        
        // Drag & Drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = 'hsl(var(--primary))';
            uploadZone.style.background = 'hsl(var(--primary) / 0.1)';
        });
        uploadZone.addEventListener('dragleave', (e) => {
             e.preventDefault();
             uploadZone.style.borderColor = '';
             uploadZone.style.background = '';
        });
        uploadZone.addEventListener('drop', (e) => {
             e.preventDefault();
             uploadZone.style.borderColor = '';
             uploadZone.style.background = '';
             const file = e.dataTransfer.files[0];
             if (file && file.name.endsWith('.csv')) processCSV(file);
        });
    }


    // --- NEW ABSENCE MODAL LOGIC ---
    const btnNewAbsence = document.getElementById('btn-new-absence');
    const modalNewAbsence = document.getElementById('new-absence-modal');
    const formNewAbsence = document.getElementById('new-absence-form');

    if (btnNewAbsence) {
        btnNewAbsence.addEventListener('click', () => {
            if (modalNewAbsence) modalNewAbsence.style.display = 'flex';
        });
    }

    window.closeNewAbsenceModal = function() {
        if (modalNewAbsence) modalNewAbsence.style.display = 'none';
        if (formNewAbsence) formNewAbsence.reset();
    };

    if (formNewAbsence) {
        formNewAbsence.addEventListener('submit', (e) => {
            e.preventDefault();
            // Gather data
            const name = document.getElementById('new-name').value;
            const className = document.getElementById('new-class').value;
            const date = document.getElementById('new-date').value;
            const dateFormatted = date.split('-').reverse().join('/'); 
            const start = document.getElementById('new-start').value;
            const end = document.getElementById('new-end').value;
            const justified = document.getElementById('new-justified').value === "Oui" ? "Oui" : "Non"; // Store as string for CSV consistency

            const newEntry = {
                name: name,
                class: className,
                date: dateFormatted,
                start: start,
                end: end,
                justified: justified,
                source: 'manual'
            };

            // Save
            let currentData = getAbsencesData();
            // Optional: fallback mock logic is handled in getAbsencesData if empty, but we want to append to IT.
            // Actually getAbsencesData will return defaults if empty.
            
            currentData.unshift(newEntry);
            sessionStorage.setItem('absencesData', JSON.stringify(currentData));

            alert('Absence enregistrée avec succès !');
            closeNewAbsenceModal();
            loadDashboardStats();
            // If on absences page, logic there will refresh if we navigating or calling render
            if (document.getElementById('page-absences').classList.contains('active')) renderAbsences();
        });
    }


    // --- SEND PAGE TABS ---
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content-send');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active from all
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.style.display = 'none');
            // Activate clicked
            btn.classList.add('active');
            const tabId = btn.dataset.tab;
            document.getElementById(`tab-${tabId}`).style.display = 'block';
        });
    });
    
    // Simulation Send
    const sendBtn = document.getElementById('btn-send-sim');
    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            const original = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
            this.disabled = true;
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-check"></i> Envoyé !';
                this.style.background = 'hsl(var(--success))';
                setTimeout(() => {
                    this.innerHTML = original;
                    this.disabled = false;
                    this.style.background = '';
                }, 2000);
            }, 1000);
        });
    }


    // --- ABSENCES PAGE LOGIC (Globally Scoped for SPA) ---
    // Variables
    let currentPage = 1;
    let rowsPerPage = 10;
    let currentSort = { column: 'date', direction: 'desc' };
    let filteredData = []; // Internal state for table

    // Elements
    const tbody = document.getElementById('absences-table-body');
    const entriesInfo = document.getElementById('entries-info');
    const pageDisplay = document.getElementById('page-display');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    
    // Filters
    const searchInput = document.getElementById('search-input');
    const classSelect = document.getElementById('class-select');
    const statusSelect = document.getElementById('status-select');
    const dateStartInput = document.getElementById('date-start');
    const dateEndInput = document.getElementById('date-end');
    const rowsPerPageSelect = document.getElementById('rows-per-page');
    const sortableHeaders = document.querySelectorAll('th.sortable');

    // Main Render Function for Absences Table
    window.renderAbsences = function() {
        if (!tbody) return;
        
        // 1. Get Data (fresh)
        let allData = getAbsencesData();

        // 2. Filter
        const term = searchInput ? searchInput.value.toLowerCase() : '';
        const classFilter = classSelect ? classSelect.value : 'Toutes les classes';
        const statusFilter = statusSelect ? statusSelect.value : 'Tous';
        const startStr = dateStartInput ? dateStartInput.value : '';
        const endStr = dateEndInput ? dateEndInput.value : '';
        const startDate = startStr ? new Date(startStr) : null;
        const endDate = endStr ? new Date(endStr) : null;

        filteredData = allData.filter(item => {
            const name = (item.name || item[0] || '').toLowerCase();
            const className = (item.class || item[1] || '');
            const justified = (item.justified === true || item.justified === "Oui" || item[7] === "Oui");
            const dateRaw = item.date || item[2] || '';
            const itemDate = parseDate(dateRaw); 

            const matchName = name.includes(term);
            const matchClass = classFilter === 'Toutes les classes' || className === classFilter;
            
            let matchStatus = true;
            if (statusFilter === 'Justifié') matchStatus = justified;
            if (statusFilter === 'Non Justifié') matchStatus = !justified;

            let matchDate = true;
            if (startDate && itemDate) matchDate = matchDate && (itemDate >= startDate);
            if (endDate && itemDate) matchDate = matchDate && (itemDate <= endDate);

            return matchName && matchClass && matchStatus && matchDate;
        });

        // 3. Sort
        filteredData.sort((a, b) => {
            let valA = getVal(a, currentSort.column);
            let valB = getVal(b, currentSort.column);
            
            if (currentSort.column === 'date') {
               valA = parseDate(valA);
               valB = parseDate(valB);
            }
            if (valA < valB) return currentSort.direction === 'asc' ? -1 : 1;
            if (valA > valB) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        });

        // 4. Paginate
        const total = filteredData.length;
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageData = filteredData.slice(start, end);

        // 5. Render
        tbody.innerHTML = '';
        if (pageData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-muted);">Aucun résultat trouvé</td></tr>';
        } else {
            pageData.forEach((item) => {
                // Find index in MASTER array for detail view
                const originalIndex = allData.indexOf(item);

                const tr = document.createElement('tr');
                const name = item.name || item[0] || 'Inconnu';
                const className = item.class || item[1] || '-';
                const dateRaw = item.date || item[2] || '';
                const dateDisplay = dateRaw.includes('-') ? dateRaw.split('-').reverse().join('/') : dateRaw;
                const startVal = item.start || item[3] || '';
                const endVal = item.end || item[4] || '';
                const justified = (item.justified === true || item.justified === "Oui" || item[7] === "Oui");

                tr.innerHTML = `
                    <td><input type="checkbox"></td>
                    <td><div style="font-weight: 500;">${name}</div></td>
                    <td>${className}</td>
                    <td>${dateDisplay}</td>
                    <td>${startVal} - ${endVal}</td>
                    <td><span class="badge ${justified ? 'badge-success' : ''}" style="${!justified ? 'background: #e0e0e0; color: #555;' : ''}">${justified ? 'Oui' : 'Non'}</span></td>
                    <td>
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick="openDetail(${originalIndex})">
                            <i class="fas fa-eye"></i> Détail
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 6. Update Controls
        if (entriesInfo) entriesInfo.textContent = `${total === 0 ? 0 : start + 1}-${Math.min(end, total)} sur ${total}`;
        if (pageDisplay) pageDisplay.textContent = `Page ${currentPage}`;
        if (btnPrev) btnPrev.disabled = currentPage === 1;
        if (btnNext) btnNext.disabled = end >= total;
        
        // Update Sort Icons
        sortableHeaders.forEach(th => {
            const icon = th.querySelector('i');
            if (icon) {
                 if (th.dataset.sort === currentSort.column) {
                    icon.className = currentSort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                    th.style.color = 'hsl(var(--primary))';
                } else {
                    icon.className = 'fas fa-sort';
                    th.style.color = '';
                }
            }
        });
    };

    // Events for Filters
    if (searchInput) searchInput.addEventListener('input', () => { currentPage=1; renderAbsences(); });
    if (classSelect) classSelect.addEventListener('change', () => { currentPage=1; renderAbsences(); });
    if (statusSelect) statusSelect.addEventListener('change', () => { currentPage=1; renderAbsences(); });
    if (dateStartInput) dateStartInput.addEventListener('change', () => { currentPage=1; renderAbsences(); });
    if (dateEndInput) dateEndInput.addEventListener('change', () => { currentPage=1; renderAbsences(); });
    if (rowsPerPageSelect) rowsPerPageSelect.addEventListener('change', (e) => {
        rowsPerPage = parseInt(e.target.value);
        currentPage = 1;
        renderAbsences();
    });
    if (btnPrev) btnPrev.addEventListener('click', () => {
        if (currentPage > 1) { currentPage--; renderAbsences(); }
    });
    if (btnNext) btnNext.addEventListener('click', () => {
        const total = filteredData.length;
        if (currentPage * rowsPerPage < total) { currentPage++; renderAbsences(); }
    });
    sortableHeaders.forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.sort;
            if (currentSort.column === col) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = col;
                currentSort.direction = 'asc';
            }
            renderAbsences();
        });
    });

    // Detail Modal Logic
    window.openDetail = function(index) {
        const allData = getAbsencesData();
        const item = allData[index];
        if (!item) return;

         const name = item.name || item[0];
         const className = item.class || item[1];
         const date = item.date || item[2];
         
        document.getElementById('modal-name').textContent = name;
        document.getElementById('modal-class').textContent = className;
        document.getElementById('modal-email').textContent = item.parentEmail || item[5] || '-';
        document.getElementById('modal-phone').textContent = item.phone || item[6] || '-';
        document.getElementById('modal-date').textContent = date;
        document.getElementById('modal-time').textContent = `${item.start || item[3]} - ${item.end || item[4]}`;
        const isJustified = (item.justified === true || item.justified === "Oui" || item[7] === "Oui");
        document.getElementById('modal-status').textContent = isJustified ? 'Justifié' : 'Non Justifié';

        const modal = document.getElementById('detail-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
        }
    };
    
    window.closeModal = function() {
        const modal = document.getElementById('detail-modal');
        if (modal) modal.style.display = 'none';
    }


    // --- INITIALIZATION ---
    loadDashboardStats();
    // Pre-render absences just in case, though it's hidden
    renderAbsences(); 
});


// --- SHARED FUNCTIONS ---

function getAbsencesData() {
    let data = [];
    const stored = sessionStorage.getItem('absencesData');
    if (stored) {
        data = JSON.parse(stored);
    } else {
        // Start Empty as requested
        data = [];
    }
    return data;
}

function loadDashboardStats() {
    const data = getAbsencesData();
    
    // Update Counts
    const totalElement = document.getElementById('total-absences');
    const pendingElement = document.getElementById('pending-count');
    const rateElement = document.getElementById('notification-rate');
    const tbody = document.getElementById('recent-activity-body');

    if (totalElement) totalElement.textContent = data.length;
    
    const pending = Math.floor(data.length * 0.3); 
    if (pendingElement) pendingElement.textContent = pending;
    
    const rate = Math.floor(((data.length - pending) / data.length) * 100);
    if (rateElement) rateElement.textContent = (isNaN(rate) ? 0 : rate) + '%';

    // Populate Recent Activity (First 5)
    if (tbody) {
        tbody.innerHTML = '';
        const recent = data.slice(0, 5);
        recent.forEach(item => {
            const tr = document.createElement('tr');
            const name = item.name || item[0] || 'Inconnu';
            const className = item.class || item[1] || '-';
            const date = item.date || item[2] || '';
            
            tr.innerHTML = `
                <td>${name}</td>
                <td>${className}</td>
                <td>${date}</td>
                <td><span class="badge badge-success">Envoyé</span></td>
                <td><span class="badge badge-warning">En attente</span></td>
                <td><button class="btn" style="padding: 5px;"><i class="fas fa-ellipsis-v"></i></button></td>
            `;
            tbody.appendChild(tr);
        });
    }
}

function processCSV(file) {
    const pContainer = document.querySelector('.progress-container-demo');
    if (pContainer) {
        pContainer.classList.remove('hidden');
        pContainer.querySelector('.progress-bar').style.width = '100%';
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const rows = text.split('\n');
        const headers = rows[0].split(',').map(h => h.trim());
        const allData = rows.slice(1).filter(r => r.trim() !== '').map(row => {
            const cols = row.split(',').map(c => c.trim());
            return {
                name: cols[0],
                class: cols[1],
                date: cols[2],
                start: cols[3],
                end: cols[4],
                parentEmail: cols[5],
                phone: cols[6],
                justified: cols[7],
                source: 'csv'
            };
        });

        // MERGE DATA
        let currentData = getAbsencesData();
        // Option: Filter out old CSV data? currentData = currentData.filter(d => d.source !== 'csv');
        // Let's just append for now to be safe.
        
        const mergedData = [...currentData, ...allData];
        sessionStorage.setItem('absencesData', JSON.stringify(mergedData));

        // Generate Preview
        generatePreviewTable(headers, rows.slice(1, 6).map(row => row.split(',').map(c => c.trim())));
        
        setTimeout(() => {
            if (pContainer) pContainer.classList.add('hidden');
            const previewCard = document.getElementById('csv-preview-card');
            if (previewCard) previewCard.classList.remove('hidden');
            alert(`Fichier chargé ! ${allData.length} entrées ajoutées.`);
            
            // Refresh Data in other views
            loadDashboardStats();
            renderAbsences();
        }, 500);
    };
    reader.readAsText(file);
}

function generatePreviewTable(headers, data) {
    const table = document.getElementById('csv-preview-table');
    if (!table) return;
    table.innerHTML = '';
    const thead = document.createElement('thead');
    const trHead = document.createElement('tr');
    headers.forEach(h => {
        const th = document.createElement('th');
        th.textContent = h;
        trHead.appendChild(th);
    });
    thead.appendChild(trHead);
    table.appendChild(thead);
    
    const tbody = document.createElement('tbody');
    data.forEach(row => {
        if (row.length > 1) {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        }
    });
    table.appendChild(tbody);
}

// Helpers
function getVal(item, col) {
    if (col === 'name') return (item.name || item[0] || '').toLowerCase();
    if (col === 'class') return (item.class || item[1] || '').toLowerCase();
    if (col === 'date') return (item.date || item[2] || '');
    if (col === 'justified') return (item.justified === true || item.justified === "Oui");
    return '';
}

function parseDate(dateStr) {
    if (!dateStr) return null;
    if (dateStr.includes('-')) return new Date(dateStr);
    const parts = dateStr.split('/');
    if (parts.length === 3) return new Date(parts[2], parts[1] - 1, parts[0]);
    return null;
}
