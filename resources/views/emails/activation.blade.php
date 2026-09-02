@extends('emails.layout')

@section('content')

    <h2 style="margin-top: 0;">Bem-vindo(a) à nossa plataforma!</h2>

    <p>
        Por favor, utilize o seguinte código para ativar sua conta:
    </p>

    <div style="
        font-size: 24px;
        font-weight: bold;
        background: #f4f4f4;
        padding: 15px;
        text-align: center;
        letter-spacing: 4px;
        border-radius: 5px;
        margin: 20px 0;
    ">
        {{ $code }}
    </div>

    <p>
        Este código irá expirar em breve. Se você não solicitou este código,
        ignore esta mensagem.
    </p>

@endsection