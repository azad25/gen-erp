<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #1a56db; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        .body { padding: 32px; line-height: 1.6; color: #333; }
        .footer { padding: 16px 32px; background: #f9fafb; color: #6b7280; font-size: 12px; text-align: center; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GenERP BD</h1>
        </div>
        <div class="body">
            {!! nl2br(e($body)) !!}
        </div>
        <div class="footer">
            {{ __('This is an automated notification from GenERP BD.') }}<br>
            {{ __('You can manage your notification preferences in Settings.') }}
        </div>
    </div>
</body>
</html>
