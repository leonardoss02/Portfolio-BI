<?php
/**
 * O que o teste de fumaça visita.
 *
 * Cada entrada:
 *   'url'    rota com placeholders: {ano} {mes} {hoje} {inicioMes} {inicioAno} {ha29dias} {anoAnterior}
 *   'tipo'   'json' ou 'html'
 *   'exigir' (json) caminho no JSON => condição (ou lista de condições): '>0', '>=N', 'entre:A:B',
 *            'naoVazio', '===false', '===true', 'contem:valor'. No caminho, '*' percorre uma lista
 *            ('linhas.*.status' = o status de cada linha).
 *   'contem' (html) texto que a página precisa conter
 *   'contemRegex' (html) expressão regular que a página precisa satisfazer
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

// As páginas da Gestão de Eventos são desenhadas no servidor: cada uma precisa
// mostrar algo que só existe nos dados gerados (nomes criados em Fornecedores.php).
$marcadoresHotel = [
    '/hotel/transportes' => '/(Azul|Gol|Latam|Cometa)</u',
    '/hotel/equipamentos' => '/Áudio e Vídeo/u',
    '/hotel/videomakers' => '/Filmes/u',
    '/hotel/relatorio' => '/\d/u',
];
$entradas = array_map(fn($url) => str_starts_with($url, '/hotel')
    ? ['url' => $url, 'tipo' => 'html', 'contemRegex' => $marcadoresHotel[$url] ?? '/Hotel \p{Lu}\p{Ll}+ \p{Lu}/u']
    : ['url' => $url, 'tipo' => 'html'], $paginas);

$json = function (string $url, array $exigir = []) {
    return ['url' => $url, 'tipo' => 'json', 'exigir' => $exigir];
};

$ano = 'year={ano}';
$anoMes = 'year={ano}&months={mes}';
$mesAteHoje = 'data_inicio={inicioMes}&data_fim={hoje}';
// Métricas raras (combo, distrato) podem zerar em poucos dias; para elas, o ano até hoje.
$anoAteHoje = 'data_inicio={inicioAno}&data_fim={hoje}';

array_push($entradas,
    // Executivo
    $json("/executive/dados?$anoMes", [
        'global.custo_mkt' => '>0',
        'global.total_leads' => '>0',
        'global.leads_qualificados' => '>0',
        'global.cadeira_vendida' => '>0',
        'global.consultas_vendidas' => '>0',
    ]),
    $json("/executive/dados?$ano", [
        'global.combos_vendidos' => '>0',
        'global.faturamento_total' => '>0',
        'global.roi' => '>0',
        'global.custo_fisico' => '>0',
    ]),
    $json("/executive/digitalDados?$anoMes"),
    $json("/executive/especialistasDados?$mesAteHoje", ['kpis.agendadas' => '>0', 'kpis.realizadas' => '>0']),
    $json("/executive/especialistasDados?$anoAteHoje", [
        'kpis.fp_vendido' => '>0',
        'kpis.contrato_enviado' => '>0',
        'kpis.combos_vendidos' => '>0',
        'kpis.distrato' => '>0',
        'kpis.churn_fp' => '>0',
    ]),
    $json("/executive/analiticoDados?$mesAteHoje", ['especialistas' => 'naoVazio']),
    $json("/executive/serasaDados?$ano", ['resumo.comercial2.consultas_realizadas' => '>0']),
    $json('/executive/campanhas'),
    $json('/executive/forms'),
    $json('/executive/responsaveis'),

    // Comercial
    $json("/comercial/dados?$ano", ['evento.cb' => '>0', 'digital.cb' => '>0', 'retencao.cb' => '>0', 'totais.dist' => '>0']),
    $json("/comercial/yoyDados?$ano", ['totais.cb' => '>0', 'totais_ly.cb' => '>0']),
    $json("/comercial/cadeirasDados?$ano", ['totais.leads' => '>0', 'totais.cadeiras' => '>0']),
    $json("/comercial/comercial1Dados?$ano", ['evento.ranking.0.cad' => '>0', 'evento.ranking.0.cv' => '>0']),
    $json("/comercial/comercial2Dados?$ano", ['ranking.0.total_cr' => '>0', 'ranking.0.total_cb' => '>0']),
    $json("/comercial/comercial3Dados?$ano", ['ranking.0.agend' => '>0', 'ranking.0.realizada' => '>0']),
    $json('/comercial/forms'),
    $json('/comercial/responsaveis'),

    // Marketing
    $json("/marketing/dados?$ano", ['kpis.investimento' => '>0', 'tabelas.campanha.0.cliques' => '>0', 'kpis.oportunidades' => '>0']),
    $json("/marketing/comparativoDados?$ano"),
    $json("/marketing/eventosDados?$ano"),
    $json("/marketing/desempenhoDados?$ano", ['rankings.profissoes' => 'naoVazio', 'rankings.motivos' => 'naoVazio']),
    $json("/marketing/classificacaoDados?$ano"),
    $json("/marketing/classificacaoGrafico?$ano"),
    $json("/marketing/descartesDados?$ano", ['totais.com1' => '>0', 'totais.com2' => '>0', 'totais.com3' => '>0']),
    $json('/marketing/campanhas'),
    $json('/marketing/forms'),
    $json('/marketing/conjuntosAnuncio'),
    $json('/marketing/anuncios'),

    // Eventos
    $json("/eventos/dados?$ano", [
        'totais.qt_eventos' => '>0',
        'totais.cadeiras' => '>0',
        'totais.checkins' => '>0',
        'totais.consultas_vendidas' => '>0',
        'totais.consultas_realizadas' => '>0',
        'totais.fp_vendido' => '>0',
        'totais.combos' => '>0',
        'totais.faturamento' => '>0',
        'totais.oportunidades' => '>0',
        'totais.custo_fisico' => '>0',
    ]),
    // Prévia: 2 eventos passados e o próximo, nessa ordem.
    $json('/eventos/preview', ['eventos.1.is_proximo' => '===false', 'eventos.2.is_proximo' => '===true']),
    $json("/eventos/lista?$ano"),

    // Financeiro, Atendimento, Jurídico
    $json("/financeiro/dados?$ano", [
        'kpis.recebido' => '>0',
        'kpis.resgatado' => '>0',
        'kpis.emDia' => '>0',
        'kpis.atrasado' => '>0',
        'kpis.inadimplente' => '>0',
    ]),
    // Primeiro cliente do QuickBooks (série começa em 4001); o e-mail vem do card no CRM.
    $json('/financeiro/parcelas?customer=4001', ['rows' => 'naoVazio', 'rows.0.email' => 'naoVazio']),
    $json("/chatwoot/dados?$anoMes", [
        'kpis.total_leads' => '>0',
        'kpis.ingressos' => '>0',
        'kpis.consultas' => '>0',
        'kpis.abertas' => '>0',
        'kpis.resolvidas' => '>0',
        'kpis.trm_min' => '>0',
        'kpisPrev.total_leads' => '>0',
    ]),
    $json("/chatwoot/serie?$anoMes", ['serie' => 'naoVazio']),
    $json("/chatwoot/detalhe?$anoMes&responsavel=Helena"),
    $json("/law/dados?$ano", ['kpis.casos_totais' => '>0', 'kpis.casos_alta_prioridade' => '>0']),
    $json("/law/grafico?$ano"),
    $json('/law/responsaveis'),
    $json('/law/ciclos'),
    $json("/law/prioridadeDados?$ano", ['kpis.casos_alta_prioridade' => '>0']),
    $json("/law/prioridadeGrafico?$ano"),

    // Customer Success (Comercial 4)
    $json("/comercial4/dados?$ano", [
        'kpis.total_clientes' => '>0',
        'kpis.vendas' => '>0',
        'kpis.arquiteturas_agendar' => '>0',
        'kpis.agendadas' => '>0',
    ]),
    $json("/comercial4/ranking?$ano"),
    $json("/comercial4/performance?$ano"),
    $json("/comercial4/registros?$ano"),
    $json('/comercial4/opcoes'),
    $json('/comercial4/ligaDados', [
        'map.carteira' => 'entre:400:460',
        'map.eq' => 'entre:370:410',
        'vendas.semana.qtd' => '>0',
    ]),

    // Segmentos e pesquisas
    $json("/credenciados/dados?$ano", [
        'kpis.total_leads' => '>0',
        'kpis.qualificados' => '>0',
        'kpis.consultas_realizadas' => '>0',
        'kpis.combo_vendido' => '>0',
    ]),
    $json("/credenciados/grafico?$ano"),
    $json("/credenciados/registros?$ano"),
    $json("/dentistas/dados?$ano", [
        'kpis.leads' => '>0',
        'kpis.cadeira_vendida' => '>0',
        'kpis.consultas_realizadas' => '>0',
        'kpis.contrato_assinado' => '>0',
    ]),
    // Listas dos filtros: arrays na raiz do JSON.
    $json('/dentistas/eventos', ['0' => 'naoVazio']),
    $json('/dentistas/utmterms', ['0' => 'naoVazio']),
    $json('/dentistas/profissoes', ['0' => 'naoVazio']),
    $json('/dentistas/formacoes', ['0' => 'naoVazio']),
    $json("/npseventos/dados?$ano", [
        'totalComprou' => '>0',
        'totalNaoComprou' => '>0',
        'indisponivel' => '===false',
        'comprou.expGeral' => 'entre:0:10',
    ]),
    // Cruzamento com a planilha: precisa haver linhas que casam por e-mail, só pelo nome e pendentes.
    $json("/consultacom2/dados?$anoMes", [
        'linhas' => 'naoVazio',
        'planilha_indisponivel' => '===false',
        'linhas.*.status' => ['contem:OK', 'contem:OK (nome)', 'contem:Pendente'],
    ]),
    $json("/consultacom2/dadoseventos?$anoMes", ['linhas' => 'naoVazio']),
    $json('/consultacom2/dadosplanilha', ['linhas' => 'naoVazio', 'planilha_indisponivel' => '===false']),

    // Helena Castro e Jornada
    $json('/helenacastro/dados?data_inicio={ha29dias}&data_fim={hoje}', [
        'kpis.leads' => '>0',
        'kpis.consultas_vendidas' => '>0',
        'kpis.ingressos_vendidos' => '>0',
    ]),
    $json('/helenacastro/forms'),
    $json('/helenacastro/jornadaDados?data_inicio={inicioMes}&data_fim={hoje}&responsavel=Helena%20Castro', [
        'kpis.leads' => '>0',
        'kpis.carrinho' => '>0',
        'kpis.pagos' => '>0',
    ]),
    $json('/helenacastro/jornadaResponsaveis', ['1' => 'naoVazio']),
    // O primeiro lead da base sempre tem jornada (ver Jornada.php).
    $json('/helenacastro/trilha?id=500001', ['eventos' => 'naoVazio']),
    $json('/helenacastro/buscaCliente?q=ana'),

    // Gestão de Eventos
    $json('/hotel/dados/valortotal?anos={ano}'),
);

// Painéis por vendedor
foreach (['rafaeltorres', 'julianaprado', 'camiladuarte', 'beatrizlemos'] as $vendedor) {
    // A Beatriz não tem aba de eventos (o endpoint existe, mas a tela não usa).
    $exigirEventos = $vendedor === 'beatrizlemos' ? [] : ['totais.ingressos_vendidos' => '>0'];
    array_push($entradas,
        $json("/$vendedor/dados?$mesAteHoje", [
            'kpis.consultas_agendadas' => '>0',
            'kpis.consultas_realizadas' => '>0',
            'kpis.consultas_a_realizar' => '>0',
        ]),
        $json("/$vendedor/dados?$anoAteHoje", ['kpis.fp_realizado' => '>0', 'kpis.combos_vendidos' => '>0']),
        $json("/$vendedor/grafico?$mesAteHoje&modo=anoMes"),
        $json("/$vendedor/forms"),
        $json("/$vendedor/eventosDados?$ano", $exigirEventos),
    );
}

// Envelhecimento: gere a base com --hoje=2027-03-15 e rode com --apenas=envelhecimento.
// Prova que a conversão do custo de marketing funciona depois de 2026.
array_push($entradas,
    ['url' => '/executive/dados?year=2027&months=1,2,3', 'tipo' => 'json', 'grupo' => 'envelhecimento', 'exigir' => ['global.custo_mkt' => '>0']],
    ['url' => '/marketing/dados?year=2027', 'tipo' => 'json', 'grupo' => 'envelhecimento', 'exigir' => ['kpis.investimento' => '>0']],
    ['url' => '/eventos/dados?year=2027', 'tipo' => 'json', 'grupo' => 'envelhecimento', 'exigir' => ['totais.investimento_mkt' => '>0']],
);

return $entradas;
