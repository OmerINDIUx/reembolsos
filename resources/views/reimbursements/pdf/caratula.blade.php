<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caratula de Reembolsos</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0052c7;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #0052c7;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 10px;
            font-weight: bold;
            color: #666;
        }
        .meta-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta-grid td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
            text-transform: uppercase;
            font-size: 9px;
            display: block;
        }
        .value {
            font-size: 12px;
            color: #111827;
            font-weight: bold;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .summary-table th {
            background-color: #f3f4f6;
            color: #374151;
            text-align: left;
            padding: 8px;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        .summary-table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        .summary-table tr.total-row td {
            background-color: #f9fafb;
            font-weight: bold;
            font-size: 12px;
            border-top: 2px solid #0052c7;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
        }
        .signature-grid {
            width: 100%;
            margin-top: 60px;
        }
        .signature-grid td {
            width: 33%;
            text-align: center;
        }
        .signature-line {
            width: 80%;
            margin: 0 auto;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            background-color: #e5e7eb;
            font-size: 9px;
            font-weight: bold;
        }
        .group-header {
            background-color: #0052c7;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            margin-top: 30px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Carátula de Gastos y Reembolsos</h1>
        <p>Generado el {{ $date }} | Proyecto: {{ $costCenter->name }}</p>
    </div>

    <table class="meta-grid">
        <tr>
            <td width="50%">
                <span class="label">Beneficiario</span>
                <span class="value">{{ $payee->name }}</span>
            </td>
            <td width="50%">
                <span class="label">Obra / Centro de Costos</span>
                <span class="value">{{ $costCenter->name }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Cuenta CLABE</span>
                <span class="value">{{ $payee->clabe ?: 'N/A' }}</span>
                @if($payee->bank_name)
                    <span style="display: block; font-size: 9px; color: #666;">({{ $payee->bank_name }})</span>
                @endif
            </td>
            <td>
                <span class="label">Semana Fiscal</span>
                <span class="value">Semana {{ $week }}</span>
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Categoría</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">IVA</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedItems as $item)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $item['type'])) }}</td>
                    <td><span class="badge">{{ strtoupper($item['category']) }}</span></td>
                    <td class="text-right">${{ number_format($item['subtotal'], 2) }}</td>
                    <td class="text-right">${{ number_format($item['impuestos'], 2) }}</td>
                    <td class="text-right">${{ number_format($item['total'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTAL ACUMULADO</td>
                <td class="text-right">${{ number_format($totals['subtotal'], 2) }}</td>
                <td class="text-right">${{ number_format($totals['impuestos'], 2) }}</td>
                <td class="text-right">${{ number_format($totals['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <span class="label">Observaciones Generales</span>
        <p style="font-size: 10px; color: #4b5563;">
            Este reporte incluye un total de {{ $count }} comprobantes. Se adjuntan copias de facturas originales y comprobantes de pago.
        </p>
    </div>

    <table class="signature-grid">
        <tr>
            <td>
                <div class="signature-line">
                    <span class="label">Solicitante</span>
                    <span style="font-size: 10px;">{{ $payee->name }}</span>
                </div>
            </td>
            <td>
                <div class="signature-line">
                    <span class="label">Autorización</span>
                </div>
            </td>
            <td>
                <div class="signature-line">
                    <span class="label">Control Interno</span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
