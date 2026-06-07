<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8" />
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    background: #fff;
    width: 1000px;
    height: 700px;
    position: relative;
    overflow: hidden;
  }

  /* ── Outer border frame ── */
  .outer-frame {
    position: absolute;
    inset: 10px;
    border: 3px solid #b8965a;
    border-radius: 4px;
  }
  .inner-frame {
    position: absolute;
    inset: 16px;
    border: 1px solid #d4af6e;
    border-radius: 2px;
  }

  /* ── Gold gradient header bar ── */
  .header-bar {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 60px;
    position: relative;
  }

  .logo-area {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .logo-icon {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #f0c040, #b8965a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    font-weight: 900;
    letter-spacing: -1px;
  }

  .logo-text {
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.5px;
  }
  .logo-sub {
    color: rgba(255,255,255,0.6);
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-top: 2px;
  }

  .header-right {
    text-align: right;
  }
  .cert-label {
    color: #f0c040;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
  }
  .cert-title {
    color: #fff;
    font-size: 26px;
    font-weight: 300;
    letter-spacing: 4px;
    text-transform: uppercase;
    margin-top: 4px;
  }

  /* ── Gold divider line ── */
  .gold-line {
    height: 3px;
    background: linear-gradient(90deg, #b8965a, #f0c040, #b8965a);
  }

  /* ── Main content ── */
  .content {
    padding: 42px 70px 30px;
    text-align: center;
  }

  .presented-to {
    font-size: 13px;
    color: #888;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .student-name {
    font-size: 48px;
    color: #1a1a2e;
    font-weight: 700;
    letter-spacing: 1px;
    border-bottom: 2px solid #d4af6e;
    display: inline-block;
    padding-bottom: 6px;
    margin-bottom: 20px;
    min-width: 400px;
  }

  .completion-text {
    font-size: 14px;
    color: #555;
    letter-spacing: 1px;
    margin-bottom: 14px;
    line-height: 1.6;
  }

  .course-name {
    font-size: 24px;
    color: #0f3460;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .course-category {
    display: inline-block;
    background: linear-gradient(135deg, #f0c040, #b8965a);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 4px 14px;
    border-radius: 20px;
    margin-bottom: 28px;
  }

  /* ── Footer section ── */
  .footer {
    padding: 0 70px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
  }

  .sig-block {
    text-align: center;
    min-width: 180px;
  }
  .sig-line {
    border-top: 1.5px solid #1a1a2e;
    margin-bottom: 6px;
    width: 160px;
    margin-left: auto;
    margin-right: auto;
  }
  .sig-name {
    font-size: 13px;
    font-weight: 700;
    color: #1a1a2e;
  }
  .sig-title {
    font-size: 10px;
    color: #888;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  /* ── Center seal ── */
  .seal {
    text-align: center;
  }
  .seal-circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 3px solid #b8965a;
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto 6px;
  }
  .seal-star { font-size: 20px; color: #f0c040; }
  .seal-text { font-size: 7px; color: #f0c040; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 2px; }

  /* ── UUID / Date strip ── */
  .bottom-strip {
    margin-top: 18px;
    padding: 10px 70px;
    background: #f8f7f4;
    border-top: 1px solid #e8e0d0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .strip-item {
    font-size: 9px;
    color: #999;
    letter-spacing: 1px;
    text-transform: uppercase;
  }
  .strip-value {
    font-size: 11px;
    color: #444;
    font-weight: 600;
    font-family: 'Courier New', monospace;
  }
</style>
</head>
<body>

<div class="outer-frame"></div>
<div class="inner-frame"></div>

<!-- Header -->
<div class="header-bar">
  <div class="logo-area">
    <div class="logo-icon">P</div>
    <div>
      <div class="logo-text">Prometrica Academy</div>
      <div class="logo-sub">Professional Education</div>
    </div>
  </div>
  <div class="header-right">
    <div class="cert-label">Issued by</div>
    <div class="cert-title">Certificate</div>
  </div>
</div>

<div class="gold-line"></div>

<!-- Main Content -->
<div class="content">
  <div class="presented-to">This certificate is proudly presented to</div>
  <div class="student-name">{{ $student }}</div>
  <div class="completion-text">
    for successfully completing the course
  </div>
  <div class="course-name">{{ $course }}</div>
  @if($category)
    <div class="course-category">{{ $category }}</div>
  @endif
</div>

<!-- Footer -->
<div class="footer">
  <div class="sig-block">
    <div class="sig-line"></div>
    <div class="sig-name">Prometrica Academy</div>
    <div class="sig-title">Director of Education</div>
  </div>

  <div class="seal">
    <div class="seal-circle">
      <div class="seal-star">★</div>
      <div class="seal-text">Certified</div>
    </div>
  </div>

  <div class="sig-block">
    <div class="sig-line"></div>
    <div class="sig-name">{{ $issued_at }}</div>
    <div class="sig-title">Date of Issue</div>
  </div>
</div>

<!-- Bottom strip -->
<div class="bottom-strip">
  <div>
    <div class="strip-item">Certificate ID</div>
    <div class="strip-value">{{ $uuid }}</div>
  </div>
  <div style="text-align:center;">
    <div class="strip-item">Verify at</div>
    <div class="strip-value">{{ $verify_url }}</div>
  </div>
  <div style="text-align:right;">
    <div class="strip-item">Valid &amp; Authentic</div>
    <div class="strip-value">prometrica.academy</div>
  </div>
</div>

</body>
</html>
