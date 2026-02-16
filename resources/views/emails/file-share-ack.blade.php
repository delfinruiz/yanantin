<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f7fafc;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(16,24,40,.06);overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
                <tr>
                    <td style="padding:20px 24px;background:#0f172a;color:#ffffff;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" style="height:40px;max-height:40px;display:block;">
                                </td>
                                <td align="right" style="font-size:14px;opacity:.9;">
                                    {{ $companyName }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 24px;">
                        <h2 style="margin:0 0 8px 0;font-size:22px;line-height:28px;color:#0f172a;">{{ __('FileManager_Mail_Header_Ack') }}</h2>
                        <p style="margin:0 0 12px 0;font-size:14px;line-height:20px;color:#334155;">
                            {{ __('FileManager_Mail_Text_Shared', ['sender' => $senderName, 'file' => $file->name]) }}
                        </p>
                        <p style="margin:0 0 12px 0;font-size:14px;line-height:20px;color:#334155;">
                            {{ __('FileManager_Mail_Text_Instructions') }}
                        </p>
                        <div style="margin:16px 0;padding:16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;text-align:center;">
                            <div style="font-size:32px;line-height:36px;font-weight:bold;letter-spacing:6px;color:#0f172a;">
                                {{ $code }}
                            </div>
                        </div>
                        @if ($expiresAt)
                            <p style="margin:0 0 12px 0;font-size:13px;line-height:18px;color:#64748b;">
                                {{ __('FileManager_Mail_Text_Expires', ['date' => $expiresAt->format('d/m/Y H:i')]) }}
                            </p>
                        @endif
                        <p style="margin:0 0 16px 0;font-size:13px;line-height:18px;color:#334155;">
                            {{ __('FileManager_Mail_Text_After_Confirm') }}
                        </p>
                        <p style="margin:0;font-size:12px;line-height:18px;color:#94a3b8;">
                            {{ __('FileManager_Mail_Text_Ignore') }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#f1f5f9;color:#475569;font-size:12px;">
                        © {{ date('Y') }} {{ $companyName }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
