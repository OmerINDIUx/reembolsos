<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use App\Mail\ReimbursementClarificationRequestMail;
use App\Models\ApprovalStep;
use App\Models\Reimbursement;
use App\Models\ReimbursementApproval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReimbursementClarificationRequestTest extends TestCase
{
    public function test_a_rejected_reimbursement_targets_the_last_rejecting_authorizer(): void
    {
        $authorizer = $this->user(20, 'Autorizador', 'autoriza@example.com');
        $approval = new ReimbursementApproval([
            'action' => 'rechazado',
            'step_name' => 'Dirección',
        ]);
        $approval->setRelation('user', $authorizer);

        $reimbursement = new Reimbursement(['status' => 'rechazado']);
        $reimbursement->setRelation('currentStep', null);
        $reimbursement->setRelation('approvals', new Collection([$approval]));

        $this->assertSame($authorizer, $this->recipientFor($reimbursement));
    }

    public function test_an_active_reimbursement_targets_the_current_authorizer(): void
    {
        $authorizer = $this->user(21, 'Autorizador actual', 'actual@example.com');
        $step = new ApprovalStep(['name' => 'Control de Obra', 'user_id' => $authorizer->id]);
        $step->setRelation('user', $authorizer);

        $reimbursement = new Reimbursement(['status' => 'pendiente_autorizacion']);
        $reimbursement->setRelation('currentStep', $step);
        $reimbursement->setRelation('approvals', new Collection());

        $this->assertSame($authorizer, $this->recipientFor($reimbursement));
    }

    public function test_the_email_uses_the_clarification_template_and_folio_subject(): void
    {
        $requestedBy = $this->user(10, 'Solicitante', 'solicita@example.com');
        $recipient = $this->user(20, 'Autorizador', 'autoriza@example.com');
        $reimbursement = new Reimbursement([
            'folio' => 'TCC-REE-2026-32-315',
            'status' => 'pendiente_autorizacion',
            'type' => 'reembolso',
            'week' => '32-2026',
        ]);
        $reimbursement->id = 315;
        $reimbursement->setRawAttributes(array_merge($reimbursement->getAttributes(), [
            'fecha' => Carbon::parse('2026-08-05'),
        ]), true);
        $reimbursement->setRelation('user', $requestedBy);
        $reimbursement->setRelation('costCenter', null);
        $reimbursement->setRelation('currentStep', null);

        $mail = new ReimbursementClarificationRequestMail($reimbursement, $requestedBy, $recipient);

        $this->assertSame('Solicitud de aclaración: SCC-REE-2026-32-315', $mail->envelope()->subject);
        $this->assertSame('emails.reimbursement-clarification-request', $mail->content()->view);
    }

    public function test_the_owner_and_the_third_party_administrator_can_request_clarification(): void
    {
        $owner = $this->user(10, 'Propietario', 'owner@example.com');
        $administrator = $this->user(20, 'Administrador', 'admin@example.com');
        $unrelatedAdministrator = $this->user(30, 'Administrador ajeno', 'other-admin@example.com');
        $administrator->role = 'admin';
        $unrelatedAdministrator->role = 'admin';
        $reimbursement = new Reimbursement([
            'user_id' => $owner->id,
            'created_by_id' => $administrator->id,
        ]);
        $reimbursement->setRelation('approvals', new Collection());
        $method = new ReflectionMethod(ReimbursementController::class, 'canUserRequestClarification');
        $controller = new ReimbursementController();

        $this->assertTrue($method->invoke($controller, $reimbursement, $owner));
        $this->assertTrue($method->invoke($controller, $reimbursement, $administrator));
        $this->assertFalse($method->invoke($controller, $reimbursement, $unrelatedAdministrator));
    }

    public function test_the_next_clarification_is_available_twenty_four_hours_later(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 16:30:00'));
        $approval = new ReimbursementApproval(['action' => 'solicitud_aclaracion']);
        $approval->setRawAttributes(array_merge($approval->getAttributes(), [
            'created_at' => Carbon::now(),
        ]), true);
        $reimbursement = new Reimbursement();
        $reimbursement->setRelation('approvals', new Collection([$approval]));
        $method = new ReflectionMethod(ReimbursementController::class, 'nextClarificationAt');

        $this->assertSame(
            '2026-08-12 16:30',
            $method->invoke(new ReimbursementController(), $reimbursement)->format('Y-m-d H:i')
        );
        Carbon::setTestNow();
    }

    private function recipientFor(Reimbursement $reimbursement): ?User
    {
        $method = new ReflectionMethod(ReimbursementController::class, 'clarificationRecipient');

        return $method->invoke(new ReimbursementController(), $reimbursement);
    }

    private function user(int $id, string $name, string $email): User
    {
        $user = new User(['name' => $name, 'email' => $email]);
        $user->id = $id;

        return $user;
    }
}
