@extends('emails.layout')

@section('preheader')
    {{ $productName }} está por {{ $currency }} {{ number_format((float) $currentPrice, 2, ',', '.') }} na {{ $store }}.
@endsection

@section('content')
    @php
        $price = $currency . ' ' . number_format((float) $currentPrice, 2, ',', '.');
        $target = $currency . ' ' . number_format((float) $targetPrice, 2, ',', '.');
        $saving = (float) $targetPrice - (float) $currentPrice;
        $formattedSaving = $currency . ' ' . number_format($saving, 2, ',', '.');
    @endphp

    <h2 style="margin: 0 0 16px; font-size: 22px; color: #111827;">
        O preço baixou! 🎉
    </h2>

    <p style="margin: 0 0 16px;">Olá, {{ $userName }}!</p>

    <p style="margin: 0 0 24px;">
        <strong>{{ $productName }}</strong> atingiu o preço que você definiu no seu alerta.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="margin: 0 0 24px; background-color: #f3f4f6; border-radius: 8px;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0; font-size: 13px; color: #6b7280;">Preço atual na {{ $store }}</p>
                <p style="margin: 4px 0 12px; font-size: 28px; font-weight: bold; color: #16a34a;">
                    {{ $price }}
                </p>
                <p style="margin: 0; font-size: 14px; color: #6b7280;">
                    Seu preço-alvo: {{ $target }}
                    @if ($saving > 0)
                        · abaixo em {{ $formattedSaving }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
        <tr>
            <td style="background-color: #2563eb; border-radius: 6px;">
                <a href="{{ $url }}"
                    style="display: inline-block; padding: 12px 24px; font-weight: bold; color: #ffffff; text-decoration: none;">
                    Ver na loja
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-size: 13px; color: #6b7280;">
        Os preços podem mudar a qualquer momento. Você pode gerenciar seus alertas no app.
    </p>
@endsection