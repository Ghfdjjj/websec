<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body>
    <p>Dear {{ $name }},</p>
    
    <p>We received a request to reset your password. Click the link below to set a new password:</p>
    
    <p>
        <a href="{{ $link }}" target="_blank">
            Reset Password
        </a>
    </p>
    
    <p>This link will expire in 1 hour.</p>
    
    <p>If you did not request a password reset, please ignore this email.</p>
    
    <p>Best regards,<br>Your Application Team</p>
</body>
</html> 