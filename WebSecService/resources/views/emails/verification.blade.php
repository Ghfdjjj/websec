<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>
<body>
    <p>Dear {{ $name }},</p>
    
    <p>Click on the following link to verify your account:</p>
    
    <p>
        <a href="{{ $link }}" target="_blank">
            Verification Link
        </a>
    </p>
    
    <p>If you did not create an account, no further action is required.</p>
    
    <p>Best regards,<br>Your Application Team</p>
</body>
</html> 