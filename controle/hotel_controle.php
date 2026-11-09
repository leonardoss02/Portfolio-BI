<?php

require_once __DIR__ . '/../lib/hotel/Repositorio.php';
require_once __DIR__ . '/../lib/hotel/Calculo.php';
// Helpers de view tambem aqui: o template os carrega, mas o template so roda
// DEPOIS do metodo do controller — e o payload dos graficos usa hbadge().
require_once __DIR__ . '/../paginas/hotel/partes/helpers.php';

/**
 * Gestão de Eventos (hotéis) — sistema OPERACIONAL.
 *
 * Diferente dos demais controllers deste projeto, este ESCREVE dados. A regra
 * "todo dashboard é SELECT" do CLAUDE.md vale para os painéis de BI, que leem
 * o CRM; aqui a escrita é o propósito. As salvaguardas correspondentes:
 *
 *   - autenticação na primeira linha de todo método, inclusive nos JSON;
 *   - toda entrada passa por HotelSchema::normalizar() + validar();
 *   - escrita apenas via repositório, nunca SQL montado à mão;
 *   - exclusão é lógica (deletado_*), nunca DELETE.
 *
 * Sobre a autenticação: o framework chama home::{template}_controle() DEPOIS
 * do método do controller (mrphp.php:513-519). Para uma página HTML isso ainda
 * funciona, porque nada foi renderizado ainda; para um endpoint JSON, que
 * termina em exit, o gancho nunca chega a rodar. Por isso a guarda é chamada
 * aqui dentro — o gancho fica como segunda camada, não como a única.
 */
class hotel extends MRPHP
{
    /**
     * Nome do formulario para o token CSRF. Um so para todo o sistema: o
     * token e de uso unico e rotacionado a cada resposta de escrita, entao
     * separar por entidade nao acrescentaria protecao e faria a tela ter de
     * administrar varios tokens ao mesmo tempo.
     */
    private const FORM = 'gestao_hotel';

    private ?HotelRepositorio $repositorio = null;
    private ?array $eventosCrm = null;
    private ?HotelCalculo $calculo = null;

    // =====================================================================
    // Telas
    // =====================================================================

    /**
     * GET /hotel — visão geral.
     *
     * O parâmetro existe por causa do roteador: quando o método pedido não
     * existe no controller, o MRPHP chama index() passando o segmento da URL
     * (mrphp.php:594-606). Sem tratar isso, /hotel/qualquer-coisa devolveria
     * a visão geral com HTTP 200, escondendo link quebrado e erro de digitação.
     */
    public function index($rotaInexistente = null)
    {
        $this->exigirAcesso();

        if ($rotaInexistente !== null && $rotaInexistente !== '') {
            $this->naoEncontrado((string)$rotaInexistente);
        }

        $this->setTemplate('hotel');

        $shell = $this->montarShell('index', 'Visão geral', 'Hotéis, equipamentos A/V e logística por evento');
        $vg    = $this->calc()->visaoGeral($shell['periodoAtivo']);

        $this->setVars([
            'shell' => $shell,
            'vg'    => $vg,
            // Payload dos graficos: rotulos e series ja prontos, para o JS so
            // desenhar. Nenhum calculo se repete no cliente.
            'boot'  => $this->bootGraficos($vg),
        ]);
    }

    /** GET /hotel/valortotal — consolidação de custos por evento */
    public function valortotal()
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        $dados = $this->dadosValorTotal($this->filtroDaRequisicao());

        /*
         * Cidade recorta no SERVIDOR (vai na URL, como Ano e Mês) porque os
         * totais do rodapé têm de refletir o recorte — filtrar linha no
         * cliente deixaria o total somando o que sumiu da tela.
         */
        $opcoesCidade = [];
        foreach ($dados['cidades'] as $nome => $qtd) {
            $opcoesCidade[] = ['valor' => $nome, 'rotulo' => $nome, 'n' => $qtd];
        }

        $shell = $this->montarShell(
            'valortotal',
            'Valor total',
            'Custo consolidado de cada evento',
            [],
            [['chave' => 'cidade', 'rotulo' => 'Cidade', 'servidor' => true,
              'valores' => $dados['cidade'], 'opcoes' => $opcoesCidade]],
            'Hotel ou cidade'
        );

        $this->setVars([
            'shell' => $shell,
            'dados' => $dados,
            'boot'  => $dados,
        ]);
    }

    /** GET /hotel/videomakers — cadastro de videomakers */
    public function videomakers()
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        $dados = $this->dadosVideomakers($this->filtroDaRequisicao());

        $shell = $this->montarShell(
            'videomakers',
            'Videomakers',
            'Vídeo e foto contratados por evento',
            [['id' => 'gh-vm-novo', 'rotulo' => 'Novo videomaker', 'destaque' => true]],
            [['chave' => 'situacao', 'rotulo' => 'Situação', 'dataset' => 'status',
              'opcoes' => $this->opcoesStatus(
                  'videomaker',
                  HotelSchema::campos('videomaker')['status']['valores'],
                  $dados['totais']['porStatus'] ?? []
              )]],
            'Videomaker ou cidade'
        );

        $this->setVars([
            'shell'  => $shell,
            'dados'  => $dados,
            'boot'   => $dados,
            'token'  => $this->getFormToken(self::FORM),
            'eventos' => $this->eventosParaSelect(),
        ]);
    }

    /** GET /hotel/lista — cadastro de hotéis/eventos */
    public function lista()
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        $hoteis = array_map(
            function (array $h): array {
                $chk = $this->calc()->checklist($h);
                return $h + [
                    '_custo'      => $this->calc()->custoDoHotel($h),
                    '_pendencias' => count($chk['pendencias']),
                    '_pct'        => $chk['pct'],
                    '_equipes'    => $this->calc()->porHotel('equipamento')[(int)$h['id_hotel']] ?? [],
                ];
            },
            $this->calc()->hoteis($this->filtroDaRequisicao())
        );

        /*
         * As opções repetem exatamente as tags que cada cartão escreve em
         * data-tags: mesmo vocabulário nos dois lugares, para o dropdown e o
         * cartão não divergirem como divergiam os chips por tela.
         */
        $conta = static fn(callable $teste): int => count(array_filter($hoteis, $teste));

        $opcoesHotel = [
            ['valor' => 'assinado',     'rotulo' => 'Assinados',        'n' => $conta(static fn($h) => $h['status_hotel'] === 'assinado')],
            ['valor' => 'aguardando',   'rotulo' => 'Aguardando',       'n' => $conta(static fn($h) => $h['status_hotel'] === 'aguardando')],
            ['valor' => 'negociacao',   'rotulo' => 'Em negociação',    'n' => $conta(static fn($h) => $h['status_hotel'] === 'negociacao')],
            ['valor' => 'cancelado',    'rotulo' => 'Cancelados',       'n' => $conta(static fn($h) => $h['status_hotel'] === 'cancelado')],
            ['valor' => 'sem-nf',       'rotulo' => 'Sem NF',           'n' => $conta(static fn($h) => !$h['nf_hotel'])],
            ['valor' => 'terceirizado', 'rotulo' => 'A/V terceirizado', 'n' => $conta(static fn($h) => $h['responsavelAv_hotel'] === 'terceirizado')],
            ['valor' => 'pendencias',   'rotulo' => 'Com pendências',   'n' => $conta(static fn($h) => $h['_pendencias'] > 0)],
            ['valor' => 'top',          'rotulo' => 'Bem avaliados',    'n' => $conta(static fn($h) => (int)$h['rating_hotel'] >= 4)],
        ];

        $shell = $this->montarShell(
            'lista',
            'Hotéis',
            'Contratos, nota fiscal, ordem de compra e quartos',
            [['id' => 'gh-h-novo', 'rotulo' => 'Novo hotel', 'destaque' => true]],
            [['chave' => 'situacao', 'rotulo' => 'Situação', 'dataset' => 'tags', 'opcoes' => $opcoesHotel]],
            'Hotel, cidade ou UF'
        );

        $this->setVars([
            'shell'      => $shell,
            'hoteis'     => $hoteis,
            'eventosCrm' => $this->eventosDoCrm(),
            'boot'       => ['periodo' => $shell['periodoAtivo'], 'total' => count($hoteis)],
            'token'      => $this->getFormToken(self::FORM),
        ]);
    }

    /** GET /hotel/equipamentos — fornecedores de A/V */
    public function equipamentos()
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        $dados = $this->calc()->listaFilhos('equipamento', $this->filtroDaRequisicao());

        $shell = $this->montarShell(
            'equipamentos',
            'Equipamentos A/V',
            'Fornecedores terceirizados por evento',
            [['id' => 'gh-eq-novo', 'rotulo' => 'Novo fornecedor', 'destaque' => true]],
            [['chave' => 'situacao', 'rotulo' => 'Situação', 'dataset' => 'status',
              'opcoes' => $this->opcoesStatus(
                  'equipamento',
                  HotelSchema::campos('equipamento')['status']['valores'],
                  $dados['totais']['porStatus'] ?? []
              )]],
            'Fornecedor, cidade ou OC'
        );

        // Eventos marcados como terceirizados que ainda não têm fornecedor —
        // no sistema antigo isso ficava invisível, porque o A/V era um bloco
        // de colunas dentro do hotel e um fornecedor em branco não aparecia
        // em lista nenhuma.
        $semFornecedor = [];
        foreach ($this->calc()->hoteis($shell['periodoAtivo']) as $h) {
            if ($h['responsavelAv_hotel'] !== 'terceirizado' || $h['status_hotel'] === 'cancelado') {
                continue;
            }
            if (!($this->calc()->porHotel('equipamento')[(int)$h['id_hotel']] ?? [])) {
                $semFornecedor[] = [
                    'id'     => (int)$h['id_hotel'],
                    'cidade' => $h['cidade_hotel'],
                    'data'   => $h['data_hotel'],
                    'nome'   => $h['nome_hotel'],
                ];
            }
        }

        $this->setVars([
            'shell'         => $shell,
            'dados'         => $dados,
            'semFornecedor' => $semFornecedor,
            'boot'          => $dados,
            'token'         => $this->getFormToken(self::FORM),
            'eventos'       => $this->eventosParaSelect(),
        ]);
    }

    /**
     * Cadastros que compartilham a mesma tela (lista + modal gerado do
     * Schema): só as colunas mudam. Quatro views quase idênticas seria
     * repetir o erro que fez as regras divergirem no sistema antigo.
     *
     * Cada coluna: rotulo, campo (sem sufixo) e tipo de exibição
     * (principal | evento | badge | moeda | data | inteiro | texto).
     */
    private const CADASTROS = [
        'transportes' => [
            'entidade'  => 'transporte',
            'titulo'    => 'Transportes',
            'subtitulo' => 'Aéreo, rodoviário e aplicativo por evento',
            'botaoNovo' => 'Novo transporte',
            'singular'  => 'transporte',
            'plural'    => 'transportes',
            'semVinculoRotulo' => 'sem evento vinculado',
            'colunas'   => [
                ['rotulo' => 'Trecho',     'campo' => 'trecho', 'tipo' => 'principal', 'sub' => ['cia', 'passageiro']],
                ['rotulo' => 'Evento',     'campo' => null,     'tipo' => 'evento'],
                ['rotulo' => 'Tipo',       'campo' => 'tipo',   'tipo' => 'texto'],
                ['rotulo' => 'Viagem',     'campo' => 'dataViagem', 'tipo' => 'data'],
                ['rotulo' => 'Status',     'campo' => 'status', 'tipo' => 'badge'],
                ['rotulo' => 'Qtd',        'campo' => 'qtd',    'tipo' => 'inteiro'],
                ['rotulo' => 'Valor',      'campo' => 'valor',  'tipo' => 'moeda'],
            ],
        ],

        'frete' => [
            'entidade'  => 'frete',
            'titulo'    => 'Frete',
            'subtitulo' => 'Envio de materiais até o hotel',
            'botaoNovo' => 'Novo frete',
            'singular'  => 'envio',
            'plural'    => 'envios',
            'semVinculoRotulo' => 'sem evento vinculado',
            'colunas'   => [
                ['rotulo' => 'Documento',  'campo' => 'doc',    'tipo' => 'principal', 'sub' => ['obs']],
                ['rotulo' => 'Evento',     'campo' => null,     'tipo' => 'evento'],
                ['rotulo' => 'Envio',      'campo' => 'dataEnvio',   'tipo' => 'data'],
                ['rotulo' => 'Entrega',    'campo' => 'dataEntrega', 'tipo' => 'data'],
                ['rotulo' => 'Status',     'campo' => 'status', 'tipo' => 'badge'],
                ['rotulo' => 'Valor',      'campo' => 'valor',  'tipo' => 'moeda'],
            ],
        ],

        'materiais' => [
            'entidade'  => 'material',
            'titulo'    => 'Materiais',
            'subtitulo' => 'Kits enviados e solicitações avulsas',
            'botaoNovo' => 'Novo envio',
            'singular'  => 'envio',
            'plural'    => 'envios',
            'semVinculoRotulo' => 'avulsos, sem evento',
            'colunas'   => [
                ['rotulo' => 'Evento',     'campo' => null,     'tipo' => 'evento'],
                ['rotulo' => 'Status',     'campo' => 'status', 'tipo' => 'badge'],
                ['rotulo' => 'Kits',       'campo' => 'qtde',   'tipo' => 'inteiro'],
                ['rotulo' => 'Valor',      'campo' => 'valor',  'tipo' => 'moeda'],
            ],
        ],

        'custosextras' => [
            'entidade'  => 'custoExtra',
            'titulo'    => 'Custos extras',
            'subtitulo' => 'Despesas do evento fora das demais categorias',
            'botaoNovo' => 'Novo custo extra',
            'singular'  => 'custo',
            'plural'    => 'custos',
            'semVinculoRotulo' => 'sem evento vinculado',
            'colunas'   => [
                ['rotulo' => 'Descrição',  'campo' => 'descricao', 'tipo' => 'principal', 'sub' => ['obs']],
                ['rotulo' => 'Evento',     'campo' => null,        'tipo' => 'evento'],
                ['rotulo' => 'OC',         'campo' => 'ocNum',     'tipo' => 'texto'],
                ['rotulo' => 'Status',     'campo' => 'status',    'tipo' => 'badge'],
                ['rotulo' => 'Valor',      'campo' => 'valor',     'tipo' => 'moeda'],
            ],
        ],

    ];

    public function transportes() { $this->renderCadastro('transportes'); }
    public function frete()       { $this->renderCadastro('frete'); }
    public function materiais()   { $this->renderCadastro('materiais'); }
    public function custosextras() { $this->renderCadastro('custosextras'); }

    private function renderCadastro(string $rota): void
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        $cfg   = self::CADASTROS[$rota];
        $ent   = $cfg['entidade'];
        $dados = $this->calc()->listaFilhos($ent, $this->filtroDaRequisicao());

        $valores = HotelSchema::campos($ent)['status']['valores'] ?? [];
        $shell   = $this->montarShell(
            $rota,
            $cfg['titulo'],
            $cfg['subtitulo'],
            [['id' => 'gh-novo', 'rotulo' => $cfg['botaoNovo'], 'destaque' => true]],
            $valores
                ? [['chave' => 'situacao', 'rotulo' => 'Situação', 'dataset' => 'status',
                    'opcoes' => $this->opcoesStatus($ent, $valores, $dados['totais']['porStatus'] ?? [])]]
                : [],
            'Descrição, evento ou cidade'
        );

        $this->setVars([
            'shell'   => $shell,
            'ent'     => $ent,
            'cfg'     => $cfg,
            'dados'   => $dados,
            'boot'    => $dados,
            'eventos' => $this->eventosParaSelect(),
            'token'   => $this->getFormToken(self::FORM),
            'pagina'  => 'cadastro',
        ]);
    }

    /** GET /hotel/calendario?calAno=2026&calMes=8 */
    public function calendario()
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        // O mês vai na querystring como o resto do estado: assim o botão
        // voltar funciona e a URL de um mês específico é compartilhável.
        // calAno/calMes, e nao ano/mes: ?ano= e ?meses= sao o recorte de
        // data do sistema inteiro, carregado de aba em aba por hurl(). Se o
        // calendario reaproveitasse ?ano=, abrir agosto passaria a filtrar
        // tambem os cartoes do topo — e o ano vazaria para as outras abas.
        $ano = (int)($_GET['calAno'] ?? date('Y'));
        $mes = (int)($_GET['calMes'] ?? date('n'));

        if ($ano < 2000 || $ano > 2100) { $ano = (int)date('Y'); }
        if ($mes < 1 || $mes > 12)      { $mes = (int)date('n'); }

        /*
         * Sem painel de filtros: o calendario navega por mes com os seus
         * proprios botoes (calAno/calMes). Um dropdown de Mes ao lado que NAO
         * move o calendario seria so armadilha — o usuario escolheria marco e
         * continuaria olhando janeiro.
         */
        $shell = $this->montarShell('calendario', 'Calendário', 'Eventos e feriados nacionais');
        $shell['semFiltros'] = true;

        $this->setVars([
            'shell'  => $shell,
            'cal'    => $this->calc()->calendario($ano, $mes),
            'boot'   => ['ano' => $ano, 'mes' => $mes],
        ]);
    }

    /** GET /hotel/relatorio[/entregas] */
    public function relatorio($sub = null)
    {
        $this->exigirAcesso();
        $this->setTemplate('hotel');

        $aba   = $sub === 'entregas' ? 'entrega' : 'geral';
        $shell = $this->montarShell(
            'relatorio',
            'Relatório',
            'Tabelas completas e exportação',
            [
                ['href' => hurl($this, 'exportar/csv'), 'rotulo' => 'Exportar CSV'],
                ['id' => 'gh-imprimir', 'rotulo' => 'Imprimir / PDF', 'destaque' => true],
            ]
        );
        $rel   = $this->calc()->relatorio($shell['periodoAtivo']);

        $this->setVars([
            'shell'   => $shell,
            'sub'     => $aba,
            'rel'     => $rel,
            'colunas' => HotelCalculo::colunasDe($aba),
            'boot'    => ['periodo' => $shell['periodoAtivo'], 'linhas' => count($rel['linhas'])],
        ]);
    }

    /**
     * GET /hotel/exportar/csv — as 43 colunas do relatório.
     *
     * Gerado no servidor, não montado com Blob no navegador. O original
     * concatenava o CSV em JavaScript sem escapar nada: uma vírgula dentro de
     * uma observação quebrava as colunas do arquivo inteiro.
     */
    public function exportar($tipo = 'csv')
    {
        $this->exigirAcesso();

        try {
            if ($tipo !== 'csv') {
                $this->naoEncontrado('exportar/' . $tipo);
            }

            $filtro = $this->filtroDaRequisicao();
            $rel    = $this->calc()->relatorio($filtro);

            // Nome do arquivo a partir do recorte: 2026-01-02-03, ou 'todos'.
            $sufixo = $filtro === null
                ? 'todos'
                : trim(implode('-', $filtro['anos']) . '-' . implode('-', array_map(
                    static fn(int $m): string => sprintf('%02d', $m),
                    $filtro['meses']
                )), '-');

            $nome = 'gestao-eventos-' . ($sufixo !== '' ? $sufixo : 'todos') . '-' . date('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nome . '"');
            header('Cache-Control: no-store');

            $saida = fopen('php://output', 'w');

            // BOM: sem ele o Excel abre UTF-8 como Latin-1 e os acentos viram
            // lixo. Separador ';' porque a virgula ja e o separador decimal.
            fwrite($saida, "\xEF\xBB\xBF");

            $colunas = HotelCalculo::COLUNAS_RELATORIO;
            fputcsv($saida, array_map(static fn(array $c): string => $c[0], $colunas), ';', '"', '\\');

            foreach ($rel['linhas'] as $linha) {
                $celulas = [];
                foreach (array_keys($colunas) as $chave) {
                    $celulas[] = $this->celulaCsv($chave, $linha[$chave] ?? '');
                }
                fputcsv($saida, $celulas, ';', '"', '\\');
            }

            fclose($saida);
            exit;
        } catch (Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** Formata um valor para a planilha, no padrão que o Excel pt-BR entende. */
    private function celulaCsv(string $chave, mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? 'Sim' : 'Não';
        }

        // Decimal com vírgula e SEM separador de milhar: com o ponto o Excel
        // pt-BR leria 1.234,50 como texto em vez de número.
        if (str_starts_with($chave, 'valor') || $chave === 'total') {
            return number_format((float)$valor, 2, ',', '');
        }

        if (str_starts_with($chave, 'data') || $chave === 'dataAss') {
            return $valor ? hdata((string)$valor, '') : '';
        }

        if (in_array($chave, ['statusContrato', 'statusAv', 'statusVm', 'entregaStatus', 'entregaAv'], true)) {
            $partes = array_filter(array_map('trim', explode(',', (string)$valor)));
            return implode(', ', array_map(static fn(string $v): string => hbadge('hotel', $v)[0], $partes));
        }

        return (string)$valor;
    }

    // =====================================================================
    // Endpoints JSON
    // =====================================================================

    /** GET /hotel/registro/{entidade}/{id} — hidrata o formulário de edição */
    public function registro($ent = null, $id = null)
    {
        $this->exigirAcesso();

        try {
            $ent = (string)$ent;
            if (!HotelSchema::existe($ent)) {
                $this->jsonOut(['ok' => false, 'erro' => 'Entidade desconhecida.'], 404);
            }

            $reg = $this->repo()->buscar($ent, (int)$id);
            if (!$reg) {
                $this->jsonOut(['ok' => false, 'erro' => 'Registro não encontrado.'], 404);
            }

            $this->jsonOut(['ok' => true, 'registro' => $reg]);
        } catch (Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * POST /hotel/salvar/{entidade} — cria ou atualiza.
     *
     * Um método só para todas as entidades, dirigido pelo Schema: oito
     * métodos quase idênticos foi justamente o que fez as regras divergirem
     * no sistema antigo.
     */
    public function salvar($ent = null)
    {
        $this->exigirAcesso();

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                $this->jsonOut(['ok' => false, 'erro' => 'Use POST.'], 405);
            }

            $ent = (string)$ent;
            if (!HotelSchema::existe($ent)) {
                $this->jsonOut(['ok' => false, 'erro' => 'Entidade desconhecida.'], 404);
            }

            $this->rateLimitJson(60, 60);

            if (!$this->verificaFormToken(self::FORM, (string)($_POST['_token'] ?? ''))) {
                $this->jsonOut([
                    'ok'    => false,
                    'erro'  => 'Sessão expirada ou formulário reenviado. Recarregue a página.',
                    'token' => $this->getFormToken(self::FORM),
                ], 419);
            }

            $id     = (int)($_POST[HotelSchema::pk($ent)] ?? 0);
            $atual  = $id > 0 ? $this->repo()->buscar($ent, $id) : null;

            if ($id > 0 && !$atual) {
                $this->jsonOut(['ok' => false, 'erro' => 'Registro não encontrado.', 'token' => $this->getFormToken(self::FORM)], 404);
            }

            $reg   = HotelSchema::normalizar($ent, $_POST, $atual);
            $erros = HotelSchema::validar($ent, $reg);
            $erros = $this->validarVinculo($ent, $reg, $erros);

            if ($erros) {
                // 422 com erro por campo, para o formulário destacar cada um
                // no lugar certo — no lugar do alert() genérico do original.
                $this->jsonOut([
                    'ok'    => false,
                    'erros' => $erros,
                    'token' => $this->getFormToken(self::FORM),
                ], 422);
            }

            $salvo = $this->repo()->salvar($ent, $reg);

            $this->jsonOut([
                'ok'       => true,
                'registro' => $salvo,
                'novo'     => $id === 0,
                // O custo do CRM acompanha esta gravação. Se falhar, o aviso
                // vem aqui — o salvamento não é desfeito, mas ninguém fica
                // sabendo semanas depois que os dois lados divergiram.
                'avisos'   => $this->repo()->avisosSync(),
                'crm'      => $this->repo()->mudancasSync(),
                // Token novo: verificaFormToken() consome o anterior, então
                // sem devolver outro o segundo salvamento da sessão falharia.
                'token'    => $this->getFormToken(self::FORM),
            ]);
        } catch (Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** POST /hotel/excluir/{entidade} — exclusão lógica */
    public function excluir($ent = null)
    {
        $this->exigirAcesso();

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                $this->jsonOut(['ok' => false, 'erro' => 'Use POST.'], 405);
            }

            $ent = (string)$ent;
            if (!HotelSchema::existe($ent)) {
                $this->jsonOut(['ok' => false, 'erro' => 'Entidade desconhecida.'], 404);
            }

            $this->rateLimitJson(60, 60);

            if (!$this->verificaFormToken(self::FORM, (string)($_POST['_token'] ?? ''))) {
                $this->jsonOut([
                    'ok'    => false,
                    'erro'  => 'Sessão expirada. Recarregue a página.',
                    'token' => $this->getFormToken(self::FORM),
                ], 419);
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$this->repo()->buscar($ent, $id)) {
                $this->jsonOut(['ok' => false, 'erro' => 'Registro não encontrado.', 'token' => $this->getFormToken(self::FORM)], 404);
            }

            $this->repo()->excluir($ent, $id);
            $this->jsonOut([
                'ok'     => true,
                'id'     => $id,
                'avisos' => $this->repo()->avisosSync(),
                'crm'    => $this->repo()->mudancasSync(),
                'token'  => $this->getFormToken(self::FORM),
            ]);
        } catch (Throwable $e) {
            $this->jsonError($e);
        }
    }

    /** Regras que dependem de outros registros, fora do alcance do Schema. */
    private function validarVinculo(string $ent, array $reg, array $erros): array
    {
        $col = HotelSchema::campo($ent, 'idHotel');
        if (!array_key_exists($col, $reg)) {
            return $erros;
        }

        $idHotel = $reg[$col];
        if ($idHotel !== null && !$this->repo()->buscar('hotel', (int)$idHotel)) {
            $erros[$col][] = 'Evento não encontrado.';
        }

        return $erros;
    }

    /**
     * Eventos do CRM para o <select> do hotel.
     *
     * Vem sempre do banco, mesmo com o sistema ainda lendo do arquivo: a lista
     * é do CRM, não deste sistema, e `evento` não existe no JSON. Memoizado
     * porque a tela chama uma vez por render e a consulta varre 132 linhas.
     */
    private function eventosDoCrm(): array
    {
        if ($this->eventosCrm !== null) {
            return $this->eventosCrm;
        }

        $repo = $this->repo();
        if (!$repo instanceof HotelRepoMysql) {
            require_once __DIR__ . '/../lib/hotel/RepoMysql.php';
            $repo = new HotelRepoMysql();
        }

        return $this->eventosCrm = $repo->eventosCrm();
    }
    /** Eventos ativos para os <select> de vínculo, em ordem cronológica. */
    private function eventosParaSelect(): array
    {
        $out = [];
        foreach ($this->calc()->hoteis() as $h) {
            if ($h['status_hotel'] === 'cancelado' || !$h['data_hotel']) {
                continue;
            }
            $out[] = [
                'id'     => (int)$h['id_hotel'],
                'data'   => $h['data_hotel'],
                'cidade' => $h['cidade_hotel'],
                'nome'   => $h['nome_hotel'],
            ];
        }
        return $out;
    }

    private function dadosVideomakers(?array $filtro): array
    {
        return $this->calc()->listaFilhos('videomaker', $filtro);
    }

    /**
     * GET /hotel/dados/{aba} — mesmo payload que a tela recebe no primeiro
     * carregamento. Serve para revalidar sem recarregar a página inteira.
     */
    public function dados($aba = null)
    {
        $this->exigirAcesso();

        try {
            $filtro = $this->filtroDaRequisicao();

            $payload = match ((string)$aba) {
                'valortotal' => $this->dadosValorTotal($filtro),
                default      => null,
            };

            if ($payload === null) {
                $this->jsonOut(['ok' => false, 'erro' => 'Aba desconhecida: ' . $aba], 404);
            }

            $this->jsonOut(['ok' => true] + $payload);
        } catch (Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Converte os agregados da visao geral no formato que o Chart.js espera.
     * Fica no controller para a view nao ter de remontar series em JS.
     */
    private function bootGraficos(array $vg): array
    {
        /*
         * A cor sai da VARIANTE que hbadge() ja atribui ao status, nao de um
         * segundo mapa aqui.
         *
         * Havia um mapa proprio com os quatro status de hotel; a rosca de A/V
         * usa outro vocabulario (pago, parcial, pendente) e saiu inteira
         * cinza, tudo caindo no default. Derivando do hbadge, badge e grafico
         * nao tem como discordar, e um status novo no Schema ja nasce com cor.
         */
        $corDaVariante = [
            'ok'     => '--green',
            'info'   => '--consulta',
            'warn'   => '--amber',
            'danger' => '--danger',
            'neutro' => '--muted',
        ];

        /*
         * Duas roscas de situacao: hotel e fornecedor de A/V. Mesma escala de
         * cor de ESTADO nas duas — verde e "esta resolvido" nos dois graficos,
         * entao o significado so precisa ser aprendido uma vez.
         */
        $rosca = static function (array $contagem, string $ent) use ($corDaVariante): array {
            $rotulos = [];
            $valores = [];
            $cores   = [];
            foreach ($contagem as $st => $n) {
                [$rotulo, $variante] = hbadge($ent, (string)$st);
                $rotulos[] = $st === '' ? 'Não informado' : $rotulo;
                $valores[] = $n;
                $cores[]   = $corDaVariante[$variante] ?? '--muted';
            }
            return ['rotulos' => $rotulos, 'valores' => $valores, 'cores' => $cores];
        };

        /*
         * Paleta categorica: cada parcela e uma entidade, nao um degrau de
         * intensidade. Chegou a ser uma rampa de um azul so — mas com tres
         * parcelas de tamanho parecido a pilha virava um bloco unico.
         *
         * Slot fixo por parcela, aqui e em $parcelas da view. A cor segue a
         * PARCELA e nunca a posicao na pilha: se uma zerar num recorte, as
         * outras nao podem trocar de cor.
         *
         * Os tokens --gh-s1..s7 vivem em css/hotel.css, com passos proprios
         * para o dark mode; charts.js le qualquer token via corToken().
         */
        $parcelaCores = [
            'hotel'       => '--gh-s1',
            'equipamento' => '--gh-s2',
            'videomaker'  => '--gh-s3',
            'transporte'  => '--gh-s4',
            'frete'       => '--gh-s5',
            'material'    => '--gh-s6',
            'custoExtra'  => '--gh-s7',
        ];
        $parcelaRotulos = [
            'hotel' => 'Hotel', 'equipamento' => 'A/V', 'videomaker' => 'Videomaker',
            'transporte' => 'Transporte', 'frete' => 'Frete', 'material' => 'Materiais',
            'custoExtra' => 'Custos extras',
        ];

        $mesesRotulos = [];
        foreach ($vg['porMes'] as $m) {
            $mesesRotulos[] = substr($m['mes'], 5, 2) . '/' . substr($m['mes'], 2, 2);
        }

        $series = [];
        foreach ($parcelaCores as $chave => $cor) {
            $dados = [];
            foreach ($vg['porMes'] as $m) {
                $dados[] = round((float)($m['parcelas'][$chave] ?? 0), 2);
            }
            // Parcela zerada em todos os meses vira ruido na legenda.
            if (array_sum($dados) > 0) {
                $series[] = ['rotulo' => $parcelaRotulos[$chave], 'cor' => $cor, 'dados' => $dados];
            }
        }

        return [
            'periodo'    => null,
            'contratos'   => $rosca($vg['contratos'], 'hotel'),
            'contratosAv' => $rosca($vg['contratosAv'], 'equipamento'),
            'meses'      => ['rotulos' => $mesesRotulos, 'series' => $series],
            'avaliacoes' => [
                'hotel'       => ['dist' => $vg['avaliacoes']['hotel']['dist'], 'cor' => '--gold'],
                'equipamento' => ['dist' => $vg['avaliacoes']['equipamento']['dist'], 'cor' => '--gold'],
            ],
        ];
    }

    /** Payload da aba Valor total, compartilhado entre a view e o endpoint. */
    private function dadosValorTotal(?array $filtro): array
    {
        // Multisseleção, como os demais campos do painel: ?cidade=Recife,Natal
        $cidades = array_values(array_filter(
            array_map('trim', explode(',', (string)($_GET['cidade'] ?? ''))),
            static fn(string $c): bool => $c !== ''
        ));
        $cons = $this->calc()->consolidado($filtro, $cidades);

        return [
            'periodo' => $filtro,
            'cidade'  => $cidades,
            'eventos' => $cons['eventos'],
            'cidades' => $cons['cidades'],
            'totais'  => $cons['totais'],
        ];
    }

    /** GET /hotel/checklist/{id} — pendências de um evento */
    public function checklist($id = null)
    {
        $this->exigirAcesso();

        try {
            $hotel = $this->repo()->buscar('hotel', (int)$id);
            if (!$hotel) {
                $this->jsonOut(['ok' => false, 'erro' => 'Evento não encontrado.'], 404);
            }

            $chk   = $this->calc()->checklist($hotel);
            $custo = $this->calc()->custoDoHotel($hotel);
            $this->jsonOut([
                'ok'    => true,
                'hotel' => [
                    'id'     => (int)$hotel['id_hotel'],
                    'nome'   => $hotel['nome_hotel'],
                    'cidade' => $hotel['cidade_hotel'],
                    'data'   => $hotel['data_hotel'],
                ],
                'checklist' => $chk,
                'custo'     => $custo,
            ]);
        } catch (Throwable $e) {
            $this->jsonError($e);
        }
    }

    // =====================================================================
    // Infraestrutura
    // =====================================================================

    private function repo(): HotelRepositorio
    {
        return $this->repositorio ??= HotelRepo::criar();
    }

    private function calc(): HotelCalculo
    {
        return $this->calculo ??= new HotelCalculo($this->repo());
    }

    /**
     * Recorte de data pedido na querystring (?anos=2025,2026&meses=1,2,3),
     * validado — nunca usado cru.
     *
     * Substituiu o ?p=s1-2026: semestre obrigava a pedir seis meses para ver
     * três. Memoizado porque quase todo método chama isto e o parsing é o
     * mesmo dentro de uma requisição.
     *
     * @return array{anos:string[],meses:int[]}|null
     */
    private function filtroDaRequisicao(): ?array
    {
        static $cache = false;
        if ($cache === false) {
            $cache = $this->calc()->filtroValido(
                isset($_GET['anos']) ? (string)$_GET['anos'] : null,
                isset($_GET['meses']) ? (string)$_GET['meses'] : null
            );
        }
        return $cache;
    }

    /**
     * Contexto que toda tela compartilha: recorte ativo, opções de ano e mês,
     * cartões de resumo e alertas. O template renderiza a partir daqui, então
     * o cabeçalho é idêntico em todas as abas sem cada view repetir a
     * montagem.
     */
    private function montarShell(
        string $aba,
        string $titulo,
        string $subtitulo = '',
        array $acoes = [],
        array $filtrosTela = [],
        ?string $buscaLabel = null
    ): array {
        $filtro = $this->filtroDaRequisicao();

        return [
            'semDados'     => $this->calc()->semDados(),
            'aba'          => $aba,
            'titulo'       => $titulo,
            'subtitulo'    => $subtitulo,
            'acoes'        => $acoes,
            'periodoAtivo' => $filtro,
            'rotuloFiltro' => $this->calc()->rotuloFiltro($filtro),
            'anos'         => $this->calc()->anos(),
            // Contagem por mês dentro do ano escolhido: é ela que decide
            // quais dos doze botões vêm desabilitados.
            'mesesDoAno'   => $this->calc()->mesesDosAnos($filtro['anos'] ?? []),
            'stats'        => $this->calc()->stats($filtro),
            'alertas'      => $this->calc()->alertas(),
            /*
             * Campos que o painel mostra ALÉM de Ano e Mês.
             *
             * Ano e Mês recortam no servidor e viajam na URL; estes filtram a
             * lista já renderizada, no cliente — por isso cada um declara o
             * nome do atributo data-* que as linhas carregam. Os dois grupos
             * moram no mesmo painel e são aplicados pelo mesmo botão.
             */
            'filtrosTela'  => $filtrosTela,
            'buscaLabel'   => $buscaLabel,
        ];
    }

    /**
     * Opções de um dropdown de situação, com a contagem de cada uma.
     *
     * @param array<string,int> $contagem valor => quantos
     * @return array<int,array{valor:string,rotulo:string,n:int}>
     */
    private function opcoesStatus(string $ent, array $valores, array $contagem): array
    {
        $out = [];
        foreach ($valores as $v) {
            $out[] = [
                'valor'  => $v,
                'rotulo' => hbadge($ent, $v)[0],
                'n'      => (int)($contagem[$v] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Mesma política dos dashboards (Basic Auth, liberado só em iframe de
     * origem permitida com ?to=1), replicada aqui porque a versão de
     * home_controle.php é privada e o projeto adota cópia por controller.
     *
     * Idempotente: chamar de novo no mesmo request não repete o cabeçalho.
     */
    private function exigirAcesso(): void
    {
        static $verificado = false;
        if ($verificado) {
            return;
        }
        $verificado = true;

        $whitelist = array_filter(array_map('trim', explode(',', (string)HotelRepo::lerEnv('DASH_IFRAME_ALLOWLIST'))));
        header("Content-Security-Policy: frame-ancestors 'self' " . implode(' ', $whitelist));

        $to1      = ($_GET['to'] ?? '') === '1';
        $isIframe = ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'iframe';

        /*
         * fetch()/XHR feito pelo JS da própria página embedada (ex.: o
         * popover de hover do calendário) nunca manda Sec-Fetch-Dest:
         * iframe — o navegador sempre manda "empty" nesses casos, mesmo
         * quando a página que fez a chamada está corretamente dentro do
         * iframe de um parceiro. Sem esta segunda via, todo endpoint JSON
         * chamado depois da carga inicial cai no Basic Auth e o navegador
         * mostra o prompt de login sozinho (o 401 vem com WWW-Authenticate).
         * Sec-Fetch-Site: same-origin garante que quem chamou foi o próprio
         * bi.exemplo.com.br, não um fetch de terceiro — não dá pra
         * falsificar via JS, o navegador que define.
         */
        $isFetchMesmaOrigem = ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'empty'
            && ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'same-origin';

        $refererOrigem = '';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $scheme = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
            $host   = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if ($scheme && $host) {
                $refererOrigem = $scheme . '://' . $host;
            }
        }

        if ($to1 && ($isIframe || $isFetchMesmaOrigem) && $refererOrigem !== '' && in_array($refererOrigem, $whitelist, true)) {
            return;
        }

        $usuarioEsperado = (string)HotelRepo::lerEnv('DASH_AUTH_USER');
        $senhaEsperada   = (string)HotelRepo::lerEnv('DASH_AUTH_PASSWORD');

        $usuario = $_SERVER['PHP_AUTH_USER'] ?? null;
        $senha   = $_SERVER['PHP_AUTH_PW'] ?? null;

        // Apache em CGI/FastCGI não popula PHP_AUTH_USER; o cabeçalho cru
        // continua disponível.
        if ($usuario === null && !empty($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Basic ')) {
            $decodificado = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
            if ($decodificado !== false && str_contains($decodificado, ':')) {
                [$usuario, $senha] = explode(':', $decodificado, 2);
            }
        }

        $valido = $usuario !== null
            && $senha !== null
            && $usuarioEsperado !== ''
            && hash_equals($usuarioEsperado, (string)$usuario)
            && hash_equals($senhaEsperada, (string)$senha);

        if (!$valido) {
            header('WWW-Authenticate: Basic realm="Acme EUA · Gestão de Eventos"');
            http_response_code(401);
            // Responde no formato que o cliente espera: um fetch() que recebe
            // HTML de erro quebra no JSON.parse com uma mensagem enganosa.
            if ($this->querJson()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'erro' => 'Acesso negado.']);
            } else {
                echo 'Acesso negado.';
            }
            exit;
        }
    }

    /**
     * Limite de requisicoes por IP e rota, respondendo em JSON.
     *
     * O rateLimit() do core renderiza a pagina HTML de erro 429, o que quebra
     * qualquer fetch() esperando JSON; por isso este controller tem a sua
     * propria versao, que responde em JSON.
     */
    private function rateLimitJson(int $maxTentativas, int $janela): void
    {
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rota    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $arquivo = __DIR__ . '/../.mrphp/rate/' . md5($ip . $rota) . '.json';

        $agora = time();
        $dados = is_file($arquivo) ? json_decode((string)file_get_contents($arquivo), true) : null;

        if (!$dados || ($agora - $dados['inicio']) >= $janela) {
            $dados = ['count' => 1, 'inicio' => $agora];
        } else {
            $dados['count']++;
            if ($dados['count'] > $maxTentativas) {
                $restante = $janela - ($agora - $dados['inicio']);
                $this->jsonOut(['ok' => false, 'erro' => "Muitas requisicoes. Aguarde {$restante}s."], 429);
            }
        }

        if (!is_dir(dirname($arquivo))) {
            mkdir(dirname($arquivo), 0755, true);
        }
        file_put_contents($arquivo, json_encode($dados), LOCK_EX);
    }

    /** Responde 404 no formato que o cliente pediu e encerra. */
    private function naoEncontrado(string $rota): void
    {
        if ($this->querJson()) {
            $this->jsonOut(['ok' => false, 'erro' => 'Rota não encontrada: ' . $rota], 404);
        }

        http_response_code(404);
        $pagina = __DIR__ . '/../paginas/error/404.php';
        if (file_exists($pagina)) {
            include $pagina;
        } else {
            echo 'Página não encontrada.';
        }
        exit;
    }

    private function querJson(): bool
    {
        $aceita = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($aceita, 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    /** @param array $dados corpo da resposta */
    private function jsonOut(array $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(Throwable $e, int $status = 500): void
    {
        $dev = strtoupper((string)HotelRepo::lerEnv('APP_ENV')) === 'DEV';
        $this->jsonOut([
            'ok'  => false,
            'erro' => $dev ? $e->getMessage() : 'Erro interno ao processar a requisição.',
        ], $status);
    }
}
