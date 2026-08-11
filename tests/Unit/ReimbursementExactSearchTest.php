<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use App\Models\Reimbursement;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReimbursementExactSearchTest extends TestCase
{
    public function test_it_returns_an_individual_result_only_for_an_exact_folio_or_uuid(): void
    {
        $controller = new ReimbursementController();
        $method = new ReflectionMethod($controller, 'exactSearchResult');

        $reimbursement = new Reimbursement([
            'folio' => 'TCC-REE-2026-32-315',
            'uuid' => 'ABC-123',
        ]);
        $reimbursement->id = 315;
        $reimbursement->setRelation('costCenter', null);
        $items = new Collection([$reimbursement]);

        $this->assertSame($reimbursement, $method->invoke($controller, $items, 'tcc-ree-2026-32-315'));
        $this->assertSame($reimbursement, $method->invoke($controller, $items, 'abc-123'));
        $this->assertNull($method->invoke($controller, $items, 'TCC-REE-2026'));
        $this->assertNull($method->invoke($controller, $items, ''));
    }
}
