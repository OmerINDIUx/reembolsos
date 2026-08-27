<?php

namespace Tests\Feature;

use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimbursementFolioEncodingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_word_starting_with_accented_e_produces_an_ascii_abbreviation(): void
    {
        $costCenter = $this->createCostCenter('Ética Operativa');

        $this->assertSame('EOP', $costCenter->abbreviation);
        $this->assertMatchesRegularExpression('/\A[A-Z0-9]+\z/', $costCenter->abbreviation);
    }

    public function test_accented_letters_are_transliterated_before_building_a_multi_word_abbreviation(): void
    {
        $costCenter = $this->createCostCenter('Área de Ñandú Ünico Óptimo');

        $this->assertSame('ANU', $costCenter->abbreviation);
        $this->assertMatchesRegularExpression('/\A[A-Z0-9]+\z/', $costCenter->abbreviation);
    }

    public function test_collisions_keep_the_existing_four_character_resolution(): void
    {
        $first = $this->createCostCenter('Planta Tijuana');
        $second = $this->createCostCenter('Proyecto Tierra');

        $this->assertSame('PTI', $first->abbreviation);
        $this->assertSame('PRTI', $second->abbreviation);
    }

    public function test_ascii_names_keep_the_existing_abbreviation_behavior(): void
    {
        $singleWord = $this->createCostCenter('INDILAB');
        $twoWords = $this->createCostCenter('Planta Tijuana');
        $severalWords = $this->createCostCenter('Control de Obra Norte');

        $this->assertSame('IND', $singleWord->abbreviation);
        $this->assertSame('PTI', $twoWords->abbreviation);
        $this->assertSame('CON', $severalWords->abbreviation);
    }

    public function test_composite_folio_contains_only_safe_ascii_characters(): void
    {
        $costCenter = $this->createCostCenter('Ética Ñandú Ünico');
        $reimbursement = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'type' => 'reembolso',
            'week' => '35-2026',
            'fecha' => '2026-08-27 12:00:00',
            'status' => 'borrador',
        ])->fresh();

        $this->assertSame("ENU-REE-2026-35-{$this->paddedId($reimbursement)}", $reimbursement->folio);
        $this->assertMatchesRegularExpression('/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/', $reimbursement->folio);
        $this->assertSame(1, preg_match('//u', $reimbursement->folio));
    }

    public function test_folio_sanitizes_an_unexpected_invalid_cost_center_abbreviation(): void
    {
        $costCenter = new class extends CostCenter
        {
            public function getAbbreviationAttribute(): string
            {
                return "PT\xC3";
            }
        };

        $reimbursement = new Reimbursement([
            'type' => 'reembolso',
            'week' => '35-2026',
            'fecha' => '2026-08-27 12:00:00',
        ]);
        $reimbursement->id = 6983;
        $reimbursement->setRelation('costCenter', $costCenter);

        $this->assertSame('PT-REE-2026-35-6983', $reimbursement->true_folio);
        $this->assertSame(1, preg_match('//u', $reimbursement->true_folio));
    }

    public function test_auto_save_updates_a_reimbursement_for_an_accented_cost_center(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $costCenter = $this->createCostCenter('Ética Ñandú Ünico');
        $draft = Reimbursement::create([
            'user_id' => $requester->id,
            'created_by_id' => $requester->id,
            'cost_center_id' => $costCenter->id,
            'status' => 'borrador',
            'type' => 'reembolso',
            'week' => '35-2026',
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($requester)->postJson(route('reimbursements.auto_save'), [
            'type' => 'reembolso',
            'cost_center_id' => $costCenter->id,
            'week' => '35-2026',
            'has_invoice' => '1',
            'items' => [[
                'draft_id' => $draft->id,
                'total' => 125,
                'moneda' => 'MXN',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('ids.0.id', $draft->id);

        $draft->refresh();

        $this->assertSame('125.00', $draft->total);
        $this->assertMatchesRegularExpression('/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/', $draft->folio);
        $this->assertStringStartsWith('ENU-REE-', $draft->folio);
    }

    private function createCostCenter(string $name): CostCenter
    {
        return CostCenter::create([
            'code' => 'CC-'.str_pad((string) (CostCenter::count() + 1), 3, '0', STR_PAD_LEFT),
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function paddedId(Reimbursement $reimbursement): string
    {
        return str_pad((string) $reimbursement->id, 3, '0', STR_PAD_LEFT);
    }
}
