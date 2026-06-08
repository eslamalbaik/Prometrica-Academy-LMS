@php
    // Embed the real system logo inline (base64) so it always renders, even in
    // clients that block remote images or can't reach a localhost URL.
    $logoData = null;
    $logoPath = public_path('logo.png');
    if (is_file($logoPath)) {
        $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Your Password – Prometrica Academy</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background-color: #eef2f7;
      color: #334155;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 32px 16px 40px; }

    .brand { text-align: center; padding: 8px 0 24px; }
    .brand img { height: 44px; width: auto; }
    .brand-fallback { font-size: 20px; font-weight: 800; color: #0f3460; letter-spacing: -0.3px; }

    .card {
      background: #ffffff; border-radius: 16px; overflow: hidden;
      box-shadow: 0 6px 28px rgba(15, 52, 96, 0.10); border: 1px solid #e8edf3;
    }

    .hero {
      background: linear-gradient(135deg, #0f3460 0%, #16213e 60%, #1a1a2e 100%);
      padding: 40px 40px 34px; text-align: center;
    }
    .hero-badge {
      width: 64px; height: 64px; border-radius: 50%;
      background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18);
      display: inline-block; line-height: 64px; font-size: 28px; margin-bottom: 18px;
    }
    .hero h1 { font-size: 23px; font-weight: 800; color: #ffffff; margin-bottom: 8px; letter-spacing: -0.3px; }
    .hero p  { font-size: 14px; color: rgba(255,255,255,0.7); line-height: 1.6; }

    .body { padding: 36px 40px 30px; }
    .greeting { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 14px; }
    .para { font-size: 14.5px; color: #475569; line-height: 1.75; margin-bottom: 16px; }

    .btn-wrap { text-align: center; margin: 30px 0 26px; }
    .btn {
      display: inline-block; background: linear-gradient(135deg, #0f3460, #1f6feb);
      color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 700;
      padding: 15px 44px; border-radius: 10px; letter-spacing: 0.2px;
      box-shadow: 0 8px 20px rgba(15, 52, 96, 0.28);
    }

    .notice {
      background: #fff8e6; border: 1px solid #fde8a8; border-radius: 10px;
      padding: 14px 16px; margin-bottom: 24px; font-size: 13px; color: #8a6100; line-height: 1.6;
    }
    .notice strong { color: #6b4a00; }

    .divider { border: none; border-top: 1px solid #eef2f7; margin: 24px 0; }
    .muted { font-size: 13px; color: #94a3b8; line-height: 1.65; }

    .fallback {
      background: #f7f9fc; border: 1px solid #e8edf3; border-radius: 10px; padding: 14px 16px; margin-top: 18px;
    }
    .fallback p { font-size: 12px; color: #94a3b8; margin-bottom: 6px; }
    .fallback a { font-size: 11.5px; color: #1f6feb; word-break: break-all; }

    .footer { text-align: center; padding: 22px 24px 0; }
    .footer p { font-size: 12px; color: #94a3b8; line-height: 1.7; }
    .footer .name { color: #0f3460; font-weight: 700; }

    @media (max-width: 480px) {
      .hero, .body { padding-left: 24px; padding-right: 24px; }
      .hero h1 { font-size: 20px; }
      .btn { padding: 14px 30px; }
    }
  </style>
</head>
<body>
<div class="wrapper">

  <!-- Brand logo (real system logo embedded inline, with text fallback) -->
  <div class="brand">
    @if ($logoData)
      <img src="{{ $logoData }}" alt="Prometrica Academy" />
    @else
      <span class="brand-fallback">Prometrica Academy</span>
    @endif
  </div>

  <div class="card">
    <div class="hero">
      <span class="hero-badge">🔐</span>
      <h1>Password Reset Request</h1>
      <p>We received a request to reset the password for your<br />Prometrica Academy account.</p>
    </div>

    <div class="body">
      <p class="greeting">Hello,</p>
      <p class="para">
        Someone requested a password reset for the account linked to this email address.
        If this was you, click the button below to securely choose a new password.
      </p>

      <div class="btn-wrap">
        <a href="{{ $resetUrl }}" class="btn" target="_blank" rel="noopener">Reset My Password</a>
      </div>

      <div class="notice">
        ⏱ &nbsp;<strong>This link expires in 60 minutes.</strong><br />
        After that you'll need to request a new reset link from the login page.
      </div>

      <p class="muted">
        If you didn't request a password reset, you can safely ignore this email —
        your password won't be changed and your account stays secure.
      </p>

      <hr class="divider" />

      <div class="fallback">
        <p>If the button doesn't work, copy and paste this link into your browser:</p>
        <a href="{{ $resetUrl }}" target="_blank" rel="noopener">{{ $resetUrl }}</a>
      </div>
    </div>
  </div>

  <div class="footer">
    <p>
      © {{ date('Y') }} <span class="name">Prometrica Academy</span> — All rights reserved.<br />
      Professional Pharmacy Education Platform.
    </p>
  </div>

</div>
</body>
</html>
