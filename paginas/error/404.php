<?php
/**
 * Página exibida quando a URL não corresponde a nenhum dashboard.
 * É renderizada dentro de template/index.php, por isso é só um fragmento.
 */
?>
<style>
    .nao-encontrada { font-family: 'Inter', 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f6f9; text-align: center; padding: 40px 20px; }
    .nao-encontrada .codigo { font-size: 5rem; font-weight: 800; color: #e2e8f0; line-height: 1; margin-bottom: 16px; }
    .nao-encontrada h1 { font-size: 1.2rem; font-weight: 700; color: #374151; margin: 0 0 8px; }
    .nao-encontrada p { color: #6b7280; font-size: 0.9rem; margin: 0; }
    .nao-encontrada a { color: #3b82f6; text-decoration: none; margin-top: 24px; display: inline-block; font-size: 0.875rem; }
</style>
<div class="nao-encontrada">
    <div>
        <div class="codigo">404</div>
        <h1>Página não encontrada</h1>
        <p>O endereço digitado não corresponde a nenhum dashboard.</p>
        <a href="/">← Voltar ao início</a>
    </div>
</div>
