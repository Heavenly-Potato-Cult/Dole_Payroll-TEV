<?php $__env->startSection('title', 'My Payslip'); ?>
<?php $__env->startSection('page-title', 'My Payslip'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>My Payslip</h1>
        <p>Your payroll slips from released batches</p>
    </div>
</div>


<div class="stats-row" id="statsRow">
    <div class="stat-card">
        <div class="stat-label">Total Payslips</div>
        <div class="stat-value" id="statTotalPayslips">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Net Pay</div>
        <div class="stat-value" id="statTotalNet">₱0.00</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Gross</div>
        <div class="stat-value" id="statTotalGross">₱0.00</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Deductions</div>
        <div class="stat-value" id="statTotalDeductions">₱0.00</div>
    </div>
</div>


<div class="filters-bar">
    <div class="filter-group">
        <label>Year</label>
        <select id="filterYear" class="filter-select">
            <option value="">All</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = range(now()->year, 2020); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Month</label>
        <select id="filterMonth" class="filter-select">
            <option value="">All</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['January','February','March','April','May','June',
                       'July','August','September','October','November','December']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <option value="<?php echo e($i + 1); ?>"><?php echo e($m); ?></option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Cutoff</label>
        <select id="filterCutoff" class="filter-select">
            <option value="">All</option>
            <option value="1st">1st</option>
            <option value="2nd">2nd</option>
        </select>
    </div>
    
    <div class="filter-group filter-search">
        <label>Search</label>
        <input type="text" id="filterSearch" class="filter-input" placeholder="Period or amount...">
    </div>
    <button id="clearFilters" class="btn-clear">Clear Filters</button>
</div>


<div class="card">
    <div class="card-header">
        <h3>My Payslips</h3>
        <div class="entries-info" id="entriesInfo">Showing 0 to 0 of 0 entries</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="payslipsTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="period">Payroll Period <span class="sort-icon">↕</span></th>
                        <th>Cutoff</th>
                        <th class="sortable" data-sort="gross">Gross Income <span class="sort-icon">↕</span></th>
                        <th class="sortable" data-sort="deductions">Total Deductions <span class="sort-icon">↕</span></th>
                        <th class="sortable" data-sort="net">Net Amount <span class="sort-icon">↕</span></th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    
                </tbody>
            </table>
        </div>

        
        <div class="pagination-wrapper">
            <div class="pagination-per-page">
                <label>Show</label>
                <select id="perPage" class="pagination-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>
            <div class="pagination-controls" id="paginationControls">
                <button class="btn-page" id="prevPage" disabled>← Previous</button>
                <span class="page-numbers" id="pageNumbers"></span>
                <button class="btn-page" id="nextPage" disabled>Next →</button>
            </div>
        </div>
    </div>
</div>


<div class="empty-state" id="emptyState" style="display: none;">
    <div class="empty-icon">📄</div>
    <h3>No payslips found</h3>
    <p>Try adjusting your filters or <a href="#" id="clearFiltersLink">clear all filters</a></p>
</div>


<script>
const payslipsData = [
    <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    {
        id: <?php echo e($entry->id); ?>,
        year: <?php echo e($entry->batch->period_year); ?>,
        month: <?php echo e($entry->batch->period_month); ?>,
        monthName: '<?php echo e(\Carbon\Carbon::createFromFormat('m', $entry->batch->period_month)->format('F')); ?>',
        cutoff: '<?php echo e($entry->batch->cutoff); ?>',
        gross: <?php echo e($entry->gross_income); ?>,
        deductions: <?php echo e($entry->total_deductions); ?>,
        net: <?php echo e($entry->net_amount); ?>,
        status: '<?php echo e($entry->batch->status); ?>',
        payslipUrl: '<?php echo e(route('payroll.payslip', [$entry->batch, $entry])); ?>'
    },
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
];
</script>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ── Stats Row ─────────────────────────────────────────────────── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(15,27,76,0.09);
    border-top: 3px solid var(--navy);
}

.stat-label {
    font-size: 0.73rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--text-light);
    margin-bottom: 6px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--navy);
    line-height: 1;
}

/* ── Filters Bar ───────────────────────────────────────────────── */
.filters-bar {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    padding: 16px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(15,27,76,0.09);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--text-mid);
}

.filter-select,
.filter-input {
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    font-size: 0.9rem;
    font-family: var(--font);
    min-width: 120px;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(15,27,76,0.09);
}

.filter-search {
    flex: 1;
    min-width: 200px;
}

.filter-search .filter-input {
    width: 100%;
}

.btn-clear {
    padding: 8px 16px;
    background: transparent;
    border: 1.5px solid var(--navy);
    border-radius: 6px;
    color: var(--navy);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-clear:hover {
    background: var(--navy-light);
}

/* ── Table ───────────────────────────────────────────────────────── */
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.table th {
    background: var(--surface);
    font-weight: 600;
    color: var(--text-mid);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.table th.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.table th.sortable:hover {
    background: var(--navy-light);
}

.sort-icon {
    font-size: 0.75rem;
    margin-left: 4px;
    opacity: 0.5;
}

.table th.sortable.asc .sort-icon::after {
    content: '↑';
}

.table th.sortable.desc .sort-icon::after {
    content: '↓';
}

.table tbody tr:nth-child(even) {
    background: #fafbfc;
}

.table tbody tr:hover {
    background: var(--navy-light);
}

/* ── Badge ─────────────────────────────────────────────────────── */
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.69rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.badge-released {
    background: var(--gold-light);
    color: #C87800;
}

.badge-locked {
    background: var(--navy-light);
    color: var(--navy);
}

.badge-pending {
    background: #FFF9C4;
    color: #F57F17;
}

/* ── Card Header ────────────────────────────────────────────────── */
.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    background: #FAFBFF;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--navy);
}

.entries-info {
    font-size: 0.85rem;
    color: var(--text-mid);
}

/* ── Pagination ─────────────────────────────────────────────────── */
.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

.pagination-per-page {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--text-mid);
}

.pagination-select {
    padding: 6px 10px;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    font-size: 0.85rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-page {
    padding: 6px 14px;
    background: white;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    color: var(--navy);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-page:hover:not(:disabled) {
    background: var(--navy-light);
    border-color: var(--navy);
}

.btn-page:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.page-numbers {
    display: flex;
    gap: 4px;
}

.page-num {
    padding: 6px 12px;
    background: white;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    color: var(--navy);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.page-num:hover {
    background: var(--navy-light);
}

.page-num.active {
    background: var(--navy);
    color: white;
    border-color: var(--navy);
}

/* ── Empty State ────────────────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(15,27,76,0.09);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    color: var(--text-mid);
    margin-bottom: 8px;
}

.empty-state p {
    color: var(--text-light);
    margin: 0;
}

.empty-state a {
    color: var(--navy);
    text-decoration: underline;
    cursor: pointer;
}

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr 1fr;
    }

    .filters-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group,
    .filter-search {
        width: 100%;
    }

    .pagination-wrapper {
        flex-direction: column;
        gap: 16px;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
let currentPage = 1;
let perPage = 10;
let filteredData = [...payslipsData];
let sortColumn = null;
let sortDirection = 'asc';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
    applyFilters();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('filterYear').addEventListener('change', applyFilters);
    document.getElementById('filterMonth').addEventListener('change', applyFilters);
    document.getElementById('filterCutoff').addEventListener('change', applyFilters);
    document.getElementById('filterSearch').addEventListener('input', applyFilters);
    document.getElementById('clearFilters').addEventListener('click', clearFilters);
    document.getElementById('clearFiltersLink').addEventListener('click', clearFilters);
    document.getElementById('perPage').addEventListener('change', function() {
        perPage = parseInt(this.value);
        currentPage = 1;
        renderTable();
    });
    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });
    document.getElementById('nextPage').addEventListener('click', () => {
        const totalPages = Math.ceil(filteredData.length / perPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    // Sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const column = th.dataset.sort;
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = 'asc';
            }
            document.querySelectorAll('.sortable').forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            th.classList.add(sortDirection);
            applyFilters();
        });
    });
}

function updateStats() {
    const totalPayslips = payslipsData.length;
    const totalNet = payslipsData.reduce((sum, p) => sum + p.net, 0);
    const totalGross = payslipsData.reduce((sum, p) => sum + p.gross, 0);
    const totalDeductions = payslipsData.reduce((sum, p) => sum + p.deductions, 0);

    document.getElementById('statTotalPayslips').textContent = totalPayslips;
    document.getElementById('statTotalNet').textContent = '₱' + totalNet.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('statTotalGross').textContent = '₱' + totalGross.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('statTotalDeductions').textContent = '₱' + totalDeductions.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function applyFilters() {
    const year = document.getElementById('filterYear').value;
    const month = document.getElementById('filterMonth').value;
    const cutoff = document.getElementById('filterCutoff').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();

    filteredData = payslipsData.filter(p => {
        if (year && p.year !== parseInt(year)) return false;
        if (month && p.month !== parseInt(month)) return false;
        if (cutoff && p.cutoff !== cutoff) return false;
        if (search) {
            const searchStr = `${p.year} ${p.monthName} ${p.gross} ${p.net}`.toLowerCase();
            if (!searchStr.includes(search)) return false;
        }
        return true;
    });

    if (sortColumn) {
        filteredData.sort((a, b) => {
            let valA, valB;
            switch (sortColumn) {
                case 'period':
                    valA = `${a.year}-${a.month}`;
                    valB = `${b.year}-${b.month}`;
                    break;
                case 'gross':
                    valA = a.gross;
                    valB = b.gross;
                    break;
                case 'deductions':
                    valA = a.deductions;
                    valB = b.deductions;
                    break;
                case 'net':
                    valA = a.net;
                    valB = b.net;
                    break;
            }
            if (sortDirection === 'asc') {
                return valA > valB ? 1 : -1;
            } else {
                return valA < valB ? 1 : -1;
            }
        });
    }

    currentPage = 1;
    renderTable();
}

function clearFilters(e) {
    if (e) e.preventDefault();
    document.getElementById('filterYear').value = '';
    document.getElementById('filterMonth').value = '';
    document.getElementById('filterCutoff').value = '';
    document.getElementById('filterSearch').value = '';
    sortColumn = null;
    sortDirection = 'asc';
    document.querySelectorAll('.sortable').forEach(h => {
        h.classList.remove('asc', 'desc');
    });
    applyFilters();
}

function renderTable() {
    const tbody = document.getElementById('tableBody');
    const emptyState = document.getElementById('emptyState');
    const card = document.querySelector('.card');

    if (filteredData.length === 0) {
        tbody.innerHTML = '';
        card.style.display = 'none';
        emptyState.style.display = 'block';
        document.getElementById('entriesInfo').textContent = 'Showing 0 to 0 of 0 entries';
        return;
    }

    card.style.display = 'block';
    emptyState.style.display = 'none';

    const start = (currentPage - 1) * perPage;
    const end = Math.min(start + perPage, filteredData.length);
    const pageData = filteredData.slice(start, end);

    tbody.innerHTML = pageData.map(p => `
        <tr>
            <td>${p.year} — ${p.monthName}</td>
            <td>${p.cutoff.charAt(0).toUpperCase() + p.cutoff.slice(1)}</td>
            <td>₱${p.gross.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>₱${p.deductions.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td><strong>₱${p.net.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
            <td>
                <span class="badge badge-${p.status}">${p.status.charAt(0).toUpperCase() + p.status.slice(1)}</span>
            </td>
            <td>
                <a href="${p.payslipUrl}" class="btn btn-sm btn-primary" target="_blank">
                    View Payslip
                </a>
            </td>
        </tr>
    `).join('');

    document.getElementById('entriesInfo').textContent = `Showing ${start + 1} to ${end} of ${filteredData.length} entries`;

    // Update pagination controls
    const totalPages = Math.ceil(filteredData.length / perPage);
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;

    const pageNumbers = document.getElementById('pageNumbers');
    pageNumbers.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.className = `page-num ${i === currentPage ? 'active' : ''}`;
        btn.textContent = i;
        btn.addEventListener('click', () => {
            currentPage = i;
            renderTable();
        });
        pageNumbers.appendChild(btn);
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.top-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/payroll/my-payslip.blade.php ENDPATH**/ ?>