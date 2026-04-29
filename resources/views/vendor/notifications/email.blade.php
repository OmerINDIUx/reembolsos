@extends('emails.layout')

@section('content')
    @if (! empty($greeting))
        <h1>{{ $greeting }}</h1>
    @else
        @if ($level === 'error')
            <h1>¡Lo sentimos!</h1>
        @else
            <h1>¡Hola!</h1>
        @endif
    @endif

    {{-- Intro Lines --}}
    @foreach ($introLines as $line)
        <p>{!! nl2br(e($line)) !!}</p>
    @endforeach

    {{-- Action Button --}}
    @isset($actionText)
        <div class="button-container" style="margin: 45px 0; text-align: center;">
            <a href="{{ $actionUrl }}" class="button" style="background-color: #2563eb; color: #ffffff !important; padding: 18px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;">
                {{ $actionText }}
            </a>
        </div>
    @endisset

    {{-- Outro Lines --}}
    @foreach ($outroLines as $line)
        <p>{!! nl2br(e($line)) !!}</p>
    @endforeach

    {{-- Salutation --}}
    <p>
        Atentamente,<br>
        <strong>{{ config('app.name') }}</strong>
    </p>

    {{-- Subcopy --}}
    @isset($actionText)
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #64748b;">
            Si tienes problemas con el botón "{{ $actionText }}", copia y pega la siguiente URL en tu navegador:
            <br>
            <a href="{{ $actionUrl }}" style="color: #2563eb; word-break: break-all;">{{ $actionUrl }}</a>
        </div>
    @endisset
@endsection
