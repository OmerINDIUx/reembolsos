<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_normalized')->nullable()->after('email');
            $table->string('status')->default('active')->after('microsoft_id')->index();
        });

        DB::table('users')->orderBy('id')->eachById(function ($user): void {
            $email = strtolower(trim((string) $user->email));
            $status = $user->blocked_at !== null
                ? 'disabled'
                : ($user->invitation_token !== null ? 'pending' : 'active');

            DB::table('users')->where('id', $user->id)->update([
                'email_normalized' => $email,
                'status' => $status,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email_normalized');
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email_normalized']);
            $table->dropColumn(['email_normalized', 'status']);
        });
    }
};
