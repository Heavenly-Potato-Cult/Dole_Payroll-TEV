<?php

namespace Modules\Tev\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Models\OfficeOrder;

class TevRequest extends Model
{
    use SoftDeletes;

    protected $table = 'tev_requests';

    protected $fillable = [
        'tev_no',
        'tev_series_no',
        'office_order_id',
        'employee_id',
        'track',
        'purpose',
        'destination',
        'travel_type',
        'travel_date_start',
        'travel_date_end',
        'total_days',
        'total_per_diem',
        'total_transportation',
        'total_other_expenses',
        'grand_total',
        'cash_advance_amount',
        'balance_due',
        'has_receipt',
        'has_boarding_pass',
        'has_cert_complete',
        'has_other_docs',
        'liquidation_remarks',
        'has_proof_payment',
        'has_travel_cert',
        'reimbursement_remarks',
        'status',
        'submitted_by',
        'submitted_at',
        'remarks',
        'deny_reason',
        'denied_at',
        'denied_by',
    ];

    protected $casts = [
        'travel_date_start'    => 'date',
        'travel_date_end'      => 'date',
        'submitted_at'         => 'datetime',
        'denied_at'            => 'datetime',
        'grand_total'          => 'decimal:2',
        'total_per_diem'       => 'decimal:2',
        'total_transportation' => 'decimal:2',
        'total_other_expenses' => 'decimal:2',
        'cash_advance_amount'  => 'decimal:2',
        'balance_due'          => 'decimal:2',
        'total_days'           => 'integer',
        'has_receipt'          => 'boolean',
        'has_boarding_pass'    => 'boolean',
        'has_cert_complete'    => 'boolean',
        'has_other_docs'       => 'boolean',
        'has_proof_payment'    => 'boolean',
        'has_travel_cert'      => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function officeOrder()
    {
        return $this->belongsTo(OfficeOrder::class, 'office_order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function submitter()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function itineraryLines()
    {
        return $this->hasMany(TevItineraryLine::class, 'tev_request_id');
    }

    public function approvalLogs()
    {
        return $this->hasMany(TevApprovalLog::class, 'tev_request_id');
    }

    public function certification()
    {
        return $this->hasOne(TevCertification::class, 'tev_request_id');
    }

    public function documents()
    {
        return $this->hasMany(TevDocument::class, 'tev_request_id');
    }

    public function denier()
    {
        return $this->belongsTo(\App\Models\User::class, 'denied_by');
    }
}
