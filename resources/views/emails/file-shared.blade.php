<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Archivo compartido</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background:#2563eb; color:#ffffff; padding:16px 24px;">
                            <h2 style="margin:0; font-size:20px;">
                                📎 Archivo compartido
                            </h2>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:24px; color:#333333; font-size:14px; line-height:1.6;">
                            
                            <p style="margin-top:0;">
                                Se ha compartido contigo el siguiente archivo:
                            </p>

                            <div style="background:#f1f5f9; border:1px solid #e2e8f0; padding:12px 16px; border-radius:6px; margin:16px 0;">
                                <strong>📄 {{ $file->name }}</strong>
                            </div>

                            <p>
                                {!! $mailBody !!}
                            </p>

                            <p style="margin-bottom:0;">
                                El archivo va adjunto en este correo.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc; padding:16px 24px; font-size:12px; color:#6b7280;">
                            <p style="margin:0;">
                                Este archivo fue enviado desde el sistema.<br>
                                Si no reconoces este mensaje, puedes ignorarlo con total tranquilidad.
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="font-size:11px; color:#9ca3af; margin-top:12px;">
                    © {{ date('Y') }} — Sistema de Archivos
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
