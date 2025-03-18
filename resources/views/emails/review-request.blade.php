// resources/views/emails/review-request.blade.php
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        .content {
            margin-bottom: 30px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
        }
        .copy-notice {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #17a2b8;
            font-style: italic;
        }
    </style>
</head>
<body>
    @if(isset($copyNote))
    <div class="copy-notice">
        {{ $copyNote }}
    </div>
    @endif

    <div class="header">
        <div class="store-name">{{ $storeName }}</div>
    </div>
    
    <div class="content">
        {!! $content !!}
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
    </div>
</body>
</html>