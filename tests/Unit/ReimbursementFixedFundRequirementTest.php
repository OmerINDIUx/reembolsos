<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use PHPUnit\Framework\TestCase;

class ReimbursementFixedFundRequirementTest extends TestCase
{
    public function test_fixed_fund_reimbursement_always_requires_an_active_fund(): void
    {
        $controller = new class extends ReimbursementController {
            public function fixedFundRequired(?string $type, bool $hasInvoice): bool
            {
                return $this->requiresFixedFund($type, $hasInvoice);
            }
        };

        $this->assertTrue($controller->fixedFundRequired('fondo_fijo', true));
        $this->assertTrue($controller->fixedFundRequired('fondo_fijo', false));
    }

    public function test_meal_never_requires_a_fixed_fund(): void
    {
        $controller = new class extends ReimbursementController {
            public function fixedFundRequired(?string $type, bool $hasInvoice): bool
            {
                return $this->requiresFixedFund($type, $hasInvoice);
            }
        };

        $this->assertFalse($controller->fixedFundRequired('comida', true));
        $this->assertFalse($controller->fixedFundRequired('comida', false));
    }
}
