<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\BatchedReimbursementsNotification;
use App\Notifications\ReimbursementNotification;
use App\Services\NotificationBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_disable_all_reimbursement_emails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.notifications.update'), [
            'email_notifications_enabled' => 0,
            'email_workflow_notifications' => 1,
            'email_payment_notifications' => 1,
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertFalse($user->wantsEmailNotification('workflow'));
        $this->assertFalse($user->wantsEmailNotification('payment'));
    }

    public function test_user_can_disable_only_payment_emails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.notifications.update'), [
            'email_notifications_enabled' => 1,
            'email_workflow_notifications' => 1,
            'email_payment_notifications' => 0,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->wantsEmailNotification('workflow'));
        $this->assertFalse($user->wantsEmailNotification('payment'));
    }

    public function test_cxp_queue_transitions_create_only_an_internal_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $reimbursement = new Reimbursement([
            'status' => 'pendiente_pago',
            'total' => 1250,
            'moneda' => 'MXN',
        ]);
        $reimbursement->id = 77;

        NotificationBatchService::add($user, $reimbursement);

        Notification::assertSentTo($user, ReimbursementNotification::class, function ($notification, $channels) use ($user) {
            return $notification->via($user) === ['database'];
        });
    }

    public function test_payment_ready_email_respects_payment_preference(): void
    {
        $user = User::factory()->create([
            'email_notifications_enabled' => true,
            'email_payment_notifications' => false,
        ]);
        $reimbursement = new Reimbursement([
            'status' => 'pendiente_pago',
            'approved_by_treasury_at' => now(),
            'total' => 900,
            'moneda' => 'MXN',
        ]);
        $reimbursement->id = 88;

        $notification = new BatchedReimbursementsNotification(collect([$reimbursement]), 'payment');

        $this->assertSame(['database'], $notification->via($user));
    }
}
