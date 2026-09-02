<!DOCTYPE html>

<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
</head>

<body style="margin: 0; padding: 0; background: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width: 600px; background: #ffffff; border-radius: 8px;">

                    <tr>
                        <td style="padding: 30px; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 30px 30px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 30px; text-align: center; color: #888; font-size: 12px;">
                            © {{ date('Y') }} {{ config('app.name') }}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>