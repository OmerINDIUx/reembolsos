<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 30px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #eeeeee;
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        .header img {
            height: 50px; /* Reducido a un tamaño discreto */
            width: auto;
        }
        .content {
            padding: 50px 60px; /* Mas margen para que no se vea pegado */
            color: #333333;
            line-height: 1.8;
            font-size: 16px;
        }
        .content h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 30px;
        }
        .button-container {
            margin: 45px 0;
            text-align: center;
        }
        .button {
            background-color: #2563eb;
            color: #ffffff !important; /* Letras en blanco */
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer {
            padding: 30px 50px;
            text-align: center;
            background-color: #ffffff;
            border-top: 1px solid #f0f0f0;
        }
        .footer-text {
            color: #999999;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .dev-by {
            font-size: 11px;
            color: #aaaaaa;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .indilab-logo {
            height: 50px;
            width: auto;
        }
        .details-box {
            background-color: #f9f9f9;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #eeeeee;
        }
    </style>
</head>
<body>
    <div class="wrapper" style="width: 100%; background-color: #f4f4f4; padding: 30px 0;">
        <div class="container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="header" style="background-color: #eeeeee; padding: 40px 60px; text-align: center; border-bottom: 1px solid #e0e0e0;">
                @if(file_exists(public_path('images/indi.png')))
                    <img src="{{ $message->embed(public_path('images/indi.png')) }}" alt="Grupo INDI" height="30" style="height: 30px; width: auto;">
                @else
                    <h1 style="color: #2563eb; margin: 0;">GRUPO INDI</h1>
                @endif
            </div>
            
            <div class="content" style="padding: 60px 60px; color: #333333; line-height: 1.8; font-size: 16px;">
                @yield('content')
            </div>

            <div class="footer" style="padding: 30px 60px; text-align: center; background-color: #ffffff; border-top: 1px solid #f0f0f0;">
                <div class="footer-text" style="color: #999999; font-size: 12px; margin-bottom: 15px;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                </div>
                
                <div style="margin-top: 20px;">
                    <div class="dev-by" style="font-size: 11px; color: #aaaaaa; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">Desarrollado por:</div>
                    @if(file_exists(public_path('images/INDI Lab - Logo Emergencia.png')))
                        <img src="{{ $message->embed(public_path('images/INDI Lab - Logo Emergencia.png')) }}" alt="INDI Lab" class="indilab-logo" style="height: 50px; width: auto;">
                    @else
                        <div style="color: #2563eb; font-weight: bold; font-size: 18px;">INDI LAB</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
