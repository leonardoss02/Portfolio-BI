<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro — MRPHP Dev</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f1117; color: #e2e8f0; padding: 40px 20px; min-height: 100vh; }
        .container { max-width: 960px; margin: 0 auto; }
        .badge { display: inline-block; background: #ef4444; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 14px; }
        h1 { font-size: 1.4rem; font-weight: 700; color: #f87171; margin-bottom: 10px; word-break: break-all; }
        .message { font-size: 1rem; color: #cbd5e1; margin-bottom: 24px; line-height: 1.6; background: #1e293b; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444; }
        .location { font-family: monospace; font-size: 0.82rem; color: #7dd3fc; margin-bottom: 28px; }
        .location em { color: #64748b; font-style: normal; }
        .section-title { font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #475569; margin-bottom: 10px; }
        .trace { background: #1e293b; border-radius: 8px; overflow: hidden; border: 1px solid #334155; }
        .trace-item { padding: 10px 16px; border-bottom: 1px solid #334155; font-family: monospace; font-size: 0.78rem; }
        .trace-item:last-child { border-bottom: none; }
        .trace-num { color: #475569; margin-right: 10px; min-width: 24px; display: inline-block; }
        .trace-file { color: #7dd3fc; }
        .trace-line { color: #fbbf24; }
        .trace-call { color: #86efac; display: block; margin-top: 3px; padding-left: 34px; }
        .env-badge { display: inline-block; background: #1e3a5f; color: #7dd3fc; font-size: 0.7rem; padding: 2px 8px; border-radius: 3px; margin-bottom: 24px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">500 — Ambiente DEV</div>
        <h1><?= htmlspecialchars(get_class($e)) ?></h1>
        <div class="message"><?= htmlspecialchars($e->getMessage()) ?></div>
        <div class="location">
            <em>Arquivo: </em><?= htmlspecialchars($e->getFile()) ?> <em> — Linha: </em><?= $e->getLine() ?>
        </div>
        <p class="section-title">Stack Trace</p>
        <div class="trace">
            <?php foreach ($e->getTrace() as $i => $frame): ?>
            <div class="trace-item">
                <span class="trace-num">#<?= $i ?></span>
                <span class="trace-file"><?= htmlspecialchars($frame['file'] ?? '[interno]') ?></span>
                <?php if (!empty($frame['line'])): ?>
                    <span class="trace-line">:<?= $frame['line'] ?></span>
                <?php endif; ?>
                <span class="trace-call"><?= htmlspecialchars(($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . '()') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
