<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * special_payroll_batch_allowances
 *
 * Mirrors the shape of payroll_entry_allowances (PayrollEntryAllowance) so the
 * two flows stay structurally consistent. One row per allowance line applied
 * to a single SpecialPayrollBatch (currently only used by the 'newly_hired'
 * and 'transferee' types via NewlyHiredPayrollService).
 *
 * full_amount   — the resolved standing/assignment monthly amount (pre-proration)
 * amount        — the value actually applied to gross/net (pro-rated, or the
 *                  manual override value when is_overridden = true)
 * is_overridden / override_reason — mirrors PayrollEntryAllowance's manual
 *                  override pattern; override_reason is required at the
 *                  application layer whenever is_overridden is set true.
 *
 * NOTE: FK constraint names are given explicitly (short) because Laravel's
 * auto-generated name for this table — e.g.
 * "special_payroll_batch_allowances_special_payroll_batch_id_foreign" —
 * exceeds MySQL's 64-character identifier limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_payroll_batch_allowances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('special_payroll_batch_id');
            $table->foreignId('allowance_type_id');

            $table->string('code');   // snapshot of AllowanceType->code at apply-time
            $table->string('name');   // snapshot of AllowanceType->name at apply-time

            $table->decimal('full_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);

            $table->boolean('is_overridden')->default(false);
            $table->string('override_reason', 500)->nullable();

            $table->timestamps();

            $table->foreign('special_payroll_batch_id', 'sp_batch_alw_batch_fk')
                ->references('id')->on('special_payroll_batches')
                ->cascadeOnDelete();

            $table->foreign('allowance_type_id', 'sp_batch_alw_type_fk')
                ->references('id')->on('allowance_types')
                ->cascadeOnDelete();

            $table->unique(
                ['special_payroll_batch_id', 'allowance_type_id'],
                'sp_batch_allowance_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_payroll_batch_allowances');
    }
};
