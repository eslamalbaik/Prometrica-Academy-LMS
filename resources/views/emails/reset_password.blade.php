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
      background-color: #f1f5f9;
      color: #334155;
      -webkit-font-smoothing: antialiased;
    }

    .wrapper {
      max-width: 620px;
      margin: 40px auto;
      padding: 0 16px 40px;
    }

    /* ── Header / Logo ── */
    .header {
      text-align: center;
      padding: 32px 0 28px;
    }
    .logo-wrap {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    .logo-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 900;
      color: #ffffff;
      letter-spacing: -1px;
    }
    .logo-name {
      font-size: 20px;
      font-weight: 800;
      color: #1e293b;
      letter-spacing: -0.3px;
    }
    .logo-name span {
      color: #6366f1;
    }

    /* ── Card ── */
    .card {
      background: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }

    /* ── Hero banner ── */
    .hero {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
      padding: 44px 40px 40px;
      text-align: center;
      position: relative;
    }
    .hero-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: rgba(255,255,255,0.12);
      border: 2px solid rgba(255,255,255,0.2);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      font-size: 32px;
    }
    .hero-title {
      font-size: 26px;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 10px;
      letter-spacing: -0.3px;
    }
    .hero-subtitle {
      font-size: 14px;
      color: rgba(255,255,255,0.65);
      line-height: 1.6;
    }

    /* ── Body ── */
    .body {
      padding: 40px 40px 36px;
    }

    .greeting {
      font-size: 16px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 14px;
    }

    .paragraph {
      font-size: 14px;
      color: #64748b;
      line-height: 1.75;
      margin-bottom: 14px;
    }

    /* ── CTA Button ── */
    .btn-wrap {
      text-align: center;
      margin: 36px 0;
    }
    .btn {
      display: inline-block;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #ffffff !important;
      text-decoration: none;
      font-size: 15px;
      font-weight: 700;
      padding: 15px 40px;
      border-radius: 12px;
      letter-spacing: 0.2px;
      box-shadow: 0 6px 20px rgba(99,102,241,0.35);
    }

    /* ── Expiry notice ── */
    .notice {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      background: #fef9ec;
      border: 1px solid #fde68a;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 28px;
    }
    .notice-icon { font-size: 18px; line-height: 1; flex-shrink: 0; }
    .notice-text { font-size: 13px; color: #92400e; line-height: 1.6; }
    .notice-text strong { color: #78350f; }

    /* ── Fallback link ── */
    .fallback {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px;
      margin-bottom: 10px;
    }
    .fallback p {
      font-size: 12px;
      color: #94a3b8;
      margin-bottom: 8px;
    }
    .fallback a {
      font-size: 11px;
      color: #6366f1;
      word-break: break-all;
    }

    /* ── Divider ── */
    .divider {
      border: none;
      border-top: 1px solid #f1f5f9;
      margin: 28px 0;
    }

    .ignore-note {
      font-size: 13px;
      color: #94a3b8;
      line-height: 1.6;
    }

    /* ── Footer ── */
    .footer {
      text-align: center;
      padding: 24px 40px 0;
    }
    .footer-links {
      margin-bottom: 12px;
    }
    .footer-links a {
      font-size: 12px;
      color: #6366f1;
      text-decoration: none;
      margin: 0 10px;
    }
    .footer-copy {
      font-size: 12px;
      color: #94a3b8;
      line-height: 1.6;
    }

    @media (max-width: 480px) {
      .hero, .body, .footer { padding-left: 24px; padding-right: 24px; }
      .hero-title { font-size: 22px; }
      .btn { padding: 14px 28px; }
    }
  </style>
</head>
<body>
<div class="wrapper">

  <!-- Logo Header -->
  <div class="header">
    <span class="logo-wrap">
      <span class="logo-icon">P</span>
      <span class="logo-name">Prometrica <span>Academy</span></span>
    </span>
  </div>

  <!-- Main Card -->
  <div class="card">

    <!-- Hero -->
    <div class="hero">
      <div class="hero-icon">🔐</div>
      <div class="hero-title">Password Reset Request</div>
      <div class="hero-subtitle">We received a request to reset your Prometrica Academy account password.</div>
    </div>

    <!-- Body -->
    <div class="body">
      <p class="greeting">Hello,</p>

      <p class="paragraph">
        Someone requested a password reset for the account associated with this email address.
        If this was you, click the button below to choose a new password.
      </p>

      <!-- CTA -->
      <div class="btn-wrap">
        <a href="{{ $resetUrl }}" class="btn" target="_blank">
          Reset My Password
        </a>
      </div>

      <!-- Expiry notice -->
      <div class="notice">
        <span class="notice-icon">⏱</span>
        <span class="notice-text">
          <strong>This link expires in 60 minutes.</strong><br />
          After that, you'll need to request a new password reset link from the login page.
        </span>
      </div>

      <hr class="divider" />

      <p class="ignore-note">
        If you did not request a password reset, you can safely ignore this email —
        your password will not be changed and your account remains secure.
      </p>

      <hr class="divider" />

      <!-- Fallback URL -->
      <div class="fallback">
        <p>If the button above doesn't work, copy and paste this URL into your browser:</p>
        <a href="{{ $resetUrl }}" target="_blank">{{ $resetUrl }}</a>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="footer-links">
      <a href="#">Help Center</a>
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
    </div>
    <p class="footer-copy">
      © {{ date('Y') }} Prometrica Academy. All rights reserved.<br />
      Professional Pharmacy Education Platform.
    </p>
  </div>

</div>
</body>
</html>
