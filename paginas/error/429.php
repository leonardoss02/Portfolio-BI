<?php
/** Ver comentário equivalente em paginas/error/500.php. */
$uriErro = $_SERVER['REQUEST_URI'] ?? '';
$prefixosApi = ['/biexplorer/', '/sql/'];
foreach ($prefixosApi as $prefixo) {
    if (str_starts_with($uriErro, $prefixo)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Muitas requisições. Aguarde alguns instantes e tente novamente.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muitas tentativas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { text-align: center; padding: 40px 20px; }
        .code { font-size: 5rem; font-weight: 800; color: #e2e8f0; line-height: 1; margin-bottom: 16px; }
        h1 { font-size: 1.2rem; font-weight: 600; color: #374151; margin-bottom: 8px; }
        p { color: #9ca3af; font-size: 0.9rem; }
        a { color: #3b82f6; text-decoration: none; margin-top: 24px; display: inline-block; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">429</div>
        <h1>Muitas tentativas</h1>
        <p>Você excedeu o limite de requisições. Aguarde alguns instantes e tente novamente.</p>
        <a href="/">← Voltar ao início</a>
    </div>
</body>
</html>
