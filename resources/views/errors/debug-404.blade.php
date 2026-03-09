<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? '404' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f8fafc; }
        .box { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 16px; }
        h1 { color: #dc2626; font-size: 1.25rem; margin: 0 0 12px 0; }
        .message { background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; margin: 12px 0; font-family: monospace; word-break: break-all; }
        .trace { font-size: 0.75rem; color: #64748b; white-space: pre-wrap; max-height: 300px; overflow: auto; }
        .url { margin-top: 12px; color: #475569; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $title ?? '404 Not Found' }}</h1>
        <p>This page is shown because <code>APP_DEBUG=true</code>. It explains why you got a 404.</p>
        <div class="url"><strong>URL:</strong> {{ request()->fullUrl() }}</div>
    </div>
    <div class="box">
        <h2 style="font-size: 1rem; margin: 0 0 8px 0;">Error message</h2>
        <div class="message">{{ $message }}</div>
        @if(!empty($trace))
        <details style="margin-top: 12px;">
            <summary style="cursor: pointer;">Stack trace</summary>
            <pre class="trace">{{ $trace }}</pre>
        </details>
        @endif
    </div>
    <p style="color: #64748b; font-size: 0.875rem;">
        Fix the cause above, then reload. Set <code>APP_DEBUG=false</code> in production when done.
    </p>
</body>
</html>
