<?php $__env->startSection('title', 'Deduction Types'); ?>
<?php $__env->startSection('page-title', 'Deduction Types'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ════════════════════════════════════════════════════════════════
   DEDUCTION TYPES — Dashboard-matching styles
   ════════════════════════════════════════════════════════════════ */

/* ── Greeting ─────────────────────────────────────────────────── */
.db-greeting {
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, var(--navy) 0%, #1a2d6d 100%);
    border-radius: var(--radius);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.db-greeting::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 140px; height: 140px;
    background: rgba(249,168,37,0.12);
    border-radius: 50%;
}

.db-greeting-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.db-greeting h1 {
    font-size: clamp(1.1rem, 3vw, 1.4rem);
    margin: 0;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}

.db-greeting-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.db-greeting-date {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.8);
    font-weight: 500;
}

.db-greeting-location {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.65);
}

/* ── Stat Grid ────────────────────────────────────────────────── */
.db-stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 18px;
}
@media (min-width: 480px) { .db-stat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 768px) { .db-stat-grid { grid-template-columns: repeat(4, 1fr); } }

.db-stat {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    min-width: 0;
    display: flex;
    align-items: stretch;
    gap: 0;
}

.db-stat-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 90px;
    padding-right: 12px;
}

.db-stat-divider {
    width: 0.5px;
    background: #e2e8f0;
    flex-shrink: 0;
}

.db-stat-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 12px;
    min-width: 70px;
}

.db-stat-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}

.db-stat-subtitle {
    font-size: 13px;
    color: #94a3b8;
}

.db-stat-value {
    font-size: 56px;
    font-weight: 600;
    letter-spacing: -3px;
    line-height: 1;
    color: #534AB7;
}

/* ── Search & Filter Bar ─────────────────────────────────────────── */
.dt-search-filter {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 18px;
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.dt-search-input {
    flex: 1;
    min-width: 200px;
    height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.875rem;
    background: var(--surface);
    color: var(--text);
    font-family: var(--font);
}
.dt-search-input:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(15,27,76,0.1);
}
.dt-filter-select {
    height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.875rem;
    background: var(--surface);
    color: var(--text);
    min-width: 140px;
    font-family: var(--font);
}
.dt-filter-select:focus {
    outline: none;
    border-color: var(--navy);
}
.dt-no-results {
    text-align: center;
    padding: 48px 20px;
    color: var(--text-light);
    font-size: 0.95rem;
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

/* ── Expand/Collapse All ─────────────────────────────────────────── */
.dt-expand-controls {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}
.dt-expand-btn {
    height: 38px;
    padding: 8px 16px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    color: var(--text-mid);
    cursor: pointer;
    transition: all 0.2s;
    font-family: var(--font);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.dt-expand-btn:hover {
    background: var(--navy);
    color: #fff;
    border-color: var(--navy);
}

/* ── Category Accordion ───────────────────────────────────────────── */
.dt-category {
    margin-bottom: 20px;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.dt-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--bg);
    border-bottom: 0.5px solid #e2e8f0;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}
.dt-category-header:hover {
    background: var(--navy-light);
}
.dt-category-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.dt-category-label {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--navy);
    margin: 0;
    font-family: var(--font);
}
.dt-category-count {
    background: var(--navy);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
}
.dt-category-toggle {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--text-mid);
    transition: transform 0.2s;
    font-size: 1.2rem;
}
.dt-category-toggle.collapsed {
    transform: rotate(-90deg);
}
.dt-category-content {
    transition: max-height 0.3s ease-out;
    overflow: hidden;
}
.dt-category-content.collapsed {
    max-height: 0;
}

/* ── Table ───────────────────────────────────────────────────────── */
.dt-table {
    width: 100%;
    border-collapse: collapse;
    font-family: var(--font);
}
.dt-table th {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-mid);
    padding: 12px 16px;
    text-align: left;
    background: var(--bg);
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
    font-family: var(--font);
}
.dt-table td {
    padding: 14px 16px;
    border-bottom: 0.5px solid #e2e8f0;
    vertical-align: middle;
    font-size: 0.875rem;
    transition: background-color 0.15s;
    font-family: var(--font);
}
.dt-table tr:last-child td {
    border-bottom: none;
}
.dt-table tr:hover td {
    background: var(--bg);
}

/* Inactive row */
.dt-table tr.dt-inactive td {
    opacity: 0.5;
}
.dt-table tr.dt-inactive:hover td {
    opacity: 0.7;
}

/* ── Badges ──────────────────────────────────────────────────────── */
.badge-computed {
    background: var(--navy-light);
    color: var(--navy);
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 1px solid var(--navy);
    white-space: nowrap;
    font-family: var(--font);
}
.badge-manual {
    background: var(--bg);
    color: var(--text-mid);
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 0.5px solid #e2e8f0;
    white-space: nowrap;
    font-family: var(--font);
}
.badge-active {
    background: var(--success-bg);
    color: var(--success);
    font-size: 0.68rem;
    padding: 3px 10px;
    border-radius: 99px;
    font-weight: 700;
    font-family: var(--font);
}
.badge-inactive {
    background: var(--red-light);
    color: var(--red);
    font-size: 0.68rem;
    padding: 3px 10px;
    border-radius: 99px;
    font-weight: 700;
    font-family: var(--font);
}
.badge-locked {
    background: #dcfce7;
    color: #166534;
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 1px solid #16a34a;
    white-space: nowrap;
    font-family: var(--font);
}
.badge-modified {
    background: #fef3c7;
    color: #92400e;
    font-size: 0.60rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 99px;
    border: 1px solid #fbbf24;
    white-space: nowrap;
    font-family: var(--font);
}

/* ── Code chip ───────────────────────────────────────────────────── */
.code-chip {
    font-family: monospace;
    font-size: 0.78rem;
    background: var(--bg);
    border: 0.5px solid #e2e8f0;
    padding: 4px 8px;
    border-radius: var(--radius);
    color: var(--navy);
    white-space: nowrap;
    font-weight: 600;
}

/* ── Order number ────────────────────────────────────────────────── */
.dt-order {
    display: inline-block;
    min-width: 28px;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-light);
    background: var(--bg);
    border: 0.5px solid #e2e8f0;
    border-radius: var(--radius);
    padding: 2px 6px;
}

/* ── Action buttons ──────────────────────────────────────────────── */
.dt-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}
.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius);
    border: 0.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--text-mid);
    transition: all 0.15s;
    text-decoration: none;
}
.btn-icon:hover {
    background: var(--navy);
    color: #fff;
    border-color: var(--navy);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.btn-icon.danger:hover {
    background: var(--red);
    border-color: var(--red);
    color: #fff;
}
.btn-icon.btn-delete {
    color: var(--red);
    border-color: #fca5a5;
    background: #fff5f5;
}
.btn-icon.btn-delete:hover {
    background: var(--red);
    border-color: var(--red);
    color: #fff;
}

/* ── Notes truncation ────────────────────────────────────────────── */
.dt-notes {
    max-width: 240px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-light);
    font-size: 0.78rem;
}

/* ── Formula rate summary (computed types) ───────────────────────── */
.dt-formula-summary {
    margin-top: 5px;
    font-size: 0.70rem;
    color: var(--text-mid);
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    line-height: 1.4;
}
.dt-formula-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    border-radius: 99px;
    padding: 1px 7px;
    font-size: 0.65rem;
    font-weight: 600;
    font-family: var(--font);
    white-space: nowrap;
}
.dt-formula-pill.default {
    background: var(--bg);
    color: var(--text-mid);
    border-color: #e2e8f0;
}
.dt-formula-pill.wht {
    background: #fef9c3;
    color: #78350f;
    border-color: #fbbf24;
}

/* ── Legend ──────────────────────────────────────────────────────── */
.dt-legend-collapsible {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    margin-top: 32px;
    margin-bottom: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}
.dt-legend-toggle {
    width: 100%;
    padding: 16px 24px;
    background: var(--bg);
    border: none;
    border-bottom: 0.5px solid #e2e8f0;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--navy);
    font-family: var(--font);
    transition: background-color 0.2s;
}
.dt-legend-toggle:hover {
    background: var(--navy-light);
}
.dt-legend-toggle-icon {
    transition: transform 0.2s;
}
.dt-legend-toggle-icon.collapsed {
    transform: rotate(-90deg);
}
.dt-legend-body {
    padding: 20px 24px;
    transition: max-height 0.3s ease-out;
    overflow: hidden;
}
.dt-legend-body.collapsed {
    max-height: 0;
    padding: 0 24px;
}
.dt-legend-content {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    align-items: flex-start;
}
.dt-legend-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.78rem;
    color: var(--text-mid);
    font-family: var(--font);
    max-width: 320px;
}

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .dt-search-filter { flex-direction: column; align-items: stretch; }
    .dt-search-input  { min-width: auto; }
    .dt-notes         { max-width: 120px; }
    .dt-table th.dt-col-notes,
    .dt-table td.dt-col-notes { display: none; }
    .dt-legend-content { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-content">

    
    <div class="db-greeting">
        <div class="db-greeting-header">
            <div>
                <h1>Deduction Types</h1>
                <p class="db-greeting-location">Manage all deduction and loan types used across payroll and employee enrollments.</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <a href="<?php echo e(route('deduction-type-categories.index')); ?>" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.4);">☰ Categories</a>
                <a href="<?php echo e(route('deduction-types.create')); ?>" class="btn btn-primary">+ New Deduction Type</a>
            </div>
        </div>
    </div>

    
    <?php
        $allTypes      = $grouped->flatten();
        $totalCount    = $allTypes->count();
        $activeCount   = $allTypes->where('is_active', true)->count();
        $computedCount = $allTypes->where('is_computed', true)->count();
        $inactiveCount = $allTypes->where('is_active', false)->count();
    ?>

    <div class="db-stat-grid">
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Total Types</div>
                <div class="db-stat-subtitle">All deduction types</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value"><?php echo e($totalCount); ?></div>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Active</div>
                <div class="db-stat-subtitle">Currently enabled</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color:var(--success);"><?php echo e($activeCount); ?></div>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Inactive</div>
                <div class="db-stat-subtitle">Disabled types</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color:var(--red);"><?php echo e($inactiveCount); ?></div>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Auto-Computed</div>
                <div class="db-stat-subtitle">System calculated</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color:#534AB7;"><?php echo e($computedCount); ?></div>
            </div>
        </div>
    </div>

    
    <div class="dt-search-filter">
        <input type="text" class="dt-search-input" id="searchInput" placeholder="Search by Code or Name…">
        <select class="dt-filter-select" id="typeFilter">
            <option value="">All Types</option>
            <option value="computed">Auto-Computed</option>
            <option value="manual">Manual</option>
        </select>
        <select class="dt-filter-select" id="statusFilter">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    
    <div class="dt-expand-controls">
        <button type="button" class="dt-expand-btn" onclick="toggleAllCategories(true)">Expand All</button>
        <button type="button" class="dt-expand-btn" onclick="toggleAllCategories(false)">Collapse All</button>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grouped->isEmpty()): ?>
        <div class="card">
            <div class="card-body" style="text-align:center;padding:48px;color:var(--text-light);">
                <div style="font-size:2rem;margin-bottom:12px;">📋</div>
                <p>No deduction types found. <a href="<?php echo e(route('deduction-types.create')); ?>">Create the first one</a> or run the seeder.</p>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div id="categoriesContainer">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categoryLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catKey => $catLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($grouped[$catKey])): ?>
        <div class="dt-category" data-category="<?php echo e($catKey); ?>">
            <div class="dt-category-header" onclick="toggleCategory('<?php echo e($catKey); ?>')">
                <div class="dt-category-title">
                    <h3 class="dt-category-label"><?php echo e($catLabel); ?></h3>
                    <span class="dt-category-count"><?php echo e($grouped[$catKey]->count()); ?></span>
                </div>
                <button class="dt-category-toggle" aria-label="Toggle category">▼</button>
            </div>
            <div class="dt-category-content" id="category-<?php echo e($catKey); ?>">
                <table class="dt-table">
                    <thead>
                        <tr>
                            <th style="width:42px;">#</th>
                            <th style="width:160px;">Code</th>
                            <th>Name / Formula</th>
                            <th style="width:130px;">Type</th>
                            <th style="width:80px;">Status</th>
                            <th class="dt-col-notes">Notes</th>
                            <th style="width:100px;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $grouped[$catKey]->sortBy('display_order'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                        <?php
                            // ── Formula rate summary for computed types ──────────
                            $isPagibig    = in_array($type->code, ['PAG_IBIG_1', 'PAGIBIG_1']);
                            $isPhilhealth = $type->code === 'PHILHEALTH';
                            $isGsis       = in_array($type->code, ['GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET']);
                            $isWht        = in_array($type->code, ['WITHHOLDING_TAX', 'WHT']);

                            $formulaPills    = [];   // each: ['label' => '…', 'isDefault' => bool]
                            $hasCustomRates  = false;

                            if ($type->is_computed) {
                                if ($isWht) {
                                    $formulaPills[] = ['label' => 'BIR TRAIN Law — developer-managed', 'isDefault' => false, 'isWht' => true];
                                } elseif ($isPagibig) {
                                    $rate      = $type->formula_rate;
                                    $rateLow   = $type->formula_rate_low;
                                    $threshold = $type->formula_rate_threshold;
                                    $cap       = $type->formula_monthly_cap;

                                    $formulaPills[] = [
                                        'label'     => 'Main: ' . ($rate ? number_format($rate * 100, 2) . '%' : '2.00%'),
                                        'isDefault' => !$rate,
                                        'isWht'     => false,
                                    ];
                                    $formulaPills[] = [
                                        'label'     => 'Low: ' . ($rateLow ? number_format($rateLow * 100, 2) . '%' : '1.00%'),
                                        'isDefault' => !$rateLow,
                                        'isWht'     => false,
                                    ];
                                    $formulaPills[] = [
                                        'label'     => 'Threshold: ₱' . number_format($threshold ?? 1500, 0),
                                        'isDefault' => !$threshold,
                                        'isWht'     => false,
                                    ];
                                    $formulaPills[] = [
                                        'label'     => 'Cap: ₱' . number_format($cap ?? 100, 0) . '/mo',
                                        'isDefault' => !$cap,
                                        'isWht'     => false,
                                    ];
                                    $hasCustomRates = $rate || $rateLow || $threshold || $cap;

                                } elseif ($isPhilhealth) {
                                    $rate    = $type->formula_rate;
                                    $floor   = $type->formula_monthly_floor;
                                    $ceiling = $type->formula_monthly_ceiling;

                                    $formulaPills[] = [
                                        'label'     => 'Rate: ' . ($rate ? number_format($rate * 100, 2) . '%' : '5.00%'),
                                        'isDefault' => !$rate,
                                        'isWht'     => false,
                                    ];
                                    $formulaPills[] = [
                                        'label'     => 'Floor: ₱' . number_format($floor ?? 500, 0) . '/mo',
                                        'isDefault' => !$floor,
                                        'isWht'     => false,
                                    ];
                                    $formulaPills[] = [
                                        'label'     => 'Ceiling: ₱' . number_format($ceiling ?? 5000, 0) . '/mo',
                                        'isDefault' => !$ceiling,
                                        'isWht'     => false,
                                    ];
                                    $hasCustomRates = $rate || $floor || $ceiling;

                                } elseif ($isGsis) {
                                    $rate = $type->formula_rate;
                                    $formulaPills[] = [
                                        'label'     => 'Rate: ' . ($rate ? number_format($rate * 100, 2) . '%' : '9.00%') . ' of basic',
                                        'isDefault' => !$rate,
                                        'isWht'     => false,
                                    ];
                                    $hasCustomRates = (bool) $rate;
                                }

                                // Override active?
                                if ($type->isOverridden()) {
                                    $formulaPills = [[
                                        'label'     => '★ Fixed override: ₱' . number_format((float)$type->override_amount, 2) . '/cut-off',
                                        'isDefault' => false,
                                        'isWht'     => false,
                                    ]];
                                    $hasCustomRates = true;
                                }
                            }
                        ?>

                        <tr class="<?php echo e($type->is_active ? '' : 'dt-inactive'); ?>"
                            data-code="<?php echo e(strtolower($type->code)); ?>"
                            data-name="<?php echo e(strtolower($type->name)); ?>"
                            data-type="<?php echo e($type->is_computed ? 'computed' : 'manual'); ?>"
                            data-status="<?php echo e($type->is_active ? 'active' : 'inactive'); ?>">

                            
                            <td><span class="dt-order"><?php echo e($loop->iteration); ?></span></td>

                            
                            <td><span class="code-chip"><?php echo e($type->code); ?></span></td>

                            
                            <td>
                                <span style="font-weight:600;color:var(--navy);"><?php echo e($type->name); ?></span>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($type->is_computed && count($formulaPills)): ?>
                                <div class="dt-formula-summary">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $formulaPills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <span class="dt-formula-pill <?php echo e($pill['isWht'] ? 'wht' : ($pill['isDefault'] ? 'default' : '')); ?>">
                                            <?php echo e($pill['label']); ?>

                                        </span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasCustomRates && !$type->isOverridden()): ?>
                                        <span class="badge-modified">★ Modified</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($type->is_computed): ?>
                                    <span class="badge-computed">⚙️ Auto-computed</span>
                                <?php elseif($type->isEffectivelyLocked()): ?>
                                    <span class="badge-locked">🔒 Global Fixed</span>
                                <?php else: ?>
                                    <span class="badge-manual">Manual</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($type->is_active): ?>
                                    <span class="badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive">Inactive</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="dt-col-notes">
                                <span class="dt-notes" title="<?php echo e($type->notes); ?>">
                                    <?php echo e($type->notes ?: '—'); ?>

                                </span>
                            </td>

                            
                            <td>
                                <div class="dt-actions" style="justify-content:flex-end;">
                                    <a href="<?php echo e(route('deduction-types.edit', $type)); ?>"
                                       class="btn-icon" title="Edit">✎</a>

                                    <form id="toggleForm-<?php echo e($type->id); ?>" method="POST"
                                          action="<?php echo e(route('deduction-types.toggle', $type)); ?>"
                                          style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <button type="button"
                                                class="btn-icon <?php echo e($type->is_active ? 'danger' : ''); ?>"
                                                title="<?php echo e($type->is_active ? 'Deactivate' : 'Activate'); ?>"
                                                onclick="confirmToggleDeductionType(<?php echo e($type->id); ?>, '<?php echo e(addslashes($type->name)); ?>', <?php echo e($type->is_active ? 'true' : 'false'); ?>)">
                                            <?php echo e($type->is_active ? '⊘' : '✓'); ?>

                                        </button>
                                    </form>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $type->is_active): ?>
                                    <form id="deleteForm-<?php echo e($type->id); ?>" method="POST"
                                          action="<?php echo e(route('deduction-types.destroy', $type)); ?>"
                                          style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="button"
                                                class="btn-icon btn-delete"
                                                title="Delete permanently"
                                                onclick="confirmDeleteDeductionType(<?php echo e($type->id); ?>, '<?php echo e(addslashes($type->name)); ?>')">
                                            🗑
                                        </button>
                                    </form>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>

                        </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>

    <div id="noResults" class="dt-no-results" style="display:none;">
        <div style="font-size:2rem;margin-bottom:12px;">🔍</div>
        <p>No results found. Try adjusting your search or filters.</p>
    </div>

    
    <div class="dt-legend-collapsible">
        <button class="dt-legend-toggle" onclick="toggleLegend()">
            <span>Legend &amp; Help</span>
            <span class="dt-legend-toggle-icon" id="legendToggleIcon">▼</span>
        </button>
        <div class="dt-legend-body" id="legendBody">
            <div class="dt-legend-content">
                <div class="dt-legend-item">
                    <span class="badge-computed" style="flex-shrink:0;">⚙️ Auto-computed</span>
                    <span>Amount is calculated by the payroll engine (GSIS, PhilHealth, Pag-IBIG, WHT). Rates shown inline — click Edit to adjust them.</span>
                </div>
                <div class="dt-legend-item">
                    <span class="badge-locked" style="flex-shrink:0;">🔒 Global Fixed</span>
                    <span>A single amount is applied to all employees automatically.</span>
                </div>
                <div class="dt-legend-item">
                    <span class="badge-manual" style="flex-shrink:0;">Manual</span>
                    <span>Amount is set individually per employee via the Deductions enrollment form.</span>
                </div>
                <div class="dt-legend-item">
                    <span class="badge-modified" style="flex-shrink:0;">★ Modified</span>
                    <span>Formula rates have been customized from statutory defaults.</span>
                </div>
                <div class="dt-legend-item">
                    <span class="dt-formula-pill default" style="flex-shrink:0;">Rate: 5.00%</span>
                    <span>Grey pills = statutory default (no custom value saved yet).</span>
                </div>
                <div class="dt-legend-item">
                    <span class="dt-formula-pill" style="flex-shrink:0;">Rate: 6.00%</span>
                    <span>Blue pills = custom rate currently active in the system.</span>
                </div>
            </div>
        </div>
    </div>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
// ── Category accordion ────────────────────────────────────────────────────
const categoryStates = {};
const categories     = document.querySelectorAll('.dt-category');

categories.forEach(cat => {
    categoryStates[cat.dataset.category] = true;
});

function toggleCategory(key) {
    const content = document.getElementById('category-' + key);
    const toggle  = document.querySelector('[data-category="' + key + '"] .dt-category-toggle');
    categoryStates[key] = !categoryStates[key];
    if (categoryStates[key]) {
        content.classList.remove('collapsed');
        toggle.classList.remove('collapsed');
        content.style.maxHeight = content.scrollHeight + 'px';
    } else {
        content.classList.add('collapsed');
        toggle.classList.add('collapsed');
        content.style.maxHeight = '0';
    }
}

function toggleAllCategories(expand) {
    categories.forEach(cat => {
        const key     = cat.dataset.category;
        const content = document.getElementById('category-' + key);
        const toggle  = cat.querySelector('.dt-category-toggle');
        categoryStates[key] = expand;
        if (expand) {
            content.classList.remove('collapsed');
            toggle.classList.remove('collapsed');
            content.style.maxHeight = content.scrollHeight + 'px';
        } else {
            content.classList.add('collapsed');
            toggle.classList.add('collapsed');
            content.style.maxHeight = '0';
        }
    });
}

function toggleLegend() {
    const body = document.getElementById('legendBody');
    const icon = document.getElementById('legendToggleIcon');
    if (body.classList.contains('collapsed')) {
        body.classList.remove('collapsed');
        icon.classList.remove('collapsed');
        body.style.maxHeight = body.scrollHeight + 'px';
    } else {
        body.classList.add('collapsed');
        icon.classList.add('collapsed');
        body.style.maxHeight = '0';
    }
}

// ── Search & filter ───────────────────────────────────────────────────────
function setupSearchAndFilter() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter  = document.getElementById('typeFilter');
    const statusFilter= document.getElementById('statusFilter');
    const container   = document.getElementById('categoriesContainer');
    const noResults   = document.getElementById('noResults');

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();
        const type   = typeFilter.value;
        const status = statusFilter.value;
        let hasVisible = false;

        document.querySelectorAll('.dt-table tbody tr').forEach(row => {
            const matchSearch = !search || row.dataset.code.includes(search) || row.dataset.name.includes(search);
            const matchType   = !type   || row.dataset.type   === type;
            const matchStatus = !status || row.dataset.status === status;
            const visible     = matchSearch && matchType && matchStatus;
            row.style.display = visible ? '' : 'none';
            if (visible) hasVisible = true;
        });

        const isFiltered = search || type || status;
        categories.forEach(cat => {
            const key        = cat.dataset.category;
            const anyVisible = Array.from(cat.querySelectorAll('.dt-table tbody tr'))
                                   .some(r => r.style.display !== 'none');
            cat.style.display = (!isFiltered || anyVisible) ? '' : 'none';
            if (isFiltered && anyVisible && !categoryStates[key]) toggleCategory(key);
        });

        container.style.display = (!isFiltered || hasVisible) ? '' : 'none';
        noResults.style.display  = (isFiltered && !hasVisible) ? ''  : 'none';
    }

    searchInput.addEventListener('input',  applyFilters);
    typeFilter.addEventListener('change',  applyFilters);
    statusFilter.addEventListener('change',applyFilters);
}

// ── Toggle confirm ────────────────────────────────────────────────────────
function confirmToggleDeductionType(typeId, typeName, isActive) {
    const action = isActive ? 'Deactivate' : 'Activate';
    Swal.fire({
        title: action + ' Deduction Type?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${typeName}</div>
            <p style="color:#6b7280;font-size:0.9rem;">Are you sure you want to ${action.toLowerCase()} this deduction type?</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: action,
        cancelButtonText: 'Cancel',
        confirmButtonColor: isActive ? '#dc3545' : '#10B981',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        const form = document.getElementById('toggleForm-' + typeId);
        if (form) {
            form.querySelectorAll('button').forEach(b => { b.disabled = true; b.textContent = '…'; });
            form.submit();
        }
    });
}

// ── Delete confirm ────────────────────────────────────────────────────────
function confirmDeleteDeductionType(typeId, typeName) {
    Swal.fire({
        title: 'Permanently Delete?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${typeName}</div>
            <p style="color:#6b7280;font-size:0.9rem;">This will permanently remove the deduction type. This action <strong>cannot be undone</strong>.</p>
            <p style="color:#6b7280;font-size:0.85rem;margin-top:6px;">Types with payroll or enrollment history cannot be deleted.</p>
        </div>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: '🗑 Delete Permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        const form = document.getElementById('deleteForm-' + typeId);
        if (form) {
            form.querySelectorAll('button').forEach(b => { b.disabled = true; b.textContent = '…'; });
            form.submit();
        }
    });
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    categories.forEach(cat => {
        const content = document.getElementById('category-' + cat.dataset.category);
        content.style.maxHeight = content.scrollHeight + 'px';
    });
    const legendBody = document.getElementById('legendBody');
    if (legendBody) legendBody.style.maxHeight = legendBody.scrollHeight + 'px';

    setupSearchAndFilter();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/deduction-types/index.blade.php ENDPATH**/ ?>