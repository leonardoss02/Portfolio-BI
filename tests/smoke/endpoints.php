<?php
/**
 * O que o teste de fumaça visita.
 *
 * Cada entrada:
 *   'url'    rota com placeholders: {ano} {mes} {hoje} {inicioMes} {ha29dias} {anoAnterior}
 *   'tipo'   'json' ou 'html'
 *   'exigir' (json) caminho no JSON => condição: '>0', '>=N', 'entre:A:B', 'naoVazio', '===false', '===true', 'contem:valor'
 *   'contem' (html) texto que a página precisa conter
 *   'grupo'  opcional; entradas com grupo só rodam com --apenas=grupo
 *
 * Os parâmetros de cada endpoint são os mesmos que a view manda no fetch.
 * Os caminhos de 'exigir' são as chaves reais devolvidas pelo método do controller.
 */

$paginas = [
    '/', '/executive', '/executive/digital', '/executive/especialistas', '/executive/analitico',
    '/executive/serasa', '/executive/dicionario',
    '/comercial', '/comercial/yoy', '/comercial/cadeiras', '/comercial/comercial1', '/comercial/comercial2',
    '/comercial/comercial3', '/comercial/dicionario',
    '/marketing', '/marketing/comparativo', '/marketing/eventos', '/marketing/desempenho',
    '/marketing/descartes', '/marketing/classificacao', '/marketing/dicionario',
    '/eventos', '/eventos/detalhamento', '/eventos/relatorio', '/eventos/dicionario',
    '/financeiro', '/financeiro/dicionario',
    '/chatwoot', '/chatwoot/dicionario',
    '/law', '/law/prioridade', '/law/dicionario',
    '/comercial4', '/comercial4/liga', '/comercial4/dicionario',
    '/credenciados', '/credenciados/dicionario',
    '/dentistas', '/dentistas/dicionario',
    '/npseventos', '/npseventos/dicionario',
    '/consultacom2', '/consultacom2/planilha', '/consultacom2/eventos',
    '/rafaeltorres', '/rafaeltorres/eventos', '/rafaeltorres/dicionario',
    '/julianaprado', '/julianaprado/eventos', '/julianaprado/dicionario',
    '/camiladuarte', '/camiladuarte/eventos', '/camiladuarte/dicionario',
    '/beatrizlemos', '/beatrizlemos/dicionario',
    '/helenacastro', '/helenacastro/jornada', '/helenacastro/dicionario',
    '/hotel', '/hotel/lista', '/hotel/valortotal', '/hotel/videomakers', '/hotel/equipamentos',
    '/hotel/transportes', '/hotel/frete', '/hotel/materiais', '/hotel/custosextras',
    '/hotel/calendario', '/hotel/relatorio',
];

$entradas = array_map(fn($url) => ['url' => $url, 'tipo' => 'html'], $paginas);

$json = function (string $url, array $exigir = []) {
    return ['url' => $url, 'tipo' => 'json', 'exigir' => $exigir];
};

$ano = 'year={ano}';
$anoMes = 'year={ano}&months={mes}';
$mesAteHoje = 'data_inicio={inicioMes}&data_fim={hoje}';

array_push($entradas,
    // Executivo
    $json("/executive/dados?$anoMes", ['global.custo_mkt' => '>0']),
    $json("/executive/digitalDados?$anoMes"),
    $json("/executive/especialistasDados?$mesAteHoje"),
    $json("/executive/analiticoDados?$mesAteHoje"),
    $json("/executive/serasaDados?$ano"),
    $json('/executive/campanhas'),
    $json('/executive/forms'),
    $json('/executive/responsaveis'),

    // Comercial
    $json("/comercial/dados?$ano"),
    $json("/comercial/yoyDados?$ano"),
    $json("/comercial/cadeirasDados?$ano"),
    $json("/comercial/comercial1Dados?$ano"),
    $json("/comercial/comercial2Dados?$ano"),
    $json("/comercial/comercial3Dados?$ano"),
    $json('/comercial/forms'),
    $json('/comercial/responsaveis'),

    // Marketing
    $json("/marketing/dados?$ano", ['kpis.investimento' => '>0', 'tabelas.campanha.0.cliques' => '>0']),
    $json("/marketing/comparativoDados?$ano"),
    $json("/marketing/eventosDados?$ano"),
    $json("/marketing/desempenhoDados?$ano"),
    $json("/marketing/classificacaoDados?$ano"),
    $json("/marketing/classificacaoGrafico?$ano"),
    $json("/marketing/descartesDados?$ano"),
    $json('/marketing/campanhas'),
    $json('/marketing/forms'),
    $json('/marketing/conjuntosAnuncio'),
    $json('/marketing/anuncios'),

    // Eventos
    $json("/eventos/dados?$ano", ['totais.qt_eventos' => '>0']),
    // Prévia: 2 eventos passados e o próximo, nessa ordem.
    $json('/eventos/preview', ['eventos.1.is_proximo' => '===false', 'eventos.2.is_proximo' => '===true']),
    $json("/eventos/lista?$ano"),

    // Financeiro, Atendimento, Jurídico
    $json("/financeiro/dados?$ano"),
    $json("/chatwoot/dados?$anoMes"),
    $json("/chatwoot/serie?$anoMes"),
    $json("/chatwoot/detalhe?$anoMes&responsavel=Helena"),
    $json("/law/dados?$ano"),
    $json("/law/grafico?$ano"),
    $json('/law/responsaveis'),
    $json('/law/ciclos'),
    $json("/law/prioridadeDados?$ano"),
    $json("/law/prioridadeGrafico?$ano"),

    // Customer Success (Comercial 4)
    $json("/comercial4/dados?$ano"),
    $json("/comercial4/ranking?$ano"),
    $json("/comercial4/performance?$ano"),
    $json("/comercial4/registros?$ano"),
    $json('/comercial4/opcoes'),
    $json('/comercial4/ligaDados'),

    // Segmentos e pesquisas
    $json("/credenciados/dados?$ano"),
    $json("/credenciados/grafico?$ano"),
    $json("/credenciados/registros?$ano"),
    $json("/dentistas/dados?$ano"),
    $json('/dentistas/eventos'),
    $json('/dentistas/utmterms'),
    $json('/dentistas/profissoes'),
    $json('/dentistas/formacoes'),
    $json("/npseventos/dados?$ano"),
    $json("/consultacom2/dados?$anoMes"),
    $json("/consultacom2/dadoseventos?$anoMes"),
    $json('/consultacom2/dadosplanilha'),

    // Helena Castro e Jornada
    $json('/helenacastro/dados?data_inicio={ha29dias}&data_fim={hoje}'),
    $json('/helenacastro/forms'),
    $json('/helenacastro/jornadaDados?data_inicio={inicioMes}&data_fim={hoje}&responsavel=Helena%20Castro'),
    $json('/helenacastro/jornadaResponsaveis'),
    $json('/helenacastro/buscaCliente?q=ana'),

    // Gestão de Eventos
    $json('/hotel/dados/valortotal?anos={ano}'),
);

// Painéis por vendedor
foreach (['rafaeltorres', 'julianaprado', 'camiladuarte', 'beatrizlemos'] as $vendedor) {
    array_push($entradas,
        $json("/$vendedor/dados?$mesAteHoje"),
        $json("/$vendedor/grafico?$mesAteHoje&modo=anoMes"),
        $json("/$vendedor/forms"),
        $json("/$vendedor/eventosDados?$ano"),
    );
}

return $entradas;
