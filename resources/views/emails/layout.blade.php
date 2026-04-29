<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding-bottom: 40px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-top: 40px;
        }
        .header {
            background-color: #1e293b;
            padding: 30px;
            text-align: center;
        }
        .header img {
            height: 50px;
            width: auto;
        }
        .content {
            padding: 40px;
            color: #334155;
            line-height: 1.6;
        }
        .content h1 {
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .button-container {
            margin: 35px 0;
            text-align: center;
        }
        .button {
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            font-size: 16px;
        }
        .footer {
            padding: 30px;
            text-align: center;
        }
        .footer-text {
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .dev-by {
            font-size: 10px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 8px;
        }
        .indilab-logo {
            height: 40px;
            width: auto;
            opacity: 0.8;
        }
        .details-box {
            background-color: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        .detail-item {
            margin: 8px 0;
            font-size: 14px;
        }
        .detail-label {
            font-weight: 700;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                @if(file_exists(public_path('images/indi.png')))
                    <img src="{{ $message->embed(public_path('images/indi.png')) }}" alt="Grupo INDI">
                @else
                    <h2 style="color: white; margin: 0;">{{ config('app.name') }}</h2>
                @endif
            </div>
            
            <div class="content">
                @yield('content')
            </div>

            <div class="footer">
                <div class="footer-text">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                </div>
                <div style="margin-top: 20px;">
                    <div class="dev-by">Desarrollado por:</div>
                    @if(file_exists(public_path('images/INDI Lab - Logo Emergencia.png')))
                        <a href="https://indi-lab.com/" target="_blank">
                            <img src="{{ $message->embed(public_path('images/INDI Lab - Logo Emergencia.png')) }}" alt="INDI Lab" class="indilab-logo">
                        </a>
                    @else
                        <div style="color: #94a3b8; font-weight: bold;">INDI LAB</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
