<?php

require_once __DIR__ . '/Repositorio.php';

/**
 * Agregados do sistema de Gestão de Eventos (hotéis).
 *
 * Toda métrica exibida em qualquer tela sai daqui. É a resposta ao maior
 * defeito do sistema antigo: cada aba reimplementava os seus cálculos, e as
 * versões já haviam divergido entre si. Havia QUATRO fórmulas de "total do
 * evento" convivendo — a do card do hotel somava hotel+A/V+videomaker+
 * transporte, a do Top 5 usava só quatro parcelas e ainda misturava valores
 * com e sem filtro de data, a aba Valor Total incluía tudo, e o relatório
 * excluía "outros". Os números não fechavam entre telas.
 *
 * Aqui existe UMA fórmula:
 *
 *     total = hotel + A/V + videomaker + transporte + frete + materiais + custos extras
 *
 * As leituras são memoizadas por instância: uma tela chega a pedir os mesmos
 * filhos uma vez por hotel, e sem isso seria um reprocessamento por chamada.
 */
final class HotelCalculo
{
    /** Entidades filhas que compõem o custo de um evento. */
    private const CUSTOS = ['equipamento', 'videomaker', 'transporte', 'frete', 'material', 'custoExtra'];

    private array $memo = [];

    public function __construct(private HotelRepositorio $repo)
    {
    }

    // =====================================================================
    // Acesso memoizado
    // =====================================================================

    public function todos(string $ent): array
    {
        return $this->memo['todos:' . $ent] ??= $this->repo->todos($ent);
    }

    /** @return array<int,array<int,array>> idHotel => registros */
    public function porHotel(string $ent): array
    {
        if (isset($this->memo['porHotel:' . $ent])) {
            return $this->memo['porHotel:' . $ent];
        }

        $col   = HotelSchema::campo($ent, 'idHotel');
        $mapa  = [];
        foreach ($this->todos($ent) as $r) {
            $id = $r[$col] ?? null;
            if ($id !== null) {
                $mapa[(int)$id][] = $r;
            }
        }

        return $this->memo['porHotel:' . $ent] = $mapa;
    }

    /**
     * Não há nenhum hotel cadastrado — situação diferente de "nenhum evento
     * neste período". Na fase de arquivo isto quase sempre significa que a
     * pasta .mrphp/hotel/ não existe naquele ambiente, e a tela precisa dizer
     * isso em vez de exibir uma fileira de zeros como se fosse um resultado.
     */
    public function semDados(): bool
    {
        return count($this->todos('hotel')) === 0;
    }

    /** Registros sem hotel vinculado — materiais avulsos e cadastros soltos. */
    public function semHotel(string $ent): array
    {
        $col = HotelSchema::campo($ent, 'idHotel');
        return array_values(array_filter(
            $this->todos($ent),
            static fn(array $r): bool => ($r[$col] ?? null) === null
        ));
    }

    // =====================================================================
    // Períodos
    // =====================================================================

    /** Abreviações usadas no filtro e no rótulo do recorte. */
    public const MESES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    /**
     * Anos oferecidos no filtro: os que existem de fato nos dados, do mais
     * recente para o mais antigo. No sistema antigo a lista de períodos era
     * só a cadastrada, então um evento fora dela ficava inalcançável — só
     * aparecia em "Todos".
     *
     * @return array<int,array{ano:string,eventos:int}>
     */
    public function anos(): array
    {
        $contagem = [];
        foreach ($this->hoteis() as $h) {
            $d = (string)$h['data_hotel'];
            if (strlen($d) >= 4) {
                $ano = substr($d, 0, 4);
                $contagem[$ano] = ($contagem[$ano] ?? 0) + 1;
            }
        }

        krsort($contagem);

        $out = [];
        foreach ($contagem as $ano => $n) {
            $out[] = ['ano' => (string)$ano, 'eventos' => $n];
        }
        return $out;
    }

    /**
     * Quantos eventos cada mês tem, dentro dos anos informados (ou em todos,
     * se a lista vier vazia). As doze chaves vêm sempre preenchidas: a tela
     * desenha as doze opções, e as vazias aparecem desabilitadas em vez de
     * sumirem — some a opção, some a informação de que não há nada ali.
     *
     * @param string[] $anos
     * @return array<int,int> mês (1-12) => eventos
     */
    public function mesesDosAnos(array $anos = []): array
    {
        $out = array_fill(1, 12, 0);

        foreach ($this->hoteis() as $h) {
            $d = (string)$h['data_hotel'];
            if (strlen($d) < 7) {
                continue;
            }
            if ($anos && !in_array(substr($d, 0, 4), $anos, true)) {
                continue;
            }
            $mes = (int)substr($d, 5, 2);
            if ($mes >= 1 && $mes <= 12) {
                $out[$mes]++;
            }
        }

        return $out;
    }

    /**
     * Normaliza o recorte vindo da querystring. Nunca usa o valor cru.
     *
     * Ano e mês são os dois multisseleção, como os dropdowns equivalentes dos
     * demais dashboards. Meses sem ano é recorte legítimo — "todos os
     * marços". Sem nenhum dos dois, devolve null, que os consumidores leem
     * como "sem recorte".
     *
     * @return array{anos:string[],meses:int[]}|null
     */
    public function filtroValido(?string $anos, ?string $meses): ?array
    {
        $anosOk = [];
        foreach (explode(',', (string)$anos) as $a) {
            $a = trim($a);
            if (preg_match('/^\d{4}$/', $a) && !in_array($a, $anosOk, true)) {
                $anosOk[] = $a;
            }
        }
        sort($anosOk);

        $mesesOk = [];
        foreach (explode(',', (string)$meses) as $m) {
            $n = (int)trim($m);
            if ($n >= 1 && $n <= 12 && !in_array($n, $mesesOk, true)) {
                $mesesOk[] = $n;
            }
        }
        sort($mesesOk);

        if (!$anosOk && !$mesesOk) {
            return null;
        }

        return ['anos' => $anosOk, 'meses' => $mesesOk];
    }

    /**
     * Rótulo legível do recorte, para o cabeçalho de impressão do relatório.
     */
    public function rotuloFiltro(?array $filtro): string
    {
        if ($filtro === null) {
            return 'todos os períodos';
        }

        $meses = $filtro['meses'];
        $anos  = $filtro['anos'];
        $rotAnos = $anos ? implode(', ', $anos) : null;

        if (!$meses) {
            return (string)$rotAnos;
        }

        // Sequência contínua vira intervalo; salteada, lista.
        $contigua = count($meses) > 1
            && ($meses[count($meses) - 1] - $meses[0] + 1) === count($meses);

        $nomes = $contigua
            ? self::MESES[$meses[0] - 1] . '–' . self::MESES[$meses[count($meses) - 1] - 1]
            : implode(', ', array_map(static fn(int $m): string => self::MESES[$m - 1], $meses));

        return $rotAnos !== null ? $nomes . ' de ' . $rotAnos : $nomes . ' (todos os anos)';
    }

    /**
     * O recorte vale para esta data?
     *
     * Evento sem data fica fora de qualquer recorte — não no meio dele. Era
     * o comportamento anterior por acidente (chavePeriodo() devolvia null e a
     * comparação falhava); aqui é explícito.
     */
    private function dentroDoFiltro(?array $filtro, ?string $data): bool
    {
        if ($filtro === null) {
            return true;
        }
        $d = (string)$data;
        if (strlen($d) < 7) {
            return false;
        }
        if ($filtro['anos'] && !in_array(substr($d, 0, 4), $filtro['anos'], true)) {
            return false;
        }
        if ($filtro['meses'] && !in_array((int)substr($d, 5, 2), $filtro['meses'], true)) {
            return false;
        }
        return true;
    }

    /**
     * Hotéis do recorte; sem recorte, todos.
     *
     * @param array{anos:string[],meses:int[]}|null $filtro
     */
    public function hoteis(?array $filtro = null): array
    {
        $lista = $this->todos('hotel');

        if ($filtro !== null) {
            $lista = array_values(array_filter(
                $lista,
                fn(array $h): bool => $this->dentroDoFiltro($filtro, $h['data_hotel'])
            ));
        }

        usort($lista, static function (array $a, array $b): int {
            // Sem data vai para o fim, em vez de quebrar a comparação — a aba
            // Valor Total do sistema antigo ordenava sem essa guarda e
            // estourava quando algum hotel estava sem data.
            if (!$a['data_hotel']) return 1;
            if (!$b['data_hotel']) return -1;
            return strcmp($a['data_hotel'], $b['data_hotel']);
        });

        return $lista;
    }

    // =====================================================================
    // Custo por evento — a fórmula única
    // =====================================================================

    /**
     * @return array{total:float,parcelas:array<string,float>}
     */
    public function custoDoHotel(array $hotel): array
    {
        $id       = (int)$hotel['id_hotel'];
        $parcelas = ['hotel' => (float)$hotel['valor_hotel']];

        foreach (self::CUSTOS as $ent) {
            $soma = 0.0;
            $col  = HotelSchema::campo($ent, 'valor');
            foreach ($this->porHotel($ent)[$id] ?? [] as $r) {
                $soma += (float)($r[$col] ?? 0);
            }
            $parcelas[$ent] = round($soma, 2);
        }

        return ['total' => round(array_sum($parcelas), 2), 'parcelas' => $parcelas];
    }

    /** Soma das parcelas de um conjunto de hotéis, na mesma decomposição. */
    public function custoDoConjunto(array $hoteis): array
    {
        $parcelas = ['hotel' => 0.0];
        foreach (self::CUSTOS as $ent) {
            $parcelas[$ent] = 0.0;
        }

        foreach ($hoteis as $h) {
            foreach ($this->custoDoHotel($h)['parcelas'] as $k => $v) {
                $parcelas[$k] += $v;
            }
        }

        $parcelas = array_map(static fn(float $v): float => round($v, 2), $parcelas);
        return ['total' => round(array_sum($parcelas), 2), 'parcelas' => $parcelas];
    }

    /**
     * Consolidação por evento — a aba "Valor total".
     *
     * Eventos cancelados ficam de fora: o contrato não vai acontecer, e somar
     * o valor negociado inflaria o custo do período.
     *
     * @return array{eventos:array,cidades:array,totais:array}
     */
    /**
     * @param string[] $filtroCidades vazio = todas. Nome distinto de $cidades
     *                 de proposito: a variavel local abaixo e a CONTAGEM por
     *                 cidade, que alimenta o dropdown.
     */
    public function consolidado(?array $filtro = null, array $filtroCidades = []): array
    {
        $hoteis = array_values(array_filter(
            $this->hoteis($filtro),
            static fn(array $h): bool => $h['status_hotel'] !== 'cancelado'
        ));

        // As cidades do filtro saem do conjunto ANTES do recorte por cidade —
        // senão escolher uma faria as outras sumirem da própria barra.
        $cidades = [];
        foreach ($hoteis as $h) {
            $c = $h['cidade_hotel'] !== '' ? $h['cidade_hotel'] : '(sem cidade)';
            $cidades[$c] = ($cidades[$c] ?? 0) + 1;
        }
        ksort($cidades);

        if ($filtroCidades) {
            $hoteis = array_values(array_filter(
                $hoteis,
                static fn(array $h): bool => in_array(
                    $h['cidade_hotel'] !== '' ? $h['cidade_hotel'] : '(sem cidade)',
                    $filtroCidades,
                    true
                )
            ));
        }

        $eventos = [];
        foreach ($hoteis as $h) {
            $custo = $this->custoDoHotel($h);
            $id    = (int)$h['id_hotel'];

            $pendentes = 0;
            foreach ($this->porHotel('transporte')[$id] ?? [] as $t) {
                if ($t['status_transporte'] === 'pendente') {
                    $pendentes++;
                }
            }

            $eventos[] = [
                'id'        => $id,
                'nome'      => $h['nome_hotel'],
                'cidade'    => $h['cidade_hotel'],
                'uf'        => $h['uf_hotel'],
                'data'      => $h['data_hotel'],
                'status'    => $h['status_hotel'],
                'parcelas'  => $custo['parcelas'],
                'total'     => $custo['total'],
                'pendentes' => $pendentes,
            ];
        }

        return [
            'eventos' => $eventos,
            'cidades' => $cidades,
            'totais'  => $this->custoDoConjunto($hoteis),
        ];
    }

    /**
     * Lista de uma entidade filha, já com o evento a que pertence.
     *
     * O recorte por período segue o EVENTO, não a data do próprio registro:
     * uma passagem comprada em dezembro para um evento de janeiro pertence ao
     * evento de janeiro. Registros sem vínculo só aparecem quando não há
     * período selecionado — senão sumiriam sem explicação.
     *
     * @return array{itens:array,totais:array,semVinculo:int}
     */
    public function listaFilhos(string $ent, ?array $filtro = null): array
    {
        $colHotel  = HotelSchema::campo($ent, 'idHotel');
        $colValor  = HotelSchema::campo($ent, 'valor');
        $colStatus = HotelSchema::campo($ent, 'status');

        $hoteisPorId = [];
        foreach ($this->todos('hotel') as $h) {
            $hoteisPorId[(int)$h['id_hotel']] = $h;
        }

        $itens      = [];
        $porStatus  = [];
        $soma       = 0.0;
        $semVinculo = 0;

        foreach ($this->todos($ent) as $r) {
            $idHotel = $r[$colHotel] ?? null;
            $pai     = $idHotel !== null ? ($hoteisPorId[(int)$idHotel] ?? null) : null;

            if ($filtro !== null) {
                if (!$pai || !$this->dentroDoFiltro($filtro, $pai['data_hotel'])) {
                    continue;
                }
            }

            if (!$pai) {
                $semVinculo++;
            }

            $valor = (float)($r[$colValor] ?? 0);
            $soma += $valor;

            $status = (string)($r[$colStatus] ?? '');
            $porStatus[$status] = ($porStatus[$status] ?? 0) + 1;

            $itens[] = $r + [
                '_evento' => $pai ? [
                    'id'     => (int)$pai['id_hotel'],
                    'cidade' => $pai['cidade_hotel'],
                    'uf'     => $pai['uf_hotel'],
                    'data'   => $pai['data_hotel'],
                    'nome'   => $pai['nome_hotel'],
                ] : null,
            ];
        }

        // Ordena pela data do evento; sem vínculo vai para o fim.
        usort($itens, static function (array $a, array $b): int {
            $da = $a['_evento']['data'] ?? null;
            $db = $b['_evento']['data'] ?? null;
            if (!$da) return 1;
            if (!$db) return -1;
            return strcmp($da, $db);
        });

        return [
            'itens'  => $itens,
            'totais' => [
                'registros' => count($itens),
                'valor'     => round($soma, 2),
                'porStatus' => $porStatus,
            ],
            'semVinculo' => $semVinculo,
        ];
    }

    // =====================================================================
    // Cartões de resumo
    // =====================================================================

    /**
     * Cartões do topo.
     *
     * Eventos cancelados ficam fora de todas as contagens e do custo, para
     * bater com a aba Valor total. O sistema antigo contava cancelados nos
     * cartões e os excluía na consolidação; os números só não divergiam
     * porque, por acaso, o único evento cancelado tinha custo zero. Basta
     * cancelar um evento que já tenha despesas para as duas telas passarem
     * a discordar — o cancelado continua visível como nota à parte.
     */
    public function stats(?array $filtro = null): array
    {
        $todos  = $this->hoteis($filtro);
        $hoteis = array_values(array_filter(
            $todos,
            static fn(array $h): bool => $h['status_hotel'] !== 'cancelado'
        ));

        $terceirizado = 0;
        $semNf        = 0;
        $cidades      = [];

        foreach ($hoteis as $h) {
            if ($h['responsavelAv_hotel'] === 'terceirizado') {
                $terceirizado++;
            }
            if (!$h['nf_hotel']) {
                $semNf++;
            }
            if ($h['cidade_hotel'] !== '') {
                $cidades[$h['cidade_hotel']] = true;
            }
        }

        return [
            'eventos'      => count($hoteis),
            'cancelados'   => count($todos) - count($hoteis),
            'terceirizado' => $terceirizado,
            'semNf'        => $semNf,
            'cidades'      => count($cidades),
            'custoTotal'   => $this->custoDoConjunto($hoteis)['total'],
        ];
    }

    // =====================================================================
    // Checklist de pendências
    // =====================================================================

    /**
     * Nove verificações por evento.
     *
     * No sistema antigo havia duas listas divergentes: a que contava
     * pendências no banner e a que o modal exibia. A do modal considerava um
     * videomaker apenas vinculado como ✅, enquanto a do banner acusava
     * pendência se ele estivesse com pagamento pendente; e o modal tinha um
     * item de entrega de materiais que nunca gerava pendência. Aqui é uma
     * lista só: pendência é exatamente um item com ok = false.
     *
     * @return array{itens:array,ok:int,total:int,pct:int,pendencias:array}
     */
    public function checklist(array $hotel): array
    {
        $id = (int)$hotel['id_hotel'];

        $equipamentos = $this->porHotel('equipamento')[$id] ?? [];
        $videomakers  = $this->porHotel('videomaker')[$id] ?? [];
        $fretes       = $this->porHotel('frete')[$id] ?? [];

        $terceirizado = $hotel['responsavelAv_hotel'] === 'terceirizado';

        $comFornecedor = array_filter(
            $equipamentos,
            static fn(array $e): bool => trim((string)$e['fornecedor_equipamento']) !== ''
        );
        $avPago = $comFornecedor && !array_filter(
            $comFornecedor,
            static fn(array $e): bool => $e['status_equipamento'] !== 'pago'
        );

        $vmPendente = array_filter(
            $videomakers,
            static fn(array $v): bool => in_array($v['status_videomaker'], ['pendente', 'aguardando'], true)
        );

        $itens = [
            [
                'chave'  => 'contrato',
                'rotulo' => 'Contrato assinado',
                'ok'     => $hotel['status_hotel'] === 'assinado',
                'detalhe' => $hotel['dataAss_hotel']
                    ? 'Assinado em ' . $this->dataBr($hotel['dataAss_hotel'])
                    : 'Situação: ' . $hotel['status_hotel'],
            ],
            [
                'chave'  => 'oc',
                'rotulo' => 'OC aberta',
                'ok'     => (bool)$hotel['oc_hotel'],
                'detalhe' => $hotel['ocNum_hotel'] !== '' ? 'OC ' . $hotel['ocNum_hotel'] : 'Não aberta',
            ],
            [
                'chave'  => 'nf',
                'rotulo' => 'NF recebida',
                'ok'     => (bool)$hotel['nf_hotel'],
                'detalhe' => $hotel['dataNf_hotel']
                    ? 'Recebida em ' . $this->dataBr($hotel['dataNf_hotel'])
                    : 'Não recebida',
            ],
            [
                'chave'  => 'quartos',
                'rotulo' => 'Quartos reservados',
                'ok'     => (bool)$hotel['quartosConfirmado_hotel'],
                'detalhe' => $hotel['quartosReserva_hotel'] !== ''
                    ? 'Reserva: ' . $hotel['quartosReserva_hotel']
                    : 'Não confirmado — 2 single + 1 duplo',
            ],
            [
                'chave'  => 'av',
                'rotulo' => 'Equipamentos A/V',
                'ok'     => !$terceirizado || count($comFornecedor) > 0,
                'detalhe' => !$terceirizado
                    ? 'Incluso com o hotel'
                    : (count($comFornecedor) > 0
                        ? implode(', ', array_column($comFornecedor, 'fornecedor_equipamento'))
                        : 'Fornecedor não definido'),
            ],
            [
                'chave'  => 'avPago',
                'rotulo' => 'Pagamento do A/V',
                'ok'     => !$terceirizado || $avPago,
                'detalhe' => !$terceirizado ? 'Incluso com o hotel' : ($avPago ? 'Pago' : 'Pendente'),
            ],
            [
                'chave'  => 'videomaker',
                'rotulo' => 'Videomaker',
                'ok'     => count($videomakers) > 0 && count($vmPendente) === 0,
                'detalhe' => count($videomakers) === 0
                    ? 'Não vinculado'
                    : implode(', ', array_map(
                        static fn(array $v): string => $v['nome_videomaker'] . ' (' . $v['status_videomaker'] . ')',
                        $videomakers
                    )),
            ],
            [
                'chave'  => 'frete',
                'rotulo' => 'Frete registrado',
                'ok'     => count($fretes) > 0,
                'detalhe' => count($fretes) > 0
                    ? 'Entrega: ' . $this->dataBr($fretes[0]['dataEntrega_frete'])
                    : 'Não registrado',
            ],
            [
                'chave'  => 'entrega',
                'rotulo' => 'Entrega de materiais',
                'ok'     => $hotel['entregaStatus_hotel'] === 'no_prazo',
                'detalhe' => $hotel['entregaStatus_hotel'] !== ''
                    ? $hotel['entregaStatus_hotel']
                    : 'Não informado',
            ],
        ];

        $ok    = count(array_filter($itens, static fn(array $i): bool => $i['ok']));
        $total = count($itens);

        return [
            'itens'      => $itens,
            'ok'         => $ok,
            'total'      => $total,
            'pct'        => (int)round($ok / $total * 100),
            'pendencias' => array_values(array_filter($itens, static fn(array $i): bool => !$i['ok'])),
        ];
    }

    // =====================================================================
    // Alertas
    // =====================================================================

    /**
     * Eventos em até 7 dias e entre 8 e 30 dias.
     *
     * Deliberadamente ignora o filtro de período: é um aviso do que está
     * chegando, e some se a pessoa estiver olhando outro semestre justamente
     * quando o evento é na semana que vem.
     *
     * @return array{proximos:array,emBreve:array}
     */
    public function alertas(?string $hoje = null): array
    {
        $hoje = $hoje ?: date('Y-m-d');
        $ts   = strtotime($hoje . ' 00:00:00');

        $proximos = [];
        $emBreve  = [];

        foreach ($this->todos('hotel') as $h) {
            if (!$h['data_hotel'] || $h['status_hotel'] === 'cancelado') {
                continue;
            }

            $dias = (int)round((strtotime($h['data_hotel'] . ' 00:00:00') - $ts) / 86400);
            if ($dias < 0 || $dias > 30) {
                continue;
            }

            $item = [
                'id'         => (int)$h['id_hotel'],
                'nome'       => $h['nome_hotel'],
                'nomeEvento' => $h['nomeEvento_hotel'],
                'cidade'     => $h['cidade_hotel'],
                'data'       => $h['data_hotel'],
                'dias'       => $dias,
                'quando'     => $dias === 0 ? 'HOJE' : ($dias === 1 ? 'AMANHÃ' : "em {$dias} dias"),
            ];

            if ($dias <= 7) {
                $chk = $this->checklist($h);
                $item['pendencias'] = count($chk['pendencias']);
                $item['pct']        = $chk['pct'];
                $proximos[]         = $item;
            } else {
                $emBreve[] = $item;
            }
        }

        $porDias = static fn(array $a, array $b): int => $a['dias'] <=> $b['dias'];
        usort($proximos, $porDias);
        usort($emBreve, $porDias);

        return ['proximos' => $proximos, 'emBreve' => $emBreve];
    }

    // =====================================================================
    // Relatório
    // =====================================================================

    /**
     * Colunas do relatório — fonte única para a tela e para o CSV.
     *
     * `chave => [rótulo, grupo]`. O grupo define em que aba a coluna aparece:
     * `geral` na tabela principal, `entrega` na aba de entregas, `csv` só na
     * exportação. Uma definição só evita o que acontecia no sistema antigo,
     * onde a tabela tinha 20 colunas, o CSV tinha 43 e as duas listas eram
     * mantidas à mão em lugares diferentes.
     */
    public const COLUNAS_RELATORIO = [
        'cidade'          => ['Cidade',              'geral'],
        'uf'              => ['UF',                  'csv'],
        'hotel'           => ['Hotel',               'geral'],
        'endereco'        => ['Endereço',            'csv'],
        'capacidade'      => ['Capacidade',          'csv'],
        'avaliacaoHotel'  => ['Avaliação do hotel',  'csv'],
        'data'            => ['Data do evento',      'geral'],
        'valorHotel'      => ['Valor do hotel',      'geral'],
        'tipoPag'         => ['Tipo de pagamento',   'geral'],
        'statusContrato'  => ['Situação',            'geral'],
        'dataAss'         => ['Assinatura',          'geral'],
        'nf'              => ['NF',                  'geral'],
        'dataNf'          => ['Data da NF',          'geral'],
        'oc'              => ['OC',                  'geral'],
        'dataOc'          => ['Data da OC',          'geral'],
        'ocNum'           => ['Nº da OC',            'geral'],
        'responsavelAv'   => ['A/V',                 'geral'],
        'fornecedorAv'    => ['Fornecedor A/V',      'geral'],
        'avaliacaoAv'     => ['Avaliação do A/V',    'csv'],
        'contatoAv'       => ['Contato A/V',         'csv'],
        'valorAv'         => ['Valor do A/V',        'geral'],
        'statusAv'        => ['Status do A/V',       'geral'],
        'ocAv'            => ['OC do A/V',           'csv'],
        'entregaAv'       => ['A/V pontual',         'entrega'],
        'horaAv'          => ['Hora do A/V',         'entrega'],
        'obsAv'           => ['Obs. do A/V',         'entrega'],
        'entregaStatus'   => ['Entrega de materiais', 'entrega'],
        'entregaData'     => ['Data da entrega',     'entrega'],
        'entregaMateriais' => ['Materiais entregues', 'entrega'],
        'entregaObs'      => ['Comentário da entrega', 'entrega'],
        'quartosReserva'  => ['Reserva de quartos',  'csv'],
        'quartosOk'       => ['Quartos confirmados', 'csv'],
        'videomaker'      => ['Videomaker',          'csv'],
        'contatoVm'       => ['Contato do videomaker', 'csv'],
        'valorVm'         => ['Valor do videomaker', 'csv'],
        'statusVm'        => ['Status do videomaker', 'csv'],
        'valorTransporte' => ['Transportes',         'csv'],
        'transpPendentes' => ['Transportes pendentes', 'csv'],
        'valorFrete'      => ['Frete',               'csv'],
        'valorMaterial'   => ['Materiais',           'csv'],
        'valorExtra'      => ['Custos extras',       'csv'],
        'total'           => ['Total do evento',     'geral'],
        'obs'             => ['Observações',         'csv'],
    ];

    /** Colunas de um grupo, na ordem declarada. */
    public static function colunasDe(string $grupo): array
    {
        $out = [];
        foreach (self::COLUNAS_RELATORIO as $chave => [$rotulo, $g]) {
            // A aba de entregas mostra também a identificação do evento.
            if ($g === $grupo || ($grupo === 'entrega' && in_array($chave, ['cidade', 'data', 'fornecedorAv'], true))) {
                $out[$chave] = $rotulo;
            }
        }
        return $out;
    }

    /**
     * Uma linha por evento, com todos os campos do relatório resolvidos.
     *
     * @return array{linhas:array,resumo:array}
     */
    public function relatorio(?array $filtro = null): array
    {
        $linhas = [];
        $resumo = [
            'eventos'    => 0,
            'comAv'      => 0,
            'avNoPrazo'  => 0, 'avAtrasado' => 0, 'avProblema' => 0,
            'entNoPrazo' => 0, 'entAtrasado' => 0, 'entProblema' => 0, 'entPendente' => 0,
            'total'      => 0.0,
        ];

        foreach ($this->hoteis($filtro) as $h) {
            $id    = (int)$h['id_hotel'];
            $custo = $this->custoDoHotel($h);

            $equipes = $this->porHotel('equipamento')[$id] ?? [];
            $vms     = $this->porHotel('videomaker')[$id] ?? [];
            $transp  = $this->porHotel('transporte')[$id] ?? [];

            $pendentes = count(array_filter(
                $transp,
                static fn(array $t): bool => $t['status_transporte'] === 'pendente'
            ));

            // Vários fornecedores por evento: junta os nomes e soma os valores.
            // O original exibia o nome do PRIMEIRO e o valor somado de TODOS,
            // então nome e valor podiam se referir a coisas diferentes.
            $juntar = static fn(array $itens, string $col): string =>
                implode(', ', array_filter(array_map(static fn(array $i): string => (string)$i[$col], $itens)));

            $entregaAv = $equipes[0]['entrega_equipamento'] ?? '';

            $linhas[] = [
                'id'               => $id,
                'cidade'           => $h['cidade_hotel'],
                'uf'               => $h['uf_hotel'],
                'hotel'            => $h['nome_hotel'],
                'endereco'         => $h['endereco_hotel'],
                'capacidade'       => $h['capacidade_hotel'],
                'avaliacaoHotel'   => (int)$h['rating_hotel'],
                'data'             => $h['data_hotel'],
                'valorHotel'       => (float)$h['valor_hotel'],
                'tipoPag'          => $h['tipoPag_hotel'],
                'statusContrato'   => $h['status_hotel'],
                'dataAss'          => $h['dataAss_hotel'],
                'nf'               => (bool)$h['nf_hotel'],
                'dataNf'           => $h['dataNf_hotel'],
                'oc'               => (bool)$h['oc_hotel'],
                'dataOc'           => $h['dataOc_hotel'],
                'ocNum'            => $h['ocNum_hotel'],
                'responsavelAv'    => $h['responsavelAv_hotel'],
                'fornecedorAv'     => $juntar($equipes, 'fornecedor_equipamento'),
                'avaliacaoAv'      => $equipes ? (int)$equipes[0]['rating_equipamento'] : 0,
                'contatoAv'        => $juntar($equipes, 'contato_equipamento'),
                'valorAv'          => $custo['parcelas']['equipamento'],
                'statusAv'         => $juntar($equipes, 'status_equipamento'),
                'ocAv'             => $juntar($equipes, 'ocNum_equipamento'),
                'entregaAv'        => $entregaAv,
                'horaAv'           => $juntar($equipes, 'hora_equipamento'),
                'obsAv'            => $juntar($equipes, 'obs_equipamento'),
                'entregaStatus'    => $h['entregaStatus_hotel'],
                'entregaData'      => $h['entregaData_hotel'],
                'entregaMateriais' => $h['entregaMateriais_hotel'],
                'entregaObs'       => $h['entregaObs_hotel'],
                'quartosReserva'   => $h['quartosReserva_hotel'],
                'quartosOk'        => (bool)$h['quartosConfirmado_hotel'],
                'videomaker'       => $juntar($vms, 'nome_videomaker'),
                'contatoVm'        => $juntar($vms, 'contato_videomaker'),
                'valorVm'          => $custo['parcelas']['videomaker'],
                'statusVm'         => $juntar($vms, 'status_videomaker'),
                'valorTransporte'  => $custo['parcelas']['transporte'],
                'transpPendentes'  => $pendentes,
                'valorFrete'       => $custo['parcelas']['frete'],
                'valorMaterial'    => $custo['parcelas']['material'],
                'valorExtra'       => $custo['parcelas']['custoExtra'],
                'total'            => $custo['total'],
                'obs'              => $h['obs_hotel'],
            ];

            $resumo['eventos']++;
            $resumo['total'] += $custo['total'];
            if ($equipes)                      { $resumo['comAv']++; }
            if ($entregaAv === 'no_prazo')     { $resumo['avNoPrazo']++; }
            if ($entregaAv === 'atrasado')     { $resumo['avAtrasado']++; }
            if ($entregaAv === 'problema')     { $resumo['avProblema']++; }

            switch ($h['entregaStatus_hotel']) {
                case 'no_prazo': $resumo['entNoPrazo']++; break;
                case 'atrasado': $resumo['entAtrasado']++; break;
                case 'problema': $resumo['entProblema']++; break;
                case 'pendente': $resumo['entPendente']++; break;
            }
        }

        $resumo['total'] = round($resumo['total'], 2);
        return ['linhas' => $linhas, 'resumo' => $resumo];
    }

    // =====================================================================
    // Visão geral
    // =====================================================================

    /**
     * Todos os blocos analíticos da tela inicial, num cálculo só.
     *
     * @return array
     */
    public function visaoGeral(?array $filtro = null): array
    {
        $hoteis = array_values(array_filter(
            $this->hoteis($filtro),
            static fn(array $h): bool => $h['status_hotel'] !== 'cancelado'
        ));

        $custos = [];
        foreach ($hoteis as $h) {
            $c = $this->custoDoHotel($h);
            $custos[] = [
                'id'       => (int)$h['id_hotel'],
                'cidade'   => $h['cidade_hotel'] !== '' ? $h['cidade_hotel'] : $h['nome_hotel'],
                'nome'     => $h['nome_hotel'],
                'data'     => $h['data_hotel'],
                'total'    => $c['total'],
                'parcelas' => $c['parcelas'],
            ];
        }

        usort($custos, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);
        $comCusto = array_values(array_filter($custos, static fn(array $c): bool => $c['total'] > 0));

        $totalGeral = array_sum(array_column($custos, 'total'));
        $media      = $comCusto ? $totalGeral / count($comCusto) : 0.0;

        return [
            'eventos'    => count($hoteis),
            'contratos'  => $this->contarPor($this->hoteis($filtro), 'status_hotel'),
            // Situação dos fornecedores de A/V do mesmo recorte, para a rosca
            // ao lado da de hotéis. Passa pelo listaFilhos() em vez de contar
            // aqui para herdar a regra de vínculo com o evento — um
            // equipamento órfão não entra em recorte nenhum.
            'contratosAv' => $this->contarPor(
                $this->listaFilhos('equipamento', $filtro)['itens'],
                'status_equipamento'
            ),
            'pendencias' => $this->pendenciasFinanceiras($hoteis),
            'av'         => $this->distribuicaoAv($hoteis),
            'porMes'     => $this->gastosPorMes($custos),
            'top'        => array_slice($comCusto, 0, 5),
            'avaliacoes' => $this->histogramasAvaliacao($hoteis),
            'kpis'       => [
                'total'      => round($totalGeral, 2),
                'media'      => round($media, 2),
                'maisCaro'   => $comCusto[0] ?? null,
                'maisBarato' => $comCusto ? end($comCusto) : null,
                'comCusto'   => count($comCusto),
            ],
            'breakdown' => $this->breakdown($custos, $totalGeral),
            'tempos'    => $this->metricasDeTempo($hoteis),
            'projecao'  => $this->projecao($hoteis, $media),
        ];
    }

    private function contarPor(array $itens, string $coluna): array
    {
        $out = [];
        foreach ($itens as $i) {
            $v = (string)($i[$coluna] ?? '');
            $out[$v] = ($out[$v] ?? 0) + 1;
        }
        return $out;
    }

    private function pendenciasFinanceiras(array $hoteis): array
    {
        $n = count($hoteis);
        $nf = $oc = $quartos = 0;
        foreach ($hoteis as $h) {
            if ($h['nf_hotel'])                { $nf++; }
            if ($h['oc_hotel'])                { $oc++; }
            if ($h['quartosConfirmado_hotel']) { $quartos++; }
        }
        return ['total' => $n, 'nf' => $nf, 'oc' => $oc, 'quartos' => $quartos];
    }

    private function distribuicaoAv(array $hoteis): array
    {
        $terc = 0;
        foreach ($hoteis as $h) {
            if ($h['responsavelAv_hotel'] === 'terceirizado') {
                $terc++;
            }
        }
        return ['terceirizado' => $terc, 'hotel' => count($hoteis) - $terc];
    }

    /**
     * Gasto mensal decomposto.
     *
     * Inclui frete e materiais. O original calculava as duas parcelas e não
     * as usava no gráfico — o mês aparecia mais barato do que foi.
     */
    private function gastosPorMes(array $custos): array
    {
        $meses = [];
        foreach ($custos as $c) {
            if (!$c['data']) {
                continue;
            }
            $chave = substr($c['data'], 0, 7);
            foreach ($c['parcelas'] as $k => $v) {
                $meses[$chave][$k] = ($meses[$chave][$k] ?? 0) + $v;
            }
        }
        ksort($meses);

        $out = [];
        foreach ($meses as $chave => $parcelas) {
            $out[] = ['mes' => $chave, 'parcelas' => array_map(static fn($v) => round($v, 2), $parcelas)];
        }
        return $out;
    }

    private function histogramasAvaliacao(array $hoteis): array
    {
        $hist = static function (array $notas): array {
            $dist = array_fill(1, 5, 0);
            foreach ($notas as $n) {
                if ($n >= 1 && $n <= 5) {
                    $dist[$n]++;
                }
            }
            $avaliados = array_sum($dist);
            $soma = 0;
            foreach ($dist as $nota => $qtd) {
                $soma += $nota * $qtd;
            }
            return [
                'dist'      => $dist,
                'avaliados' => $avaliados,
                'media'     => $avaliados ? round($soma / $avaliados, 1) : null,
            ];
        };

        $notasHotel = array_map(static fn(array $h): int => (int)$h['rating_hotel'], $hoteis);

        $ids   = array_column($hoteis, 'id_hotel');
        $notasAv = [];
        foreach ($this->todos('equipamento') as $e) {
            if (in_array((int)$e['idHotel_equipamento'], array_map('intval', $ids), true)) {
                $notasAv[] = (int)$e['rating_equipamento'];
            }
        }

        return ['hotel' => $hist($notasHotel), 'equipamento' => $hist($notasAv)];
    }

    /**
     * Composição do custo. Inclui `outros` — sem ele as barras não somavam
     * 100 % no sistema antigo, embora a parcela entrasse no total.
     */
    private function breakdown(array $custos, float $totalGeral): array
    {
        $soma = [];
        foreach ($custos as $c) {
            foreach ($c['parcelas'] as $k => $v) {
                $soma[$k] = ($soma[$k] ?? 0) + $v;
            }
        }

        $out = [];
        foreach ($soma as $k => $v) {
            $out[] = [
                'chave' => $k,
                'valor' => round($v, 2),
                'pct'   => $totalGeral > 0 ? round($v / $totalGeral * 100, 1) : 0.0,
            ];
        }
        usort($out, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
        return $out;
    }

    /**
     * Quatro tempos médios, em dias.
     *
     * Registro sem uma das datas fica de fora — não conta como zero, que
     * puxaria a média para baixo. Valores negativos (a OC aberta antes da
     * assinatura, por exemplo) são mantidos e contados à parte: o original
     * descartava os negativos em duas das quatro métricas e os aceitava nas
     * outras duas, sem critério. Esconder o negativo esconde justamente o
     * processo fora de ordem, que é o que vale investigar.
     */
    private function metricasDeTempo(array $hoteis): array
    {
        $pares = [
            'contratoOc' => ['Contrato → OC',   'dataAss_hotel', 'dataOc_hotel'],
            'eventoNf'   => ['Evento → NF',     'data_hotel',    'dataNf_hotel'],
            'contratoNf' => ['Contrato → NF',   'dataAss_hotel', 'dataNf_hotel'],
            'ocNf'       => ['OC → NF',         'dataOc_hotel',  'dataNf_hotel'],
        ];

        $out = [];
        foreach ($pares as $chave => [$rotulo, $de, $ate]) {
            $dias = [];
            foreach ($hoteis as $h) {
                if (!$h[$de] || !$h[$ate]) {
                    continue;
                }
                $dias[] = (int)round((strtotime($h[$ate]) - strtotime($h[$de])) / 86400);
            }

            $negativos = count(array_filter($dias, static fn(int $d): bool => $d < 0));

            $out[$chave] = [
                'rotulo'    => $rotulo,
                'n'         => count($dias),
                'media'     => $dias ? (int)round(array_sum($dias) / count($dias)) : null,
                'min'       => $dias ? min($dias) : null,
                'max'       => $dias ? max($dias) : null,
                'negativos' => $negativos,
            ];
        }

        return $out;
    }

    private function projecao(array $hoteis, float $media): array
    {
        $hoje    = date('Y-m-d');
        $futuros = 0;
        $semNf   = 0;

        foreach ($hoteis as $h) {
            if ($h['data_hotel'] && $h['data_hotel'] >= $hoje) {
                $futuros++;
            }
            if (!$h['nf_hotel']) {
                $semNf++;
            }
        }

        return [
            'futuros'   => $futuros,
            'media'     => round($media, 2),
            'projetado' => round($futuros * $media, 2),
            'semNf'     => $semNf,
            'comNf'     => count($hoteis) - $semNf,
        ];
    }

    // =====================================================================
    // Calendário
    // =====================================================================

    /**
     * Feriados nacionais de um ano, calculados.
     *
     * No sistema antigo eram quinze datas escritas à mão, só de 2026 — em
     * 2027 o calendário simplesmente deixaria de mostrar feriado. Os móveis
     * derivam todos da Páscoa, então basta calculá-la.
     *
     * @return array<string,string> 'YYYY-MM-DD' => nome
     */
    public function feriados(int $ano): array
    {
        $pascoa = $this->pascoa($ano);
        $desloca = static fn(int $dias): string =>
            date('Y-m-d', strtotime($pascoa . ' ' . ($dias >= 0 ? '+' : '') . $dias . ' days'));

        $lista = [
            "{$ano}-01-01" => 'Confraternização Universal',
            "{$ano}-04-21" => 'Tiradentes',
            "{$ano}-05-01" => 'Dia do Trabalho',
            "{$ano}-09-07" => 'Independência do Brasil',
            "{$ano}-10-12" => 'Nossa Senhora Aparecida',
            "{$ano}-11-02" => 'Finados',
            "{$ano}-11-15" => 'Proclamação da República',
            "{$ano}-11-20" => 'Consciência Negra',
            "{$ano}-12-25" => 'Natal',

            $desloca(-48) => 'Carnaval (segunda)',
            $desloca(-47) => 'Carnaval',
            $desloca(-46) => 'Quarta-feira de Cinzas',
            $desloca(-2)  => 'Sexta-feira Santa',
            $pascoa       => 'Páscoa',
            $desloca(60)  => 'Corpus Christi',
        ];

        ksort($lista);
        return $lista;
    }

    /**
     * Domingo de Páscoa pelo algoritmo de Meeus/Jones/Butcher (gregoriano).
     * É a âncora de Carnaval, Sexta-feira Santa e Corpus Christi.
     */
    private function pascoa(int $ano): string
    {
        $a = $ano % 19;
        $b = intdiv($ano, 100);
        $c = $ano % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);

        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
    }

    /**
     * Um mês do calendário: eventos e feriados nacionais por dia.
     *
     * @return array{ano:int,mes:int,dias:array,eventos:int}
     */
    public function calendario(int $ano, int $mes): array
    {
        $prefixo = sprintf('%04d-%02d', $ano, $mes);

        $porDia = [];
        foreach ($this->todos('hotel') as $h) {
            $d = $h['data_hotel'];
            if (!$d || !str_starts_with($d, $prefixo) || $h['status_hotel'] === 'cancelado') {
                continue;
            }
            $porDia[$d]['eventos'][] = [
                'id'     => (int)$h['id_hotel'],
                'cidade' => $h['cidade_hotel'] !== '' ? $h['cidade_hotel'] : $h['nome_hotel'],
                'nome'   => $h['nome_hotel'],
                'status' => $h['status_hotel'],
            ];
        }

        foreach ($this->feriados($ano) as $d => $nome) {
            if (str_starts_with($d, $prefixo)) {
                $porDia[$d]['feriados'][] = ['nome' => $nome, 'nacional' => true, 'id' => null];
            }
        }

        ksort($porDia);

        $totalEventos = 0;
        foreach ($porDia as $d => $info) {
            $totalEventos += count($info['eventos'] ?? []);
        }

        return ['ano' => $ano, 'mes' => $mes, 'dias' => $porDia, 'eventos' => $totalEventos];
    }

    // =====================================================================

    /** Minúsculas e sem acento, para comparação de texto tolerante. */
    private static function dobrar(string $v): string
    {
        return strtr(mb_strtolower(trim($v), 'UTF-8'), [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);
    }

    private function dataBr(?string $d): string
    {
        if (!$d || strlen($d) < 10) {
            return '—';
        }
        return substr($d, 8, 2) . '/' . substr($d, 5, 2) . '/' . substr($d, 0, 4);
    }
}
