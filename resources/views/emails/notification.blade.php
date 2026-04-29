@extends('emails.layout')

@section('content')
    <h1>{{ $greeting ?? 'Hola,' }}</h1>
    
    <p>{!! $bodyText !!}</p>
    
    @if(isset($actionUrl))
        <div class="button-container">
            <a href="{{ $actionUrl }}" class="button">{{ $actionText ?? 'Ver Detalle' }}</a>
        </div>
    @endif

    @if(isset($details) && count($details) > 0)
        <div class="details-box">
            @foreach($details as $key => $value)
                <div class="detail-item">
                    <span class="detail-label">{{ $key }}:</span> {{ $value }}
                </div>
            @endforeach
        </div>
    @endif

    @if(isset($breakdown) && count($breakdown) > 0)
        <div style="margin-top: 30px;">
            <h2 style="font-size: 18px; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">Desglose por Centro de Costos</h2>
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background-color: #f8fafc;">
                        <th style="text-align: left; padding: 12px; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b; text-transform: uppercase;">Centro de Costos</th>
                        <th style="text-align: center; padding: 12px; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b; text-transform: uppercase;">Cant.</th>
                        <th style="text-align: right; padding: 12px; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b; text-transform: uppercase;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breakdown as $cc => $data)
                        <tr>
                            <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155;">{{ $cc }}</td>
                            <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; text-align: center;">{{ $data['count'] }}</td>
                            <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; text-align: right; font-weight: 600;">${{ number_format($data['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p style="margin-top: 35px; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 20px;">
        Si tienes alguna duda, por favor contacta al administrador del sistema.
    </p>
@endsection
