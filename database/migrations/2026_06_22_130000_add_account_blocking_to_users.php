<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('remember_token')->index();
            $table->string('blocked_reason_code')->nullable()->after('blocked_at');
            $table->text('blocked_reason_message')->nullable()->after('blocked_reason_code');
            $table->foreignId('blocked_by')->nullable()->after('blocked_reason_message')->constrained('users')->nullOnDelete();
        });

        Schema::create('account_block_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->string('reason_code')->nullable();
            $table->text('reason_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_block_events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blocked_by');
            $table->dropColumn(['blocked_at', 'blocked_reason_code', 'blocked_reason_message']);
        });
    }
};
