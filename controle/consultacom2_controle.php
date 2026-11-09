<?php

/**
 * Dashboard "ConsultaCom2" — tabela única de deals do time Comercial 2
 * (Rafael Torres, Beatriz Lemos, Camila Duarte, Juliana Prado).
 *
 * Sem KPIs/gráficos — só a lista de deals com consulta realizada no
 * período filtrado (dataConsultaRealizada_dealV2), ordenável e
 * pesquisável no client-side, com filtro opcional de vendedor(es).
 *
 * Mesmo padrão de rafaeltorres_controle.php (sem camada calculos/): toda
 * a lógica de SQL/cálculo fica em métodos privados desta classe.
 */
class consultacom2 extends MRPHP
{
    private const EQUIPE = ['Rafael Torres', 'Beatriz Lemos', 'Camila Duarte', 'Juliana Prado', 'Gustavo Leal'];
    private const BITRIX_BASE = 'https://exemplo.bitrix24.com.br/crm/deal/details/';

    /**
     * Fase de consulta. LIKE 'C5:%' em vez do LEFT(faseId_dealV2,2) = 'C5' que
     * o CLAUDE.md documenta: existem categorias de dois dígitos (C11, C15,
     * C29, C31), então o LEFT casaria um futuro C50-C59. Hoje as 17 fases C5
     * são todas 'C5:...', o resultado é idêntico — e o LIKE usa idx_dealV2_faseId.
     */
    private const SQL_FASE_C5 = "d.faseId_dealV2 LIKE 'C5:%'";

    /**
     * Data de consulta realizada preenchida. Zero-date não é NULL, então o
     * IS NOT NULL sozinho deixaria passar (ver CLAUDE.md).
     *
     * Junto com SQL_FASE_C5 e o IN de self::EQUIPE, é o que define o universo da
     * aba "Consultas". A aba "Planilha" cruza contra exatamente esse universo —
     * as duas telas compartilham estes fragmentos justamente pra não poderem
     * divergir: um cliente só recebe OK se aparece na primeira aba.
     */
    private const SQL_CONSULTA_REALIZADA = "d.dataConsultaRealizada_dealV2 > '0000-00-00'";

    /**
     * Fases do funil de Evento — o funil padrão do Bitrix, o único cujo faseId
     * não tem prefixo de categoria. Confirmado em produção: as fases sem ':'
     * são WON, LOSE, NEW, UC_C07YVI, FINAL_INVOICE e UC_OSVR3K; só as duas
     * primeiras interessam, porque é nelas que o card de venda da consulta
     * termina.
     *
     * LOSE não significa "não comprou": dos 120 que cruzam com a aba
     * Consultas, 112 têm dataVenda preenchida (descarte depois da compra da
     * consulta) e 8 não têm (descarte antes). Por isso o lado Evento NÃO
     * filtra por dataVenda — nesses 8 a coluna sai vazia na tela, e é esse o
     * comportamento desejado.
     */
    private const SQL_FASES_EVENTO = "e.faseId_dealV2 IN ('WON','LOSE')";

    /**
     * Consulta vendida, regra canônica do projeto aplicada ao funil de Evento:
     * fase WON com data de venda preenchida. LOSE fica de fora mesmo quando tem
     * dataVenda (ali a venda aconteceu e o card foi descartado depois) — quem
     * conta venda é a fase WON, não a presença da data.
     *
     * O 'C11:WON' que o CLAUDE.md cita junto de 'Won' é de outro funil e não
     * entra aqui: esta tela só fala do funil de Evento.
     */
    private const SQL_CONSULTA_VENDIDA = "e.faseId_dealV2 = 'WON' AND e.dataVenda_dealV2 > '0000-00-00'";

    public function index()
    {
        $this->setTemplate('consultacom2');
    }

    /** GET /consultacom2/planilha — segunda aba (linhas da planilha do time). */
    public function planilha()
    {
        $this->setTemplate('consultacom2');
    }

    /** GET /consultacom2/eventos — terceira aba (C5 × card de Evento do mesmo contato). */
    public function eventos()
    {
        $this->setTemplate('consultacom2');
    }

    // ===================================================================
    // HELPERS: CACHE EM ARQUIVO (.mrphp/cache) — TTL curto, sem tocar no banco
    // Mesmo padrão de executive_controle.php (cópia própria do controller,
    // sem camada compartilhada — convenção do projeto, ver memória
    // cache-copia-por-controller).
    // ===================================================================

    private const CACHE_TTL_DADOS    = 600;
    /** Lista de vendedores da aba Eventos: nao depende de filtro, so muda
     *  quando entra vendedor novo no C5 — chave fixa e TTL longo. */
    private const CACHE_TTL_RESPONSAVEIS = 21600;

    /**
     * Rótulo de quem só casou pelo nome, não pelo e-mail.
     *
     * Evidência mais fraca de propósito visível: o e-mail do CRM diverge do da
     * planilha (normalmente digitação errada, mas às vezes é outra pessoa de
     * mesmo nome), e quem confere precisa enxergar isso sem abrir o Bitrix.
     */
    private const STATUS_OK_NOME = 'OK (nome)';

    /** Colunas que buscarNaAbaConsultas() aceita — o nome vai concatenado no SQL. */
    private const COL_EMAIL = 'email_dealV2';
    private const COL_NOME  = 'nome_dealV2';

    private function cacheDir(): string
    {
        return dirname(__DIR__) . '/.mrphp/cache';
    }

    private function cacheGet(string $key, int $ttlSeconds)
    {
        $file = $this->cacheDir() . '/' . $key . '.json';
        if (!is_file($file) || (time() - filemtime($file)) > $ttlSeconds) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function cacheSet(string $key, $data): void
    {
        $file = $this->cacheDir() . '/' . $key . '.json';
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $this->cacheGc();
    }

    private function cacheKeyFromRequest(string $endpoint): string
    {
        return $endpoint . '_' . md5($_SERVER['QUERY_STRING'] ?? '');
    }

    private function cacheGc(): void
    {
        if (mt_rand(1, 100) > 2) {
            return;
        }
        foreach (glob($this->cacheDir() . '/*.json') ?: [] as $file) {
            if ((time() - filemtime($file)) > 7200) {
                @unlink($file);
            }
        }
    }

    /**
     * GET /consultacom2/dados?year=&months=&comercial=
     * comercial (opcional) é um CSV com um ou mais nomes de self::EQUIPE;
     * ausente/vazio/inválido = equipe toda (padrão "Todos").
     */
    public function dados()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('consultacom2_dados');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year      = $this->g('year');
            $months    = $this->g('months');
            $comercial = $this->g('comercial');

            $planilha = $this->carregarPlanilha();
            $linhas   = $this->calcularLinhas($year, $months, $comercial, $planilha);

            $response = [
                'linhas'                => $linhas,
                'planilha_indisponivel' => $planilha['indisponivel'],
            ];
            // Sem a planilha o status sai todo null; cachear isso congelaria a
            // tela por 10min mesmo depois de o acesso voltar.
            if (!$planilha['indisponivel']) {
                $this->cacheSet($cacheKey, $response);
            }
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    private function calcularLinhas(?string $year, ?string $months, ?string $comercial, array $planilha): array
    {
        // Restringe sempre ao time do Comercial 2 — mesmo que o parâmetro venha
        // manipulado na URL, só nomes dentro de self::EQUIPE são aceitos.
        $selecionados = $comercial
            ? array_values(array_intersect(array_map('trim', explode(',', $comercial)), self::EQUIPE))
            : [];
        $vendedores = $selecionados ?: self::EQUIPE;

        $filtroData   = $this->buildDateFilter('d.dataConsultaRealizada_dealV2', $year, $months);
        $filtroEquipe = $this->buildInFilter('d.responsavel_dealV2', implode(',', $vendedores));

        $sql = "
            SELECT
                d.idBitrix_dealV2                  AS id_bitrix,
                d.nome_dealV2                      AS nome_card,
                d.email_dealV2                     AS email,
                d.responsavel_dealV2               AS comercial2,
                d.dataConsultaRealizada_dealV2      AS data_consulta_realizada,
                d.dataConsultaAgendada_dealV2       AS data_consulta_agendada,
                d.dataContratoComboAssinado_dealV2  AS data_contrato_assinado,
                d.fase_dealV2                       AS fase_bitrix
            FROM dealV2 d
            WHERE {$filtroData['sql']}
              AND " . self::SQL_CONSULTA_REALIZADA . "
              AND " . self::SQL_FASE_C5 . "
              {$filtroEquipe['sql']}
            ORDER BY d.dataConsultaRealizada_dealV2 DESC
            LIMIT 1000
        ";

        $params = array_merge($filtroData['params'], $filtroEquipe['params']);
        $rows   = $this->queryAll($sql, $params);

        // Cruzamento com a planilha é em memória — as linhas já estão carregadas,
        // não custa query. Sem planilha o status fica null e a view mostra "—"
        // em vez de marcar todo mundo como Pendente.
        //
        // Dois índices: e-mail (match forte) e nome (fallback). O fallback existe
        // porque o e-mail costuma vir digitado errado no CRM — 'daianombardi28@'
        // contra 'daianelombardi28@' na planilha, por exemplo.
        $indisponivel = $planilha['indisponivel'];
        $porEmail = [];
        $porNome  = [];
        foreach ($planilha['linhas'] as $l) {
            $porEmail[$l['email_norm']] = $l;
            if ($l['nome_norm'] !== '' && !isset($porNome[$l['nome_norm']])) {
                $porNome[$l['nome_norm']] = $l;
            }
        }

        return array_map(function ($r) use ($indisponivel, $porEmail, $porNome) {
            $status  = null;
            $detalhe = null;
            $linhaPlanilha = null;
            if (!$indisponivel) {
                if (isset($porEmail[$this->emailNorm($r['email'])])) {
                    $status = 'OK';
                    $linhaPlanilha = $porEmail[$this->emailNorm($r['email'])];
                } elseif (isset($porNome[$this->nomeNorm($r['nome_card'])])) {
                    $status  = self::STATUS_OK_NOME;
                    $linhaPlanilha = $porNome[$this->nomeNorm($r['nome_card'])];
                    $detalhe = 'Casou pelo nome. E-mail na planilha: ' . $linhaPlanilha['email'];
                } else {
                    $status = 'Pendente';
                }
            }

            return [
                'id_bitrix'               => $r['id_bitrix'],
                'nome_card'               => $r['nome_card'],
                'email'                   => $r['email'],
                'status'                  => $status,
                'status_detalhe'          => $detalhe,
                'comercial2'              => $r['comercial2'],
                'data_consulta_realizada' => $this->fmtDataOuNull($r['data_consulta_realizada']),
                'data_consulta_agendada'  => $this->fmtDataOuNull($r['data_consulta_agendada']),
                'data_contrato_assinado'  => $this->fmtDataOuNull($r['data_contrato_assinado']),
                'data_consulta_planilha'  => $linhaPlanilha && $linhaPlanilha['data_consulta'] !== ''
                    ? $linhaPlanilha['data_consulta'] : null,
                'fase_bitrix'             => $r['fase_bitrix'],
                'link_bitrix'             => $r['id_bitrix'] ? self::BITRIX_BASE . $r['id_bitrix'] . '/' : null,
            ];
        }, $rows);
    }

    // ===================================================================
    // ABA "EVENTOS": C5 com consulta realizada x card do funil de Evento
    // ===================================================================

    /**
     * GET /consultacom2/dadoseventos?year=&months=&comercial=
     *
     * Terceira aba. Diferente das outras duas, não restringe a self::EQUIPE:
     * o universo é o pipe C5 inteiro (24 responsáveis hoje), porque a pergunta
     * aqui é "de que evento veio esta consulta", não "o time conferiu?".
     */
    public function dadoseventos()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('consultacom2_dadoseventos');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $year   = $this->g('year');
            $months = $this->g('months');

            $response = [
                'linhas'            => $this->calcularLinhasEventos($year, $months, $this->g('comercial')),
                'vendidas_por_form' => $this->vendidasPorFormulario($year, $months),
                'responsaveis'      => $this->listaResponsaveis(),
            ];

            $this->cacheSet($cacheKey, $response);
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    /**
     * Cada lado é uma subquery com universo próprio — o pipe C5 com consulta
     * realizada de um lado, os cards do funil de Evento do outro — e o join é
     * só a ponte pelo contato (contactId_dealV2). Lê-se como a regra de
     * negócio: achar o card do evento onde o cliente comprou a consulta e,
     * pelo contato, localizar a consulta realizada dele no C5.
     *
     * LEFT JOIN de propósito: os ~1.500 cards C5 sem card de evento continuam
     * na lista, com o bloco de evento vazio.
     */
    private function calcularLinhasEventos(?string $year, ?string $months, ?string $comercial): array
    {
        $filtroData = $this->buildDateFilter('d.dataConsultaRealizada_dealV2', $year, $months);
        // Sem array_intersect contra self::EQUIPE como em calcularLinhas(): aqui
        // qualquer responsável é válido, e o bind_param do buildInFilter já é a
        // proteção contra valor manipulado na URL.
        $filtroResp = $this->buildInFilter('d.responsavel_dealV2', $comercial);

        // Notas que não devem ser "corrigidas" depois:
        //
        // 1. O <> vazio do contactId vive dentro da subquery de Evento. No WHERE
        //    externo ele viraria filtro sobre o resultado do LEFT JOIN e sumiria
        //    com o único card C5 de contato vazio; onde está, só impede que dois
        //    contatos vazios casem entre si.
        // 2. CONVERT(... USING latin1) no join com evento: idEvento_dealV2 é
        //    varchar e evento.id_evento é int — sem o CONVERT o MySQL descarta o
        //    índice (ver CLAUDE.md, mesmo fix de eventos_controle.php).
        // 3. LIMIT 5000 (e não 1000 como calcularLinhas): o universo inteiro sem
        //    filtro é ~3.600 linhas, e 1000 truncaria "todos os períodos" calado.
        // 4. As subqueries derivadas não custam nada aqui — o MariaDB faz merge
        //    delas em vez de materializar, medido em 19ms com filtro de mês.
        //    Não trocar por self-join achando que é otimização.
        // 5. O formulário do registro vem do card de evento e, quando não há
        //    card de evento, do próprio card C5 — que também carrega
        //    formPreenchido_dealV2. Sem esse fallback, filtrar por um
        //    formulário escondia todo C5 que não cruzou, que é justamente o
        //    que se quer enxergar. Quando os dois existem eles coincidem em
        //    98% dos casos (2.019 de 2.059); nos 17 que divergem vale o do
        //    card de evento, que é onde a consulta foi vendida.
        $sql = "
            SELECT
                c5.id_bitrix_c2,
                c5.nome_card,
                c5.email,
                c5.comercial2,
                c5.data_consulta_realizada,
                ev.fase_evento,
                ev.data_venda_evento,
                COALESCE(NULLIF(ev.form_evento, ''), NULLIF(c5.form_c5, '')) AS form,
                ev.id_evento,
                evt.nome_evento,
                ev.id_bitrix_evento
            FROM (
                SELECT
                    d.contactId_dealV2             AS contact_id,
                    d.idBitrix_dealV2              AS id_bitrix_c2,
                    d.nome_dealV2                  AS nome_card,
                    d.email_dealV2                 AS email,
                    d.responsavel_dealV2           AS comercial2,
                    d.formPreenchido_dealV2        AS form_c5,
                    d.dataConsultaRealizada_dealV2 AS data_consulta_realizada
                FROM dealV2 d
                WHERE {$filtroData['sql']}
                  AND " . self::SQL_CONSULTA_REALIZADA . "
                  AND " . self::SQL_FASE_C5 . "
                  {$filtroResp['sql']}
            ) c5
            LEFT JOIN (
                SELECT
                    e.contactId_dealV2      AS contact_id,
                    e.faseId_dealV2         AS fase_evento,
                    e.dataVenda_dealV2      AS data_venda_evento,
                    e.formPreenchido_dealV2 AS form_evento,
                    e.idEvento_dealV2       AS id_evento,
                    e.idBitrix_dealV2       AS id_bitrix_evento
                FROM dealV2 e
                WHERE " . self::SQL_FASES_EVENTO . "
                  AND e.contactId_dealV2 <> ''
            ) ev ON ev.contact_id = c5.contact_id
            LEFT JOIN evento evt
                   ON evt.id_evento = CONVERT(ev.id_evento USING latin1)
            ORDER BY c5.data_consulta_realizada DESC
            LIMIT 5000
        ";

        // bind_param é posicional e todos os parâmetros estão na subquery c5,
        // nesta ordem: filtro de data, depois filtro de responsável. A subquery
        // de Evento não tem nenhum.
        $params = array_merge($filtroData['params'], $filtroResp['params']);

        return array_map(function ($r) {
            $idEvento = trim((string)($r['id_evento'] ?? ''));

            return [
                'nome_card'               => $r['nome_card'],
                'email'                   => $r['email'],
                'comercial2'              => $r['comercial2'],
                'data_consulta_realizada' => $this->fmtDataOuNull($r['data_consulta_realizada']),
                'fase_evento'             => $r['fase_evento'],
                // Zero-date nos 8 descartes anteriores à venda — vira null e a
                // tela mostra travessão, sem badge especial.
                'data_venda_evento'       => $this->fmtDataOuNull($r['data_venda_evento']),
                'form'                    => $r['form'],
                'id_evento'               => ($idEvento === '' || $idEvento === '0') ? null : $idEvento,
                'nome_evento'             => $r['nome_evento'],
                'id_bitrix_c2'            => $r['id_bitrix_c2'],
                'id_bitrix_evento'        => $r['id_bitrix_evento'],
                'link_bitrix_c2'          => $r['id_bitrix_c2']
                    ? self::BITRIX_BASE . $r['id_bitrix_c2'] . '/' : null,
                'link_bitrix_evento'      => $r['id_bitrix_evento']
                    ? self::BITRIX_BASE . $r['id_bitrix_evento'] . '/' : null,
            ];
        }, $this->queryAll($sql, $params));
    }

    /**
     * Consultas vendidas por formulário, para a tabela agrupada da aba Eventos.
     *
     * Conta o universo inteiro de cards de Evento vendidos no período — não só
     * os que aparecem na tabela de registros. É isso que dá sentido à leitura
     * "vendeu X, compareceu Y": um form pode ter vendido 33 consultas e ter só
     * 23 realizadas, e as 10 que faltam são justamente o que não aparece na
     * tabela de baixo.
     *
     * Duas assimetrias deliberadas em relação à tabela de registros:
     *
     * - O período filtra por dataVenda_dealV2, não por dataConsultaRealizada —
     *   cada métrica é medida na sua própria coluna de data (CLAUDE.md).
     * - O filtro de vendedor NÃO entra. Ele incide sobre responsavel_dealV2 do
     *   card C5, e quem vende a consulta no evento é outra pessoa; aplicá-lo
     *   aqui zeraria as vendidas de quase todo form. A view avisa isso no
     *   cabeçalho da coluna.
     *
     * @return array<string,int> formulário => quantidade (chave '' = sem form)
     */
    private function vendidasPorFormulario(?string $year, ?string $months): array
    {
        $filtroData = $this->buildDateFilter('e.dataVenda_dealV2', $year, $months);

        $sql = "
            SELECT e.formPreenchido_dealV2 AS form, COUNT(*) AS vendidas
            FROM dealV2 e
            WHERE {$filtroData['sql']}
              AND " . self::SQL_CONSULTA_VENDIDA . "
            GROUP BY e.formPreenchido_dealV2
        ";

        $mapa = [];
        foreach ($this->queryAll($sql, $filtroData['params']) as $r) {
            $mapa[(string)$r['form']] = (int)$r['vendidas'];
        }
        return $mapa;
    }

    /**
     * Vendedores que alimentam o dropdown da aba Eventos.
     *
     * Query à parte, de propósito: derivar a lista do resultado já filtrado
     * faria o dropdown encolher a cada filtro aplicado. Como não depende de
     * nenhum parâmetro, usa chave de cache fixa e TTL longo (checklist de
     * performance do CLAUDE.md).
     *
     * @return string[]
     */
    private function listaResponsaveis(): array
    {
        $cacheKey = 'consultacom2_responsaveis';
        $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_RESPONSAVEIS);
        if ($cached !== null) {
            return $cached;
        }

        $sql = "
            SELECT DISTINCT d.responsavel_dealV2 AS responsavel
            FROM dealV2 d
            WHERE " . self::SQL_FASE_C5 . "
              AND " . self::SQL_CONSULTA_REALIZADA . "
              AND d.responsavel_dealV2 <> ''
            ORDER BY d.responsavel_dealV2
        ";

        $lista = array_column($this->queryAll($sql), 'responsavel');
        $this->cacheSet($cacheKey, $lista);
        return $lista;
    }

    // ===================================================================
    // ABA "PLANILHA": linhas da planilha do time + status + link Bitrix
    // ===================================================================

    /**
     * GET /consultacom2/dadosplanilha?comercial=
     *
     * comercial (opcional) é um CSV com um ou mais nomes de self::EQUIPE e
     * filtra pela coluna "Nome Comercial" da própria planilha.
     */
    public function dadosplanilha()
    {
        try {
            $cacheKey = $this->cacheKeyFromRequest('consultacom2_dadosplanilha');
            $cached   = $this->cacheGet($cacheKey, self::CACHE_TTL_DADOS);
            if ($cached !== null) {
                $cached['cached'] = true;
                $this->jsonOut($cached);
            }

            $planilha = $this->carregarPlanilha();
            $linhas   = $this->calcularLinhasPlanilha($this->g('comercial'), $planilha);

            $response = [
                'linhas'                => $linhas,
                'planilha_indisponivel' => $planilha['indisponivel'],
            ];
            if (!$planilha['indisponivel']) {
                $this->cacheSet($cacheKey, $response);
            }
            $this->jsonOut($response);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    private function calcularLinhasPlanilha(?string $comercial, array $planilha): array
    {
        if ($planilha['indisponivel'] || !$planilha['linhas']) {
            return [];
        }

        // Filtro de vendedor incide sobre a coluna "Nome Comercial" da própria
        // planilha e, como em calcularLinhas(), só aceita nomes de self::EQUIPE.
        $selecionados = $comercial
            ? array_values(array_intersect(array_map('trim', explode(',', $comercial)), self::EQUIPE))
            : [];

        $linhas = $planilha['linhas'];
        if ($selecionados) {
            $linhas = array_values(array_filter(
                $linhas,
                fn($l) => in_array(trim($l['nome_comercial']), $selecionados, true)
            ));
        }

        $email = fn($v) => $this->emailNorm($v);
        $nome  = fn($v) => $this->nomeNorm($v);

        // Camada 1 — a aba Consultas. É só ela que dá OK.
        $okEmail = $this->buscarNaAbaConsultas(self::COL_EMAIL, array_column($linhas, 'email'), $email);
        $restam  = array_filter($linhas, fn($l) => !isset($okEmail[$l['email_norm']]));
        $okNome  = $restam
            ? $this->buscarNaAbaConsultas(self::COL_NOME, array_column($restam, 'nome_cliente'), $nome)
            : [];

        // Camada 2 — quem não está na aba Consultas ainda costuma ter card no
        // CRM (em C5 sem data de realização, ou sob vendedor de fora do time).
        // Esta busca serve exclusivamente pra achar o link do Bitrix: o status
        // continua Pendente, porque OK significa "está na primeira aba".
        $pendentes = array_filter(
            $restam,
            fn($l) => $l['nome_norm'] === '' || !isset($okNome[$l['nome_norm']])
        );
        $foraEmail = $pendentes
            ? $this->buscarEmTodoC5(self::COL_EMAIL, array_column($pendentes, 'email'), $email)
            : [];
        $semLink = array_filter($pendentes, fn($l) => !isset($foraEmail[$l['email_norm']]));
        $foraNome = $semLink
            ? $this->buscarEmTodoC5(self::COL_NOME, array_column($semLink, 'nome_cliente'), $nome)
            : [];

        return array_map(function ($l) use ($okEmail, $okNome, $foraEmail, $foraNome) {
            $porNomeNorm = fn(array $mapa) => $l['nome_norm'] !== '' ? ($mapa[$l['nome_norm']] ?? null) : null;

            $deal    = $okEmail[$l['email_norm']] ?? null;
            $status  = 'OK';
            $detalhe = null;

            if (!$deal) {
                $deal = $porNomeNorm($okNome);
                if ($deal) {
                    $status  = self::STATUS_OK_NOME;
                    $detalhe = 'Casou pelo nome. E-mail no CRM: '
                             . ($deal['email'] !== '' ? $deal['email'] : 'vazio');
                } else {
                    $status = 'Pendente';
                    $deal   = ($foraEmail[$l['email_norm']] ?? null) ?: $porNomeNorm($foraNome);
                    if ($deal) {
                        $detalhe = $this->motivoForaDaAba($deal);
                    }
                }
            }

            return [
                'nome_cliente'      => $l['nome_cliente'],
                'email'             => $l['email'],
                'data_consulta'     => $l['data_consulta'],
                // Data que o CRM registra pro deal que casou. Fica lado a lado
                // com a da planilha, pra dar pra conferir divergência no olho.
                'data_consulta_crm' => $deal ? $this->fmtDataOuNull($deal['data_consulta_realizada']) : null,
                'nome_comercial'    => $l['nome_comercial'],
                'obs'               => $l['obs'],
                'status'            => $status,
                'status_detalhe'    => $detalhe,
                'link_bitrix'       => ($deal && $deal['id_bitrix'])
                    ? self::BITRIX_BASE . $deal['id_bitrix'] . '/'
                    : null,
            ];
        }, $linhas);
    }

    /**
     * Explica, pro tooltip, por que a linha tem card no CRM mas não entrou na
     * aba Consultas — as duas únicas causas possíveis, já que o card é C5.
     */
    private function motivoForaDaAba(array $deal): string
    {
        $base = 'Não está na aba Consultas, mas há card em C5 no CRM. ';

        if ($this->fmtDataOuNull($deal['data_consulta_realizada']) === null) {
            return $base . 'O card está sem data de consulta realizada.';
        }

        $resp = trim((string)($deal['responsavel'] ?? ''));
        return $base . 'A consulta está sob ' . ($resp !== '' ? $resp : 'vendedor de fora do Comercial 2') . '.';
    }

    /**
     * Procura os valores dentro do universo da aba "Consultas" e devolve o mapa
     * "valor normalizado => deal".
     *
     * As três regras são as mesmas da primeira aba — fase C5, data de consulta
     * realizada preenchida e responsável do Comercial 2 — porque o OK da aba
     * "Planilha" significa exatamente "este cliente aparece na aba Consultas".
     * Buscar no C5 inteiro do banco daria OK a quem não está lá: cliente de
     * vendedor de fora do time, ou com o card em C5 mas sem a data de realização
     * preenchida (são 28 casos hoje, quase todos C5:EXECUTING com só a data
     * agendada).
     *
     * O período não entra: a aba 1 filtra por ano/mês na tela, mas a planilha não
     * traz o ano, e na prática todos os deals que casam estão no mesmo mês da
     * planilha.
     *
     * Desempate quando o mesmo valor tem mais de um deal: ORDER BY DESC mais
     * "guarda só a primeira ocorrência" escolhe o de consulta mais recente, sem
     * window function; o id_dealV2 (PK) desempata datas iguais.
     */
    private function buscarNaAbaConsultas(string $coluna, array $valores, callable $normalizar): array
    {
        return $this->buscarDeals($coluna, $valores, $normalizar, true);
    }

    /**
     * Mesma busca, sem as regras da aba Consultas — só a fase C5.
     *
     * Usada apenas pra recuperar o link do Bitrix de quem ficou Pendente. Nunca
     * pode alimentar o status: um card em C5 sem data de realização, ou sob
     * vendedor de fora do time, continua fora da primeira aba.
     */
    private function buscarEmTodoC5(string $coluna, array $valores, callable $normalizar): array
    {
        return $this->buscarDeals($coluna, $valores, $normalizar, false);
    }

    private function buscarDeals(string $coluna, array $valores, callable $normalizar, bool $soAbaConsultas): array
    {
        // $coluna entra concatenada no SQL, então só os dois valores conhecidos
        // passam — nunca vem de request.
        if (!in_array($coluna, [self::COL_EMAIL, self::COL_NOME], true)) {
            throw new \InvalidArgumentException('Coluna de busca não suportada: ' . $coluna);
        }

        $vals = array_values(array_unique(array_filter(
            array_map(fn($v) => trim((string)$v), $valores),
            fn($v) => $v !== ''
        )));
        if (!$vals) {
            return [];
        }

        // As duas colunas são latin1_swedish_ci: o IN já casa sem diferenciar
        // caixa nem acento ('Fulano_94@' x 'fulano_94@', 'Antônio' x 'Antonio'),
        // então nada de LOWER() na coluna. O TRIM cobre o espaço à direita que
        // vários nomes de card têm.
        // As regras extras da aba Consultas entram só quando pedido; sem elas
        // sobra a fase C5, que é o suficiente pra localizar o card no Bitrix.
        $regrasAba = '';
        $paramsAba = [];
        if ($soAbaConsultas) {
            $regrasAba = ' AND ' . self::SQL_CONSULTA_REALIZADA
                       . ' AND d.responsavel_dealV2 IN ('
                       . implode(',', array_fill(0, count(self::EQUIPE), '?')) . ')';
            $paramsAba = self::EQUIPE;
        }

        $sql = "
            SELECT
                d.email_dealV2                 AS email,
                d.nome_dealV2                  AS nome,
                d.responsavel_dealV2           AS responsavel,
                d.idBitrix_dealV2              AS id_bitrix,
                d.dataConsultaRealizada_dealV2 AS data_consulta_realizada
            FROM dealV2 d
            WHERE " . self::SQL_FASE_C5 . $regrasAba . "
              AND TRIM(d.{$coluna}) IN (" . implode(',', array_fill(0, count($vals), '?')) . ")
            ORDER BY d.dataConsultaRealizada_dealV2 DESC, d.id_dealV2 DESC
        ";

        // bind_param é posicional: equipe antes dos valores, na mesma ordem em
        // que os IN aparecem na query.
        $params = array_merge($paramsAba, $vals);
        $campo  = $coluna === self::COL_EMAIL ? 'email' : 'nome';

        $mapa = [];
        foreach ($this->queryAll($sql, $params) as $r) {
            $chave = $normalizar($r[$campo]);
            if ($chave !== '' && !isset($mapa[$chave])) {
                $mapa[$chave] = $r;
            }
        }
        return $mapa;
    }

    // ===================================================================
    // PLANILHA DO TIME (tabela planilhaConsulta)
    // ===================================================================

    /**
     * Linhas da planilha em que o time anota as consultas realizadas. As
     * chaves normalizadas (nome_norm, email_norm) são calculadas aqui, para o
     * cruzamento com o CRM em memória. Sem linhas, indisponivel=true faz quem
     * chama mostrar "—" em vez de marcar a base inteira como Pendente.
     *
     * @return array{linhas: array<int, array<string, string>>, indisponivel: bool}
     */
    private function carregarPlanilha(): array
    {
        $resultado = $this->db->query(
            "SELECT nomeCliente_planilhaConsulta   AS nome,
                    email_planilhaConsulta         AS email,
                    dataConsulta_planilhaConsulta  AS data,
                    nomeComercial_planilhaConsulta AS comercial,
                    obs_planilhaConsulta           AS obs
               FROM planilhaConsulta
              WHERE email_planilhaConsulta LIKE '%@%'
              ORDER BY dataConsulta_planilhaConsulta DESC, id_planilhaConsulta"
        );

        $linhas = [];
        while ($r = $resultado->fetch_assoc()) {
            $linhas[] = [
                'nome_cliente'   => (string)$r['nome'],
                'nome_norm'      => $this->nomeNorm($r['nome']),
                'email'          => (string)$r['email'],
                'email_norm'     => $this->emailNorm($r['email']),
                'data_consulta'  => $r['data'] ? date('d/m/Y', strtotime($r['data'])) : '',
                'nome_comercial' => (string)$r['comercial'],
                'obs'            => (string)$r['obs'],
            ];
        }

        return ['linhas' => $linhas, 'indisponivel' => !$linhas];
    }

    /**
     * Texto -> minúsculo, sem acento e com espaço colapsado. Usado como chave
     * de cruzamento de nome entre a planilha e o CRM.
     *
     * Sem mb_*: nada garante que o servidor tenha a extensão mbstring (o resto
     * do projeto só usa mb_strlen em caminhos que dashboard nenhum executa). O
     * strtr troca as sequências UTF-8 acentuadas nas duas caixas byte a byte, e
     * o que sobra é ASCII — strtolower dá conta.
     */
    private function normalizarTexto(string $t): string
    {
        static $acentos = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ç' => 'c',
        ];
        $t = strtolower(strtr(trim($t), $acentos));
        return preg_replace('/\s+/', ' ', $t);
    }

    /**
     * Chave de cruzamento de nome — o fallback de quando o e-mail não bate.
     *
     * Sem acento, minúsculo e com espaço interno colapsado, então "Marcos
     * Antônio  Siqueira" casa com "Carlos Antonio Siqueira". Match é sempre
     * do nome inteiro: nada de comparação parcial, que casaria homônimos
     * demais (só 7 dos 3914 nomes em deals C5 têm mais de um e-mail, e é essa
     * taxa baixa que torna o fallback seguro).
     */
    private function nomeNorm(?string $nome): string
    {
        return $this->normalizarTexto((string)$nome);
    }

    /**
     * Chave de cruzamento de e-mail. Normalizar é obrigatório: email_dealV2 é
     * latin1_swedish_ci e o SQL casa sem ligar pra caixa, mas o cruzamento aqui
     * é em PHP — 'Fulano_94@exemplo.com' no CRM e 'fulano_94@...' na planilha
     * só batem depois do strtolower. strtolower (e não mb_strtolower) porque
     * e-mail é ASCII e mbstring pode não existir no servidor.
     */
    private function emailNorm(?string $email): string
    {
        return strtolower(trim((string)$email));
    }

    /**
     * Zero-date (0000-00-00, com ou sem parte de hora) ou vazio vira null;
     * senão formata via $this->data() (só a parte de data).
     */
    private function fmtDataOuNull(?string $v): ?string
    {
        $dataParte = substr((string)$v, 0, 10);
        if ($v === null || $v === '' || $dataParte <= '0000-00-00') {
            return null;
        }
        return $this->data($dataParte);
    }

    // ===================================================================
    // HELPERS: HTTP / JSON
    // ===================================================================

    private function g(string $key): ?string
    {
        $v = $_GET[$key] ?? null;
        return ($v === null || $v === '') ? null : trim($v);
    }

    private function jsonOut($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(\Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $isDev = strtoupper(self::$config['app']['env'] ?? 'DEV') === 'DEV';
        echo json_encode(
            ($isDev || $this->debugAutorizado())
                ? ['error' => $e->getMessage(), 'classe' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]
                : ['error' => 'Erro interno do servidor.'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    /**
     * Libera o detalhe do erro em produção pra quem apresentar o SQL_API_TOKEN
     * (header Authorization: Bearer, ou ?token=). É o mesmo segredo do
     * /sql/query, que já concede acesso bem maior — não abre superfície nova, e
     * sem isso um 500 no servidor é indepurável daqui, já que não há acesso ao
     * error_log.
     */
    private function debugAutorizado(): bool
    {
        $path = dirname(__DIR__) . '/.env';
        if (!is_file($path)) {
            return false;
        }
        $token = null;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos !== false && trim(substr($line, 0, $pos)) === 'SQL_API_TOKEN') {
                $token = trim(trim(substr($line, $pos + 1)), '"\'');
                break;
            }
        }
        if ($token === null || $token === '') {
            return false;
        }

        $enviado = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($enviado, 'Bearer ') === 0) {
            $enviado = substr($enviado, 7);
        } elseif ($enviado === '') {
            $enviado = (string)($_GET['token'] ?? '');
        }

        return $enviado !== '' && hash_equals($token, $enviado);
    }

    // ===================================================================
    // HELPERS: EXECUÇÃO DE QUERY (mysqli, sempre ? posicional + bind_param)
    // ===================================================================

    private function queryAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Erro na preparação da consulta: ' . $this->db->error);
        }
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    // ===================================================================
    // HELPERS: FILTROS
    // ===================================================================

    /**
     * Fase 1C.4: reescrito pra gerar intervalo de data literal (sargável) em
     * vez de YEAR($col)=?/MONTH($col) IN(...) — mesmo fix já provado em
     * marketing/executive/law/financeiro/helenacastro/chatwoot. Isso também
     * resolve a preocupação de `ORDER BY ... LIMIT 1000` sobre filtro
     * não-sargável em calcularLinhas() (mesma coluna do filtro e do ORDER BY).
     * Este arquivo não tem buildPeriodFilter() — nunca teve suporte a range
     * explícito (data_inicio/data_fim), só year/months.
     */
    private function buildDateFilter(string $col, $year, ?string $months): array
    {
        if (!preg_match('/^[A-Za-z_]\w*\.\w+$/', $col)) {
            return $this->buildDateFilterLegacy($col, $year, $months);
        }

        $years = ($year !== null && $year !== '')
            ? array_values(array_filter(array_map('trim', explode(',', (string)$year)), fn($v) => $v !== ''))
            : [];
        $ms = ($months !== null && $months !== '')
            ? array_values(array_filter(array_map('trim', explode(',', $months)), fn($v) => $v !== ''))
            : [];

        if (!$years) {
            if (!$ms) {
                return ['sql' => '1=1', 'params' => []];
            }
            return [
                'sql'    => "MONTH($col) IN (" . implode(',', array_fill(0, count($ms), '?')) . ")",
                'params' => $ms,
            ];
        }

        $monthInts = [];
        foreach ($ms as $m) {
            $monthInts[] = (int)$m;
        }
        $monthInts = array_values(array_unique($monthInts));
        sort($monthInts);

        $ranges = [];
        $params = [];
        foreach ($years as $yStr) {
            $y = (int)$yStr;
            if (!$monthInts) {
                $ranges[] = "($col >= ? AND $col <= ?)";
                $params[] = sprintf('%04d-01-01 00:00:00', $y);
                $params[] = sprintf('%04d-12-31 23:59:59', $y);
                continue;
            }
            foreach ($this->agruparMesesContiguos($monthInts) as [$mStart, $mEnd]) {
                $ranges[] = "($col >= ? AND $col <= ?)";
                $params[] = sprintf('%04d-%02d-01 00:00:00', $y, $mStart);
                $params[] = sprintf('%04d-%02d-%02d 23:59:59', $y, $mEnd, $this->ultimoDiaDoMes($y, $mEnd));
            }
        }

        return ['sql' => '(' . implode(' OR ', $ranges) . ')', 'params' => $params];
    }

    /** Versão antiga (YEAR()/MONTH(), não-sargável) — usada só se $col não for
     *  uma coluna simples (ver guarda em buildDateFilter). */
    private function buildDateFilterLegacy(string $col, $year, ?string $months): array
    {
        $conds  = [];
        $params = [];

        if ($year !== null && $year !== '') {
            $years = array_values(array_filter(array_map('trim', explode(',', (string)$year)), fn($v) => $v !== ''));
            if (count($years) === 1) {
                $conds[]  = "YEAR($col) = ?";
                $params[] = $years[0];
            } elseif (count($years) > 1) {
                $conds[] = "YEAR($col) IN (" . implode(',', array_fill(0, count($years), '?')) . ")";
                array_push($params, ...$years);
            }
        }

        if ($months !== null && $months !== '') {
            $ms = array_values(array_filter(array_map('trim', explode(',', $months)), fn($v) => $v !== ''));
            if ($ms) {
                $conds[] = "MONTH($col) IN (" . implode(',', array_fill(0, count($ms), '?')) . ")";
                array_push($params, ...$ms);
            }
        }

        return ['sql' => $conds ? implode(' AND ', $conds) : '1=1', 'params' => $params];
    }

    /** Agrupa uma lista ordenada de meses (1-12) em pares [inicio,fim] de sequências contíguas. */
    private function agruparMesesContiguos(array $monthIntsOrdenados): array
    {
        $runs = [];
        $start = $end = null;
        foreach ($monthIntsOrdenados as $m) {
            if ($start === null) {
                $start = $end = $m;
            } elseif ($m === $end + 1) {
                $end = $m;
            } else {
                $runs[] = [$start, $end];
                $start = $end = $m;
            }
        }
        if ($start !== null) {
            $runs[] = [$start, $end];
        }
        return $runs;
    }

    /** Último dia do mês $m no ano $y, sem depender de timezone (mktime/date). */
    private function ultimoDiaDoMes(int $y, int $m): int
    {
        if (in_array($m, [1, 3, 5, 7, 8, 10, 12], true)) {
            return 31;
        }
        if (in_array($m, [4, 6, 9, 11], true)) {
            return 30;
        }
        return ($y % 4 === 0 && ($y % 100 !== 0 || $y % 400 === 0)) ? 29 : 28;
    }

    private function buildInFilter(string $col, ?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return ['sql' => '', 'params' => []];
        }
        $vals = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($v) => $v !== ''));
        if (!$vals) {
            return ['sql' => '', 'params' => []];
        }
        return ['sql' => "AND $col IN (" . implode(',', array_fill(0, count($vals), '?')) . ")", 'params' => $vals];
    }
}
