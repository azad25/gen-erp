<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Locked</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #dc2626;">Account Locked</h1>
        
        <p>Dear {{ $user->name }},</p>
        
        <p>Your account has been locked due to multiple failed login attempts.</p>
        
        <p><strong>Locked Until:</strong> {{ $lockedUntil }}</p>
        
        <p>This is a security measure to protect your account. You will be able to login again after the lockout period expires.</p>
        
        <p>If you did not attempt to login, please contact support immediately.</p>
        
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <p style="color: #6b7280; font-size: 14px;">
            This is an automated email. Please do not reply.
        </p>
    </div>
</body>
</html>
