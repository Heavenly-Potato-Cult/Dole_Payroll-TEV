<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tev_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tev_request_id')->constrained('tev_requests')->restrictOnDelete();
            $table->string('document_type', 50)->comment('receipt, boarding_pass, certification, itinerary, other');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size')->comment('File size in bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tev_request_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tev_documents');
    }
};
