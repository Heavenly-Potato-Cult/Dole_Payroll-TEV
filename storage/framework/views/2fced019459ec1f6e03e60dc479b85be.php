
<div class="table-wrap">
    <table class="sd-table">
        <thead>
            <tr>
                <th>TEV No.</th>
                <th>Employee</th>
                <th>Track</th>
                <th>Office Order</th>
                <th>Travel Dates</th>
                <th class="text-right">Grand Total</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $tevRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $emp = $tev->employee;

                    $trackLabel = $tev->track === 'cash_advance' ? 'Cash Advance' : 'Reimbursement';
                    $trackStyle = $tev->track === 'cash_advance'
                        ? 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;'
                        : 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;';

                    $statusClass = match ($tev->status) {
                        'submitted'            => 'badge-pending',
                        'accountant_certified' => 'badge-computed',
                        'rd_approved'          => 'badge-released',
                        'cashier_released'     => 'badge-locked',
                        'reimbursed'           => 'badge-locked',
                        'liquidated'           => 'badge-locked',
                        'liquidation_filed'    => 'badge-pending',
                        'rejected'             => 'badge-inactive',
                        default                => 'badge-draft',
                    };
                    $statusLabel = ucwords(str_replace('_', ' ', $tev->status));

                    $isOwner  = $emp && ($emp->user_id === auth()->id() || $emp->employee_id === session('hris_employee_id'));
                    $canSubmit = $tev->status === 'draft'
                        && ($isOwner || auth()->user()->hasAnyRole(['payroll_officer', 'hrmo']));
                ?>

                
                <tr class="sd-main-row" data-id="<?php echo e($tev->id); ?>" onclick="toggleSdRow(this)">

                    <td class="col-tev fw-bold" style="color:var(--navy); white-space:nowrap;">
                        <?php echo e($tev->tev_no); ?>

                    </td>

                    <td class="col-employee">
                        <div>
                            <span class="sd-name-label">
                                <?php echo e(optional($emp)->last_name); ?>,
                                <?php echo e(optional($emp)->first_name); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(optional($emp)->middle_name): ?>
                                    <?php echo e(substr($emp->middle_name, 0, 1)); ?>.
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(optional($emp)->position_title): ?>
                                <div class="sd-name-sub">
                                    <?php echo e(optional($emp)->position_title); ?>

                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </td>

                    <td class="col-track">
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 8px;
                                     border-radius:12px; <?php echo e($trackStyle); ?>">
                            <?php echo e($trackLabel); ?>

                        </span>
                    </td>

                    <td class="col-oo" style="font-size:0.82rem;">
                        <?php echo e(optional($tev->officeOrder)->office_order_no ?? '—'); ?>

                    </td>

                    <td class="col-dates text-muted" style="font-size:0.82rem; white-space:nowrap;">
                        <?php echo e($tev->travel_date_start->format('M d')); ?>

                        –
                        <?php echo e($tev->travel_date_end->format('M d, Y')); ?>

                    </td>

                    <td class="col-total text-right fw-bold">
                        ₱<?php echo e(number_format($tev->grand_total, 2)); ?>

                    </td>

                    <td class="col-status">
                        <span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                    </td>

                    <td class="col-actions">
                        <div class="d-flex gap-2" style="justify-content:center;">
                            <a href="<?php echo e(route('tev.requests.show', $tev->id)); ?>"
                               class="btn btn-outline btn-sm"
                               onclick="event.stopPropagation();">View</a>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canSubmit): ?>
                                <form method="POST"
                                      action="<?php echo e(route('tev.requests.submit', $tev->id)); ?>"
                                      onsubmit="event.stopPropagation(); return confirm('Submit this TEV for approval?')">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            onclick="event.stopPropagation();">Submit</button>
                                </form>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <span class="sd-expand-btn" aria-label="Expand">▼</span>
                    </td>

                </tr>

                
                <tr class="sd-detail-row" id="sd-detail-<?php echo e($tev->id); ?>">
                    <td colspan="8">
                        <div class="sd-detail-grid">
                            <div class="sd-detail-item">
                                <label>TEV No.</label>
                                <span style="color:var(--navy); font-weight:700;"><?php echo e($tev->tev_no); ?></span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Track</label>
                                <span>
                                    <span style="font-size:0.72rem; font-weight:700;
                                                 padding:2px 8px; border-radius:10px;
                                                 <?php echo e($trackStyle); ?>"><?php echo e($trackLabel); ?></span>
                                </span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Office Order</label>
                                <span><?php echo e(optional($tev->officeOrder)->office_order_no ?? '—'); ?></span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Grand Total</label>
                                <span class="mono" style="color:var(--navy); font-weight:700;">
                                    ₱<?php echo e(number_format($tev->grand_total, 2)); ?>

                                </span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Travel Start</label>
                                <span><?php echo e($tev->travel_date_start->format('M d, Y')); ?></span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Travel End</label>
                                <span><?php echo e($tev->travel_date_end->format('M d, Y')); ?></span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Status</label>
                                <span><span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></span>
                            </div>
                        </div>
                        <div class="sd-detail-actions">
                            <a href="<?php echo e(route('tev.requests.show', $tev->id)); ?>"
                               class="btn btn-outline btn-sm">View</a>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canSubmit): ?>
                                <form method="POST"
                                      action="<?php echo e(route('tev.requests.submit', $tev->id)); ?>"
                                      style="flex:1;"
                                      onsubmit="return confirm('Submit this TEV for approval?')">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            style="width:100%;">Submit</button>
                                </form>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </td>
                </tr>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:var(--text-light);">
                        No TEV requests found.
                        <a href="<?php echo e(route('tev.requests.create')); ?>">Create one now →</a>
                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>
</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevRequests->hasPages()): ?>
<div style="padding:4px 20px 8px;">
    <?php echo e($tevRequests->links('pagination::custom', ['pageName' => $pageName ?? 'page'])); ?>

</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Tev\resources/views/partials/tev-table.blade.php ENDPATH**/ ?>