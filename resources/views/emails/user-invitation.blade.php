@extends('emails.layout')

@section('content')
    <h1>Hola {{ $name }},</h1>
    
    <p>Has sido invitado(a) a formar parte del <strong>Sistema de Reembolsos</strong>.</p>
    
    <p>Para comenzar a utilizar tu cuenta y registrar tus gastos, por favor activa tu acceso configurando tu contraseña personal a través del siguiente botón:</p>
    
    <div class="button-container" style="margin: 45px 0; text-align: center;">
    <a href="{{ $url }}" class="button" style="background-color: #2563eb; color: #ffffff !important; padding: 18px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;">
        Activar mi Cuenta
    </a>
</div>
    
    <p style="font-size: 13px; color: #64748b; margin-top: 40px;">
        Si tienes problemas con el botón, puedes copiar y pegar la siguiente URL en tu navegador:<br>
        <a href="{{ $url }}" style="color: #2563eb; word-break: break-all;">{{ $url }}</a>
    </p>
    
    <p style="margin-top: 30px;">
        Atentamente,<br>
        <strong>{{ config('app.name') }}</strong>
    </p>
@endsection
