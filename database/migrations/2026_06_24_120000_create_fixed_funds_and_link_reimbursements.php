<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->decimal('budget', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['cost_center_id', 'user_id', 'is_active']);
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->foreignId('fixed_fund_id')->nullable()->after('cost_center_id')
                ->constrained('fixed_funds')->nullOnDelete();
        });

        DB::table('cost_centers')
            ->whereNotNull('beneficiary_id')
            ->orderBy('id')
            ->each(function ($costCenter) {
                $fundId = DB::table('fixed_funds')->insertGetId([
                    'cost_center_id' => $costCenter->id,
                    'user_id' => $costCenter->beneficiary_id,
                    'name' => 'Fondo fijo principal',
                    'budget' => $costCenter->budget ?? 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('reimbursements')
                    ->where('cost_center_id', $costCenter->id)
                    ->whereIn('type', ['fondo_fijo', 'comida', 'viaje'])
                    ->update(['fixed_fund_id' => $fundId]);
            });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fixed_fund_id');
        });
        Schema::dropIfExists('fixed_funds');
    }
};
