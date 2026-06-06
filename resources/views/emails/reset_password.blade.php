<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f5f7;
            padding: 20px;
            color: #333333;
        }
        .container {
            max-width: 600px;
            background-color: #ffffff;
            margin: 0 auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #7367f0;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            background-color: #7367f0;
            color: #ffffff !important;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            display: inline-block;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777777;
            text-align: center;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Prometrica Academy</div>
        </div>
        <p>Hello,</p>
        <p>You are receiving this email because we received a password reset request for your account.</p>
        <p>Please click the button below to reset your password. This link will expire shortly.</p>
        
        <div class="button-container">
            <a href="{{ $resetUrl }}" class="button" target="_blank">Reset Password</a>
        </div>
        
        <p>If you did not request a password reset, no further action is required.</p>
        
        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>&copy; {{ date('Y') }} Prometrica Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
