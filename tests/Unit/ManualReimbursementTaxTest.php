<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use PHPUnit\Framework\TestCase;

class ManualReimbursementTaxTest extends TestCase
{
    public function test_iva_is_the_difference_between_total_and_subtotal(): void
    {
        $controller = new class extends ReimbursementController {
            public function manualIva(mixed $subtotal, mixed $total): float
            {
                return $this->calculateManualIva($subtotal, $total);
            }
        };

        $this->assertSame(16.00, $controller->manualIva(100, 116));
        $this->assertSame(13.80, $controller->manualIva('86.20', '100.00'));
    }

    public function test_iva_never_becomes_negative_and_rounds_to_cents(): void
    {
        $controller = new class extends ReimbursementController {
            public function manualIva(mixed $subtotal, mixed $total): float
            {
                return $this->calculateManualIva($subtotal, $total);
            }
        };

        $this->assertSame(0.00, $controller->manualIva(120, 100));
        $this->assertSame(16.67, $controller->manualIva(100, 116.666));
    }
}
