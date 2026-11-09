<?php

/**
 * Controle de acesso dos dashboards: HTTP Basic Auth por padrão, liberado
 * sem login apenas quando a página é embutida via <iframe> por um site da
 * whitelist (?to=1 + Sec-Fetch-Dest: iframe + Referer permitido). Ganchado
 * via {template}_controle() — chamado pelo framework após o controller real
 * de cada dashboard, antes de qualquer HTML ser renderizado.
 */
class home extends MRPHP{

    public function index(){

    }


    public function index_controle(){
        $this->verificarAcessoDashboard();
    }

    public function executive_controle(){
        $this->verificarAcessoDashboard();
    }

    public function eventos_controle(){
        $this->verificarAcessoDashboard();
    }

    public function financeiro_controle(){
        $this->verificarAcessoDashboard();
    }

    public function chatwoot_controle(){
        $this->verificarAcessoDashboard();
    }

    public function rafaeltorres_controle(){
        $this->verificarAcessoDashboard();
    }

    public function julianaprado_controle(){
        $this->verificarAcessoDashboard();
    }

    public function beatrizlemos_controle(){
        $this->verificarAcessoDashboard();
    }

    public function camiladuarte_controle(){
        $this->verificarAcessoDashboard();
    }

    public function law_controle(){
        $this->verificarAcessoDashboard();
    }

    public function comercial4_controle(){
        $this->verificarAcessoDashboard();
    }

    public function helenacastro_controle(){
        $this->verificarAcessoDashboard();
    }

    public function marketing_controle(){
        $this->verificarAcessoDashboard();
    }

    public function consultacom2_controle(){
        $this->verificarAcessoDashboard();
    }

    public function comercial_controle(){
        $this->verificarAcessoDashboard();
    }

    public function dentistas_controle(){
        $this->verificarAcessoDashboard();
    }

    public function credenciados_controle(){
        $this->verificarAcessoDashboard();
    }

    public function npseventos_controle(){
        $this->verificarAcessoDashboard();
    }

    /*
     * Sistema de Gestao de Eventos (hoteis). Segunda camada de protecao: o
     * framework chama este gancho DEPOIS do metodo do controller, entao ele
     * cobre as paginas HTML mas nunca chega a rodar num endpoint JSON, que
     * termina em exit. Por isso hotel_controle.php tambem chama a sua propria
     * guarda na primeira linha de cada metodo.
     */
    public function hotel_controle(){
        $this->verificarAcessoDashboard();
    }

    private function verificarAcessoDashboard(): void
    {
        $whitelist = array_filter(array_map('trim', explode(',', (string)$this->lerEnv('DASH_IFRAME_ALLOWLIST'))));

        header("Content-Security-Policy: frame-ancestors 'self' " . implode(' ', $whitelist));

        $to1 = ($_GET['to'] ?? '') === '1';
        $isIframe = ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'iframe';

        $refererOrigem = '';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $scheme = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
            $host   = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if ($scheme && $host) {
                $refererOrigem = $scheme . '://' . $host;
            }
        }
        $refererPermitido = $refererOrigem !== '' && in_array($refererOrigem, $whitelist, true);

        if ($to1 && $isIframe && $refererPermitido) {
            return;
        }

        $this->exigirBasicAuth();
    }

    private function exigirBasicAuth(): void
    {
        $usuarioEsperado = (string)$this->lerEnv('DASH_AUTH_USER');
        $senhaEsperada   = (string)$this->lerEnv('DASH_AUTH_PASSWORD');

        $usuario = $_SERVER['PHP_AUTH_USER'] ?? null;
        $senha   = $_SERVER['PHP_AUTH_PW'] ?? null;

        if ($usuario === null && !empty($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Basic ')) {
            $decodificado = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
            if ($decodificado !== false && str_contains($decodificado, ':')) {
                [$usuario, $senha] = explode(':', $decodificado, 2);
            }
        }

        $valido = $usuario !== null
            && $senha !== null
            && hash_equals($usuarioEsperado, (string)$usuario)
            && hash_equals($senhaEsperada, (string)$senha);

        if (!$valido) {
            header('WWW-Authenticate: Basic realm="Acme EUA BI"');
            http_response_code(401);
            echo 'Acesso negado.';
            exit;
        }
    }

    private function lerEnv(string $chave): ?string
    {
        $path = __DIR__ . '/../.env';
        if (!file_exists($path)) {
            return null;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }
            $pos = strpos($linha, '=');
            if ($pos === false) {
                continue;
            }
            $nome = trim(substr($linha, 0, $pos));
            if ($nome === $chave) {
                return trim(trim(substr($linha, $pos + 1)), '"\'');
            }
        }

        return null;
    }

}
?>
