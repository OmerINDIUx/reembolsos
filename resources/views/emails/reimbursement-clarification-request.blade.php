@extends('emails.layout')

@section('content')
    <h1>Hola {{ $recipient->name }},</h1>

    <p><strong>{{ $requestedBy->name }}</strong> solicita una aclaración sobre el reembolso <strong>{{ $reimbursement->true_folio }}</strong>.</p>

    <div class="details-box" style="background-color: #f9f9f9; border-radius: 8px; padding: 25px; margin-top: 30px; border: 1px solid #eeeeee;">
        <div style="margin-bottom: 12px;"><strong>Folio:</strong> {{ $reimbursement->true_folio }}</div>
        <div style="margin-bottom: 12px;"><strong>Solicitante:</strong> {{ $reimbursement->user?->name ?? 'No disponible' }}</div>
        <div style="margin-bottom: 12px;"><strong>Centro de costos:</strong> {{ $reimbursement->costCenter?->name ?? 'Sin centro de costos' }}</div>
        <div style="margin-bottom: 12px;"><strong>Etapa:</strong> {{ $reimbursement->currentStep?->name ?? ucfirst(str_replace('_', ' ', $reimbursement->status)) }}</div>
        <div><strong>Importe:</strong> ${{ number_format((float) $reimbursement->total + (float) ($reimbursement->propina ?? 0), 2) }} {{ $reimbursement->moneda ?? 'MXN' }}</div>
    </div>

    <div style="margin: 45px 0; text-align: center;">
        <a href="{{ route('reimbursements.show', $reimbursement) }}" class="button" style="background-color: #2563eb; color: #ffffff !important; padding: 18px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Abrir reembolso</a>
    </div>

    <p style="font-size: 14px; color: #64748b;">Ingresa al enlace para revisar el expediente y responder directamente a la persona que solicitó la aclaración.</p>
@endsection
