<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reimbursement_approvals', function (Blueprint $row) {
            $row->id();
            $row->foreignId('reimbursement_id')->constrained()->onDelete('cascade');
            $row->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $row->string('step_name');
            $row->string('action'); // approved, rejected, requires_correction, resubmitted
            $row->text('comment')->nullable();
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_approvals');
    }
};
