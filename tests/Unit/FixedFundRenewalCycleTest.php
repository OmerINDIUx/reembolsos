<?php

namespace Tests\Unit;

use App\Models\FixedFund;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixedFundRenewalCycleTest extends TestCase
{
    #[Test]
    public function approved_replenishments_count_complete_capital_cycles(): void
    {
        $fund = new FixedFund(['budget' => 50000]);

        $this->assertSame(2, $fund->completedRenewalCycles(114000));
        $this->assertSame(14000.0, $fund->renewalCycleProgress(114000));
    }

    #[Test]
    public function incomplete_replenishment_does_not_count_as_a_renewal(): void
    {
        $fund = new FixedFund(['budget' => 50000]);

        $this->assertSame(0, $fund->completedRenewalCycles(49999.99));
        $this->assertEqualsWithDelta(49999.99, $fund->renewalCycleProgress(49999.99), 0.001);
    }
}
