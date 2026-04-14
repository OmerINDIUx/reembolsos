<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\ApprovalStep;
use App\Models\TravelEvent;
use App\Models\BudgetRenewal;
use App\Models\ReimbursementApproval;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CleanProjectSeeder extends Seeder
{
    /**
     * Seed the application's database for production-like clean state.
     */
    public function run(): void
    {
        $this->command->info('Cleaning database tables...');

        // Disable foreign key checks for clearing
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Truncate business logic tables
        $this->command->comment('Truncating business tables...');
        Reimbursement::truncate();
        TravelEvent::truncate();
        DB::table('travel_event_user')->truncate();
        ApprovalStep::truncate();
        CostCenter::truncate();
        DB::table('cost_center_user')->truncate();
        BudgetRenewal::truncate();
        ReimbursementApproval::truncate();
        DB::table('reimbursement_files')->truncate();
        DB::table('notifications')->truncate();
        DB::table('notification_batches')->truncate();

        // 2. Clear users except admin@example.com
        $this->command->comment('Clearing users (except admin@example.com)...');
        User::where('email', '!=', 'admin@example.com')->delete();

        // 3. Ensure admin@example.com exists with the correct role
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'must_change_password' => false,
            ]
        );

        // Explicitly set role if it existed with a different one
        $admin->role = 'admin';
        $admin->save();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database cleaned successfully. Only admin@example.com remains.');
    }
}
