<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #1a202c;
            padding: 30px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 40px;
            color: #2d3748;
            line-height: 1.6;
        }
        .content h1 {
            font-size: 24px;
            color: #1a202c;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .content p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .button-wrapper {
            margin: 30px 0;
            text-align: center;
        }
        .button {
            background-color: #1e40af;
            color: #ffffff !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .footer {
            background-color: #f7fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
        }
        .highlight {
            font-weight: bold;
            color: #1e40af;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Ensure this path is reachable or use a base64/absolute external URL if necessary -->
            <img src="{{ asset('images/indi.png') }}" alt="Logo">
        </div>
        <div class="content">
            <h1>{{ $greeting ?? 'Hola,' }}</h1>
            <p>{!! $bodyText !!}</p>
            
            @if(isset($actionUrl))
                <div class="button-wrapper">
                    <a href="{{ $actionUrl }}" class="button">{{ $actionText ?? 'Ver Detalle' }}</a>
                </div>
            @endif

            @if(isset($details))
                <div style="background-color: #edf2f7; padding: 20px; border-radius: 6px; margin-top: 20px;">
                    @foreach($details as $key => $value)
                        <p style="margin: 5px 0; font-size: 14px;">
                            <span class="highlight">{{ $key }}:</span> {{ $value }}
                        </p>
                    @endforeach
                </div>
            @endif

            @if(isset($breakdown) && count($breakdown) > 0)
                <div style="margin-top: 30px;">
                    <h2 style="font-size: 18px; color: #1a202c; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">Desglose por Centro de Costos</h2>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead>
                            <tr style="background-color: #f7fafc;">
                                <th style="text-align: left; padding: 12px; border-bottom: 2px solid #edf2f7; font-size: 13px; color: #4a5568; text-transform: uppercase;">Centro de Costos</th>
                                <th style="text-align: center; padding: 12px; border-bottom: 2px solid #edf2f7; font-size: 13px; color: #4a5568; text-transform: uppercase;">Cant.</th>
                                <th style="text-align: right; padding: 12px; border-bottom: 2px solid #edf2f7; font-size: 13px; color: #4a5568; text-transform: uppercase;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($breakdown as $cc => $data)
                                <tr>
                                    <td style="padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">{{ $cc }}</td>
                                    <td style="padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748; text-align: center;">{{ $data['count'] }}</td>
                                    <td style="padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748; text-align: right; font-weight: 600;">${{ number_format($data['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p style="margin-top: 30px;">Si tienes alguna duda, por favor contacta al administrador del sistema.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
