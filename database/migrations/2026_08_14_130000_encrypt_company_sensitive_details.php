<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('rfc')->nullable()->change();
            $table->text('account')->change();
        });

        DB::table('companies')
            ->select(['id', 'rfc', 'account'])
            ->orderBy('id')
            ->chunkById(100, function ($companies) {
                foreach ($companies as $company) {
                    DB::table('companies')->where('id', $company->id)->update([
                        'rfc' => $this->encryptIfNeeded($company->rfc),
                        'account' => $this->encryptIfNeeded($company->account),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('companies')
            ->select(['id', 'rfc', 'account'])
            ->orderBy('id')
            ->chunkById(100, function ($companies) {
                foreach ($companies as $company) {
                    DB::table('companies')->where('id', $company->id)->update([
                        'rfc' => $this->decryptIfNeeded($company->rfc),
                        'account' => $this->decryptIfNeeded($company->account),
                    ]);
                }
            });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->change();
            $table->string('account')->change();
        });
    }

    private function encryptIfNeeded(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            Crypt::decryptString($value);

            return $value;
        } catch (Throwable) {
            return Crypt::encryptString($value);
        }
    }

    private function decryptIfNeeded(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }
};