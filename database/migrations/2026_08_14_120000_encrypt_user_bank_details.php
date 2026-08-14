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
        Schema::table('users', function (Blueprint $table) {
            $table->text('bank_name')->nullable()->change();
            $table->text('clabe')->nullable()->change();
        });

        DB::table('users')
            ->select(['id', 'bank_name', 'clabe'])
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'bank_name' => $this->encryptIfNeeded($user->bank_name),
                        'clabe' => $this->encryptIfNeeded($user->clabe),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->select(['id', 'bank_name', 'clabe'])
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'bank_name' => $this->decryptIfNeeded($user->bank_name),
                        'clabe' => $this->decryptIfNeeded($user->clabe),
                    ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->change();
            $table->string('clabe', 18)->nullable()->change();
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