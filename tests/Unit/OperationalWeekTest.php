<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use App\Models\ApprovalStep;
use App\Models\CostCenter;
use App\Models\Profile;
use App\Models\Reimbursement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class OperationalWeekTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_payment_uses_stored_payment_week_without_changing_fiscal_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $controller = new ReimbursementController();
        $reimbursement = new Reimbursement(['week' => '12-2026', 'payment_week' => '24-2026']);
        $items = new Collection([$reimbursement]);

        $method = new ReflectionMethod($controller, 'attachOperationalWeek');
        $method->invoke($controller, $items, 'payment');

        $this->assertSame('12-2026', $reimbursement->week);
        $this->assertSame('24-2026', $reimbursement->operational_week);
    }

    public function test_management_uses_current_processing_week_without_changing_fiscal_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $controller = new ReimbursementController();
        $reimbursement = new Reimbursement(['week' => '12-2026']);
        $items = new Collection([$reimbursement]);

        $method = new ReflectionMethod($controller, 'attachOperationalWeek');
        $method->invoke($controller, $items, 'management');

        $this->assertSame('12-2026', $reimbursement->week);
        $this->assertSame('26-2026', $reimbursement->operational_week);
    }

    public function test_audit_uses_the_notification_items_week_instead_of_defaulting_regular_visits(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $controller = new ReimbursementController();
        $method = new ReflectionMethod($controller, 'notificationAuditWeek');
        $reimbursement = new Reimbursement(['week' => '12-2026']);
        $reimbursement->setAttribute('operational_week', '26-2026');
        $items = new Collection([$reimbursement]);

        $this->assertNull($method->invoke($controller, null, 'management', $items, false));
        $this->assertSame('26-2026', $method->invoke($controller, null, 'management', $items, true));
        $this->assertSame('12-2026', $method->invoke($controller, '12-2026', 'management', $items, true));
        $this->assertSame('12-2026', $method->invoke($controller, null, 'history', $items, true));
    }

    public function test_historical_tabs_keep_the_original_fiscal_week_only(): void
    {
        $controller = new ReimbursementController();
        $reimbursement = new Reimbursement(['week' => '12-2026']);

        $method = new ReflectionMethod($controller, 'attachOperationalWeek');
        $method->invoke($controller, new Collection([$reimbursement]), 'history');

        $this->assertSame('12-2026', $reimbursement->week);
        $this->assertNull($reimbursement->operational_week);
    }

    public function test_executive_owner_starts_at_control_de_obra_even_when_captured_by_someone_else(): void
    {
        $controller = new ReimbursementController();
        $executive = $this->userWithProfile('director_ejecutivo', 30);
        $director = $this->userWithProfile('director', 10);
        $control = $this->userWithProfile('control_obra', 20);
        $costCenter = new CostCenter(['control_obra_id' => 20]);

        $directorStep = $this->step(101, 1, $director);
        $controlStep = $this->step(102, 2, $control);

        $method = new ReflectionMethod($controller, 'hierarchyOverrideInitialStep');
        $result = $method->invoke($controller, collect([$directorStep, $controlStep]), $costCenter, $executive);

        $this->assertSame(102, $result->id);
    }

    public function test_subdirection_owner_falls_back_to_first_step_when_control_de_obra_is_absent(): void
    {
        $controller = new ReimbursementController();
        $subdirection = $this->userWithProfile('direccion', 30);
        $director = $this->userWithProfile('director', 10);
        $firstStep = $this->step(101, 1, $director);

        $method = new ReflectionMethod($controller, 'hierarchyOverrideInitialStep');
        $result = $method->invoke($controller, collect([$firstStep]), new CostCenter(), $subdirection);

        $this->assertSame(101, $result->id);
    }

    private function userWithProfile(string $profileName, int $id): User
    {
        $user = new User(['role' => 'user']);
        $user->id = $id;
        $user->setRelation('profile', new Profile(['name' => $profileName]));

        return $user;
    }

    private function step(int $id, int $order, User $user): ApprovalStep
    {
        $step = new ApprovalStep(['user_id' => $user->id, 'order' => $order]);
        $step->id = $id;
        $step->setRelation('user', $user);

        return $step;
    }
}
