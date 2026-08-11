<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications_enabled')->default(true)->after('personal_info_confirmed_at');
            $table->boolean('email_workflow_notifications')->default(true)->after('email_notifications_enabled');
            $table->boolean('email_payment_notifications')->default(true)->after('email_workflow_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_notifications_enabled', 'email_workflow_notifications', 'email_payment_notifications']);
        });
    }
};
