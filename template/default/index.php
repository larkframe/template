<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'LarkFrame' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; background: #f5f5f5; }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 2em; margin-bottom: 16px; color: #1a1a1a; }
        p { line-height: 1.6; margin-bottom: 12px; color: #666; }
        .card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .links { list-style: none; }
        .links li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .links li:last-child { border-bottom: none; }
        .links a { color: #0066cc; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .method { display: inline-block; background: #e8f4e8; color: #2d7a2d; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-family: monospace; margin-right: 8px; }
        footer { text-align: center; color: #999; margin-top: 40px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Hello, <?= htmlspecialchars($username ?? 'World') ?>!</h1>
            <p>Welcome to LarkFrame Framework.</p>
        </div>

        <div class="card">
            <h3>API Examples</h3>
            <ul class="links">
                <li><span class="method">GET</span><a href="/api">Request Info</a></li>
                <li><span class="method">GET</span><a href="/api/db">Database Query</a></li>
                <li><span class="method">GET</span><a href="/api/cache">Cache Demo</a></li>
                <li><span class="method">GET</span><a href="/api/queue">Queue Push</a></li>
                <li><span class="method">GET</span><a href="/api/util">Util Demo</a></li>
            </ul>
        </div>

        <footer>Powered by LarkFrame</footer>
    </div>
</body>
</html>
