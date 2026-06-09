<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('account');
            $table->timestamps();
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('name')->constrained('companies')->nullOnDelete();
        });

        if (Schema::hasColumn('cost_centers', 'company')) {
            DB::table('cost_centers')
                ->whereNotNull('company')
                ->where('company', '!=', '')
                ->select('company')
                ->distinct()
                ->orderBy('company')
                ->get()
                ->each(function ($row) {
                    Company::firstOrCreate(
                        ['name' => trim($row->company)],
                        ['account' => 'SIN CUENTA']
                    );
                });

            DB::table('cost_centers')
                ->whereNotNull('company')
                ->where('company', '!=', '')
                ->orderBy('id')
                ->get(['id', 'company'])
                ->each(function ($row) {
                    $companyId = Company::where('name', trim($row->company))->value('id');
                    DB::table('cost_centers')->where('id', $row->id)->update(['company_id' => $companyId]);
                });

            Schema::table('cost_centers', function (Blueprint $table) {
                $table->dropColumn('company');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->string('company')->nullable()->after('name');
        });

        DB::table('cost_centers')
            ->leftJoin('companies', 'companies.id', '=', 'cost_centers.company_id')
            ->whereNotNull('companies.name')
            ->select('cost_centers.id', 'companies.name')
            ->orderBy('cost_centers.id')
            ->get()
            ->each(function ($row) {
                DB::table('cost_centers')->where('id', $row->id)->update(['company' => $row->name]);
            });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('companies');
    }
};
