<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tev_requests', function (Blueprint $table) {
            // Custom series order numbering
            $table->string('tev_series_no', 50)->nullable()->after('tev_no')->comment('Custom series order for TEV requests');
            
            // Liquidation requirements for Cash Advance
            $table->boolean('has_receipt')->default(false)->after('balance_due')->comment('Receipt uploaded for liquidation');
            $table->boolean('has_boarding_pass')->default(false)->after('has_receipt')->comment('Boarding pass uploaded for liquidation');
            $table->boolean('has_cert_complete')->default(false)->after('has_boarding_pass')->comment('Certificate of completion uploaded');
            $table->boolean('has_other_docs')->default(false)->after('has_cert_complete')->comment('Other supporting documents uploaded');
            $table->text('liquidation_remarks')->nullable()->after('has_other_docs')->comment('Remarks on liquidation documents');
            
            // Deny workflow support
            $table->text('deny_reason')->nullable()->after('remarks')->comment('Reason for denial if rejected');
            $table->timestamp('denied_at')->nullable()->after('deny_reason')->comment('Timestamp when request was denied');
            $table->foreignId('denied_by')->nullable()->after('denied_at')->constrained('users')->nullOnDelete()->comment('User who denied the request');
        });
    }

    public function down(): void
    {
        Schema::table('tev_requests', function (Blueprint $table) {
            $table->dropColumn([
                'tev_series_no',
                'has_receipt',
                'has_boarding_pass',
                'has_cert_complete',
                'has_other_docs',
                'liquidation_remarks',
                'deny_reason',
                'denied_at',
                'denied_by',
            ]);
        });
    }
};
