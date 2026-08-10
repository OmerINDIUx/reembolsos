<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use App\Models\Reimbursement;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_cfdi_details_are_extracted_from_xml(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" xmlns:implocal="http://www.sat.gob.mx/implocal" Version="4.0" Fecha="2026-07-22T10:00:00" SubTotal="150.00" Total="166.50" Moneda="MXN" TipoDeComprobante="I" LugarExpedicion="64000">
  <cfdi:Emisor Rfc="AAA010101AAA" Nombre="EMISOR" RegimenFiscal="601"/>
  <cfdi:Receptor Rfc="BBB010101BBB" Nombre="RECEPTOR" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto ClaveProdServ="90101501" Descripcion="CONSUMO DE ALIMENTOS" Importe="100.00" ObjetoImp="02">
      <cfdi:Impuestos><cfdi:Traslados><cfdi:Traslado Base="100.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="16.00"/></cfdi:Traslados></cfdi:Impuestos>
    </cfdi:Concepto>
    <cfdi:Concepto ClaveProdServ="90101800" Descripcion="SERVICIO EXENTO" Importe="50.00" ObjetoImp="02">
      <cfdi:Impuestos><cfdi:Traslados><cfdi:Traslado Base="50.00" Impuesto="002" TipoFactor="Exento"/></cfdi:Traslados></cfdi:Impuestos>
    </cfdi:Concepto>
  </cfdi:Conceptos>
  <cfdi:Impuestos TotalImpuestosTrasladados="16.00"><cfdi:Traslados><cfdi:Traslado Base="100.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="16.00"/></cfdi:Traslados></cfdi:Impuestos>
  <cfdi:Complemento>
    <implocal:ImpuestosLocales version="1.0" TotaldeRetenciones="0.00" TotaldeTraslados="0.50">
      <implocal:TrasladosLocales ImpLocTrasladado="ISH" TasadeTraslado="1.00" Importe="0.50"/>
    </implocal:ImpuestosLocales>
    <tfd:TimbreFiscalDigital UUID="11111111-2222-3333-4444-555555555555"/>
  </cfdi:Complemento>
</cfdi:Comprobante>
XML;

        $controller = new ReimbursementController();
        $method = new \ReflectionMethod($controller, 'extractXmlData');
        $data = $method->invoke($controller, $xml);

        $this->assertSame([
            [
                'clave_prod_serv' => '90101501',
                'descripcion' => 'CONSUMO DE ALIMENTOS',
                'importe' => 100.0,
                'iva_traslados' => [[
                    'base' => 100.0,
                    'tasa_o_cuota' => '0.160000',
                    'importe' => 16.0,
                ]],
            ],
            [
                'clave_prod_serv' => '90101800',
                'descripcion' => 'SERVICIO EXENTO',
                'importe' => 50.0,
                'iva_traslados' => [[
                    'base' => 50.0,
                    'tasa_o_cuota' => 'NH',
                    'importe' => 'NH',
                ]],
            ],
        ], $data['cfdi_conceptos']);

        $this->assertSame([
            ['imp_loc_trasladado' => 'ISH', 'importe' => 0.5],
        ], $data['impuestos_locales']);

        $this->assertSame(16.0, $data['monto_iva']);

        $xmlElement = simplexml_load_string($xml);
        $xmlElement->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $taxMethod = new \ReflectionMethod($controller, 'extractTaxRateAndAmount');
        $taxSummary = $taxMethod->invoke($controller, $xmlElement, [
            '//cfdi:Comprobante/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado',
            '//cfdi:Conceptos/cfdi:Concepto/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado',
        ], '002');

        $this->assertSame(['amount' => 16.0, 'percent' => 16.0], $taxSummary);
    }

    public function test_payment_policy_prefers_stored_details_and_preserves_nh(): void
    {
        $reimbursement = new Reimbursement();
        $reimbursement->setRawAttributes([
            'cfdi_conceptos' => json_encode([
                [
                    'clave_prod_serv' => '90101501',
                    'descripcion' => 'CONSUMO',
                    'importe' => 100,
                    'iva_traslados' => [['base' => 100, 'tasa_o_cuota' => '0.160000', 'importe' => 16]],
                ],
                [
                    'clave_prod_serv' => '90101800',
                    'descripcion' => 'EXENTO',
                    'importe' => 50,
                    'iva_traslados' => [['base' => 50, 'tasa_o_cuota' => 'NH', 'importe' => 'NH']],
                ],
            ]),
            'impuestos_locales' => json_encode([
                ['imp_loc_trasladado' => 'ISH', 'importe' => 0.5],
            ]),
            'xml_path' => 'archivo-que-no-debe-consultarse.xml',
        ], true);

        $controller = new ReimbursementController();
        $detailsMethod = new \ReflectionMethod($controller, 'paymentPolicyXmlDetails');
        $columnsMethod = new \ReflectionMethod($controller, 'paymentPolicyXmlDetailColumns');

        $details = $detailsMethod->invoke($controller, $reimbursement);
        $columns = $columnsMethod->invoke($controller, $details);

        $this->assertSame([
            '90101501 | 90101800',
            'CONSUMO | EXENTO',
            '100.00 | 50.00',
            '100.00 | 50.00',
            '0.160000 | NH',
            '16.00 | NH',
            'ISH',
            '0.50',
        ], $columns);
    }
}
