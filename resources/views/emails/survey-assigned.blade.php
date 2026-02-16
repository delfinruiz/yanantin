<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva encuesta asignada</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;">
    @inject('settingService', 'App\Services\SettingService')
    @php
        $company = $settingService->get('company_name') ?? config('app.name', 'Finanzas Personales');
        $logoLight = $settingService->get('logo_light');
        $logoLightUrl = $logoLight ? url(\Illuminate\Support\Facades\Storage::url($logoLight)) : url(asset('/asset/images/logo-light.png'));
    @endphp
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td align="center" style="padding:24px 24px 8px 24px;">
                            <img src="{{ $logoLightUrl }}" alt="{{ $company }}" style="height:56px;max-height:56px;display:block;">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 24px 16px 24px;">
                            <div style="font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Helvetica Neue,Arial;font-size:22px;line-height:1.3;font-weight:700;color:#111827;">
                                Nueva encuesta asignada
                            </div>
                            <div style="font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Helvetica Neue,Arial;font-size:14px;color:#6b7280;margin-top:6px;">
                                {{ $company }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 24px 0 24px;">
                            <div style="font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Helvetica Neue,Arial;font-size:16px;color:#111827;">
                                Se te ha asignado la encuesta:
                                <span style="font-weight:600;color:#111827;">{{ $title }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 24px 4px 24px;">
                            <a href="{{ $url }}" style="display:inline-block;background:#1A2A4F;color:#ffffff;text-decoration:none;border-radius:10px;padding:12px 18px;font-weight:600;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Helvetica Neue,Arial;">
                                Responder encuesta
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 24px 24px 24px;">
                            <div style="font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Helvetica Neue,Arial;font-size:13px;color:#6b7280;text-align:center;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <span style="word-break:break-all;color:#374151;">{{ $url }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
                <div style="font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Helvetica Neue,Arial;font-size:12px;color:#9ca3af;margin-top:12px;">
                    © {{ date('Y') }} {{ $company }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
