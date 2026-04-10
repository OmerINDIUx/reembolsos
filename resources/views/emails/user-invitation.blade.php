<x-mail::message>
<div style="text-align: center; margin-bottom: 25px;">
    <img src="{{ $message->embed(public_path('images/indi.png')) }}" alt="Logo INDI" style="height: 60px; width: auto;">
</div>

# Hola {{ $name }},

Has sido invitado(a) a formar parte del **Sistema de Reembolsos**.

Para comenzar a utilizar tu cuenta y registrar tus gastos, por favor activa tu acceso configurando tu contraseña personal a través del siguiente botón:

<x-mail::button :url="$url">
Activar mi Cuenta
</x-mail::button>

Si tienes problemas con el botón, puedes copiar y pegar la siguiente URL en tu navegador:
<br>
[{{ $url }}]({{ $url }})

Atentamente,<br>
{{ config('app.name') }}
</x-mail::message>
