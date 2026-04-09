<x-mail::message>
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
