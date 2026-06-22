<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_logins', function (Blueprint $table) {
            $table->boolean('is_new_device')->default(false)->after('device_label')->index();
            $table->unsignedSmallInteger('risk_score')->default(0)->after('is_new_device')->index();
            $table->json('risk_reasons')->nullable()->after('risk_score');
            $table->string('approx_location')->nullable()->after('ip_address');
            $table->unsignedSmallInteger('simultaneous_devices_count')->default(0)->after('risk_reasons');
            $table->unsignedSmallInteger('shared_accounts_count')->default(0)->after('simultaneous_devices_count');
        });
    }

    public function down(): void
    {
        Schema::table('device_logins', function (Blueprint $table) {
            $table->dropColumn([
                'is_new_device',
                'risk_score',
                'risk_reasons',
                'approx_location',
                'simultaneous_devices_count',
                'shared_accounts_count',
            ]);
        });
    }
};
