<?php

/**
 * Helpers de view do sistema de Gestão de Eventos (hotéis).
 *
 * Incluído por template/hotel.php, antes de qualquer view.
 *
 * Estes arquivos ficam em paginas/hotel/partes/ e não em paginas/hotel/ porque
 * getPagina() resolve paginas/{controller}/{metodo}.php: um arquivo solto ali
 * viraria uma rota acessível (/hotel/helpers). Com a subpasta, /hotel/partes
 * procura paginas/hotel/partes.php, não encontra, e cai no 404.
 *
 * Nomes com prefixo `h` e guarda de function_exists porque o MRPHP inclui
 * templates e views no escopo global — template/eventos.php, por exemplo, já
 * declara navClass() e navAria() globalmente.
 */

if (!function_exists('hesc')) {
    /**
     * Escape obrigatório em 100% das interpolações, inclusive nas que
     * "obviamente" são números. O sistema antigo não escapava nada: qualquer
     * observação com <img src=x onerror=...> executava em todas as telas.
     */
    function hesc(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('hurl')) {
    /**
     * URL interna que carrega adiante o estado compartilhado entre as abas:
     * o recorte de data (?anos= e ?meses=), o de cidade (?cidade=) e o
     * modo embed (?to=1).
     *
     * Como cada aba é uma rota de verdade, esse estado precisa viajar na
     * querystring — senão trocar de aba perderia o filtro selecionado.
     * Passar a chave explicitamente sobrescreve o valor herdado, e passá-la
     * como null a remove (é assim que "Todos" e "Limpar" funcionam).
     */
    function hurl(MRPHP $app, string $rota = '', array $params = []): string
    {
        foreach (['anos', 'meses', 'cidade', 'to'] as $chave) {
            if (!array_key_exists($chave, $params) && ($_GET[$chave] ?? '') !== '') {
                $params[$chave] = $_GET[$chave];
            }
        }

        $params = array_filter(
            $params,
            static fn($v): bool => $v !== null && $v !== '' && $v !== false
        );

        $qs = $params ? '?' . http_build_query($params) : '';
        return $app->site . 'hotel' . ($rota !== '' ? '/' . ltrim($rota, '/') : '') . $qs;
    }
}

if (!function_exists('hmoeda')) {
    /**
     * Zero vira travessão, como no sistema antigo — num painel de custos,
     * "R$ 0,00" e "ainda não informado" são coisas diferentes, e a maior
     * parte dos zeros aqui é a segunda.
     */
    function hmoeda(mixed $v, bool $zeroComoTraco = true): string
    {
        $n = (float)$v;
        if ($n == 0.0 && $zeroComoTraco) {
            return '—';
        }
        return 'R$ ' . number_format($n, 2, ',', '.');
    }
}

if (!function_exists('hmoedaHtml')) {
    /**
     * Mesma formatação de hmoeda(), com o "R$" marcado como rótulo.
     *
     * Usada só nos números de destaque (indicador, faixa de resumo, total de
     * painel, valor de cartão): ali o símbolo em corpo cheio disputava
     * atenção com o dígito, e no número herói chegava a cair sozinho para a
     * linha de cima. Devolve HTML — já escapado — então NÃO passar por hesc().
     */
    function hmoedaHtml(mixed $v, bool $zeroComoTraco = true, bool $centavos = true): string
    {
        $texto = hmoeda($v, $zeroComoTraco);
        if (!str_starts_with($texto, 'R$ ')) {
            return hesc($texto);
        }

        $numero = substr($texto, 3);

        /*
         * Sem centavos nos números de destaque: "R$ 1.947.687,16" a 30px não
         * cabia em meio painel e empurrava o quarto indicador para a linha de
         * baixo. Em número de ordem de grandeza, os centavos não decidem nada
         * — quem precisa deles olha a tabela, que continua com eles.
         */
        if (!$centavos) {
            $numero = substr($numero, 0, -3);
        }

        return '<span class="gh-moeda">R$</span>' . hesc($numero);
    }
}

if (!function_exists('hdata')) {
    /** 'YYYY-MM-DD' => 'dd/mm/aaaa'; vazio => travessão. */
    function hdata(?string $d, string $vazio = '—'): string
    {
        if (!$d || strlen($d) < 10) {
            return $vazio;
        }
        return substr($d, 8, 2) . '/' . substr($d, 5, 2) . '/' . substr($d, 0, 4);
    }
}

if (!function_exists('hnum')) {
    function hnum(mixed $v, int $casas = 0): string
    {
        return number_format((float)$v, $casas, ',', '.');
    }
}

if (!function_exists('hjson')) {
    /**
     * Serializa dados para dentro de <script type="application/json">.
     *
     * As flags HEX_* impedem que uma string contendo `</script>` feche a tag
     * mais cedo e transforme dado em marcação — o motivo de nunca usar
     * `var x = <?= json_encode(...) ?>` solto.
     */
    function hjson(mixed $dados): string
    {
        return (string)json_encode(
            $dados,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
    }
}

if (!function_exists('hicone')) {
    /**
     * Ícone do sprite SVG (paginas/hotel/partes/sprite-icones.php).
     *
     * Sprite em vez da fonte Tabler por CDN: herda currentColor, funciona no
     * dark mode, não depende de rede externa e — o ponto principal — PHP e JS
     * consomem exatamente a mesma marcação, sem um mapa de paths duplicado.
     */
    function hicone(string $nome, string $classe = 'i'): string
    {
        $nome   = preg_replace('/[^a-z0-9\-]/', '', $nome) ?? '';
        $classe = hesc($classe);
        return '<svg class="' . $classe . '" aria-hidden="true"><use href="#i-' . $nome . '"></use></svg>';
    }
}

if (!function_exists('hbadge')) {
    /**
     * Rótulo e variante de cada status. Fonte única para as telas — no
     * sistema antigo cada aba tinha o seu próprio dicionário inline, e eles
     * já haviam divergido entre si.
     */
    function hbadge(string $entidade, ?string $status): array
    {
        $mapa = [
            // contrato do hotel
            'assinado'   => ['Contrato assinado', 'ok'],
            'aguardando' => ['Aguardando', 'info'],
            'negociacao' => ['Em negociação', 'warn'],
            'negociando' => ['Em negociação', 'warn'],
            'cancelado'  => ['Cancelado', 'danger'],
            // pagamento
            'pago'     => ['Pago', 'ok'],
            'parcial'  => ['Parcialmente pago', 'info'],
            'pendente' => ['Pendente', 'warn'],
            // logística
            'entregue' => ['Entregue', 'ok'],
            // transporte
            'reservado' => ['Reservado', 'info'],
            // entrega
            'no_prazo' => ['No prazo', 'ok'],
            'atrasado' => ['Atrasado', 'warn'],
            'problema' => ['Problema', 'danger'],
        ];

        $s = (string)$status;
        if ($s === '') {
            return ['Não informado', 'neutro'];
        }

        // 'aguardando' significa coisas diferentes conforme o contexto.
        if ($s === 'aguardando') {
            return $entidade === 'hotel'
                ? ['Aguardando assinatura', 'info']
                : ['Aguardando orçamento', 'neutro'];
        }

        return $mapa[$s] ?? [$s, 'neutro'];
    }
}
