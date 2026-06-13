@php
  // ─── Embedded fonts (base64) so PDF rendering is identical on any server ───
  $fontDir = storage_path('fonts');
  $fontData = function (string $file) use ($fontDir) {
      $path = $fontDir . DIRECTORY_SEPARATOR . $file;
      return file_exists($path)
          ? 'data:font/woff2;base64,' . base64_encode(file_get_contents($path))
          : '';
  };
  $poppins400  = $fontData('poppins-400.woff2');
  $poppins600  = $fontData('poppins-600.woff2');
  $poppins700  = $fontData('poppins-700.woff2');
  $playfair400 = $fontData('playfair-400.woff2');

  // ─── Background template image ────────────────────────────────────────────
  $imagePath = public_path('images/certificate-template-clean.png');
  if (! file_exists($imagePath)) {
      $imagePath = public_path('images/Blue-and-Gold-Patterned-Appreciation-Certificate.png');
  }
  $base64Image = '';
  if (file_exists($imagePath)) {
      $base64Image = 'data:image/png;base64,' . base64_encode(file_get_contents($imagePath));
  }

  // ─── Dynamic variables mapping with compatibility logic ───────────────────
  $name_student      = $name_student ?? ($student ?? '');
  $course_name       = $course_name ?? ($course ?? '');
  $date              = $date ?? ($issued_at ?? '');
  $verification_code = $verification_code ?? ($uuid ?? '');
  $verify_domain     = 'prometrica.com/verify';

  // Force the name to uppercase for a professional look.
  $name_student = mb_strtoupper($name_student);

  // Scale the student name down for long names so it stays on one line.
  $nameLength = mb_strlen($name_student);
  if ($nameLength > 32) {
      $nameFontSize = '32px';
  } elseif ($nameLength > 24) {
      $nameFontSize = '40px';
  } elseif ($nameLength > 16) {
      $nameFontSize = '50px';
  } else {
      $nameFontSize = '56px';
  }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  @font-face {
      font-family: 'Poppins'; font-style: normal; font-weight: 400;
      src: url({{ $poppins400 }}) format('woff2');
  }
  @font-face {
      font-family: 'Poppins'; font-style: normal; font-weight: 600;
      src: url({{ $poppins600 }}) format('woff2');
  }
  @font-face {
      font-family: 'Poppins'; font-style: normal; font-weight: 700;
      src: url({{ $poppins700 }}) format('woff2');
  }
  @font-face {
      font-family: 'Playfair Display'; font-style: normal; font-weight: 400;
      src: url({{ $playfair400 }}) format('woff2');
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  @page { size: 1191px 1684px; margin: 0; }

  html, body {
      width: 1191px;
      height: 1684px;
      font-family: 'Poppins', 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;
      background-color: #ffffff;
  }

  .cert {
      position: relative;
      width: 1191px;
      height: 1684px;
      background-repeat: no-repeat;
      background-position: center;
      background-size: 1191px 1684px;
      overflow: hidden;
  }

  .field { position: absolute; }

  /* [name student] — large serif line */
  .f-name {
      top: 46%;
      left: 31%;
      width: 60%;
      text-align: center;
      font-family: 'Playfair Display', 'Georgia', 'Times New Roman', serif;
      font-size: 56px;
      font-weight: 400;
      color: #b8995a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      text-transform: uppercase;
      background: transparent;
  }

  /* The descriptive paragraph (embeds Course Name + Date) */
  .f-body {
      top: 52%;
      left: 35.6%;
      width: 53%;
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 400;
      line-height: 1.7;
      color: #555555;
      text-align: left;
      background: #ffffff;
      padding: 6px 0 20px 0;
  }
  .f-body .hl { color: #0f3460; font-weight: 700; }

  /* certificate ID: <code> */
  .f-code {
      top: 81.5%;
      left: 46%;
      width: 45%;
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 600;
      color: #333333;
      text-align: left;
      background: #ffffff;
      padding: 4px 0;
  }
  .f-code .lbl { color: #555555; }
  .f-code .val { font-family: 'Courier New', monospace; letter-spacing: 0.3px; color: #0f3460; }

  /* Verify at: <url> */
  .f-url {
      top: 83.5%;
      left: 46%;
      width: 45%;
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 600;
      color: #333333;
      text-align: left;
      background: #ffffff;
      padding: 4px 0;
  }
  .f-url .lbl { color: #555555; }
  .f-url .val { color: #0f3460; }

  /* White rectangle to hide the pre-printed duplicate labels on the PNG template */
  .f-cover {
      top: 85.5%;
      left: 33%;
      width: 58%;
      height: 5.5%;
      background: #ffffff;
      z-index: 1;
  }

</style>
</head>
<body>

<div class="cert" style="background-image: url('{{ $base64Image }}');">

    <!-- [name student] (template area is blank) -->
    <div class="field f-name" style="font-size: {{ $nameFontSize }};">{{ $name_student }}</div>

    <div class="field f-body">for successfully completing the <span class="hl">{{ $course_name }}</span> course on <span class="hl">{{ $date }}</span>. We hope this milestone achieved with Prometrica Academy serves as a great motivation for your future endeavors.</div>

    <div class="field f-cover"></div>
    <div class="field f-code"><span class="lbl">certificate ID:</span> <span class="val">{{ $verification_code }}</span></div>
    <div class="field f-url"><span class="lbl">Verify at:</span> <span class="val">{{ $verify_domain }}</span></div>

</div>

</body>
</html>
