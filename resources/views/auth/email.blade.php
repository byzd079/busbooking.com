<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - JatraPoth</title>
</head>

<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:520px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

                    <!-- Brand header -->
                    <tr>
                        <td style="background-color:#1e1b4b; padding:24px; text-align:center;">
                            <div style="color:#ffffff; font-size:22px; font-weight:800; letter-spacing:0.3px;">
                                JatraPoth
                            </div>
                            <div style="color:#c7d2fe; font-size:13px; margin-top:4px;">
                                Smart Bus Booking
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 28px;">
                            <h1 style="margin:0 0 12px; font-size:22px; font-weight:700; color:#0f172a;">
                                Reset your password
                            </h1>

                            <p style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#475569;">
                                We received a request to reset the password for your JatraPoth account.
                                Click the button below to choose a new one.
                            </p>

                            <!-- Primary action -->
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="border-radius:12px; background-color:#2563eb;">
                                        <a href="{{ route('resetPassword', $token) }}"
                                            style="display:inline-block; padding:14px 32px; font-size:16px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px; font-size:13px; line-height:1.6; color:#64748b;">
                                This link expires in 60 minutes and can only be used once.
                            </p>

                            <p style="margin:0 0 20px; font-size:13px; line-height:1.6; color:#64748b;">
                                If the button does not work, copy and paste this address into your browser:
                            </p>

                            <p style="margin:0 0 24px; font-size:12px; line-height:1.5; color:#2563eb; word-break:break-all;">
                                {{ route('resetPassword', $token) }}
                            </p>

                            <div style="border-top:1px solid #e2e8f0; padding-top:20px;">
                                <p style="margin:0; font-size:13px; line-height:1.6; color:#64748b;">
                                    Did not request this? You can safely ignore this email —
                                    your password will stay the same.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; padding:20px 28px; text-align:center; border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 4px; font-size:12px; color:#94a3b8;">
                                Need help? Call +880 1995-46531
                            </p>
                            <p style="margin:0; font-size:12px; color:#94a3b8;">
                                &copy; {{ date('Y') }} JatraPoth. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
