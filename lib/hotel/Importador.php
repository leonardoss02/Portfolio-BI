<?php

require_once __DIR__ . '/Repositorio.php';

/**
 * Carga única dos dados do sistema antigo (Google Apps Script + Sheets).
 *
 * O problema central da migração é que lá não existe entidade de evento: o
 * vínculo entre um custo e o seu evento é a string "Slug|yyyy-mm-dd", montada
 * a partir de um dicionário fixo de cidades. Pior, ao escolher o evento no
 * formulário o sistema gravava o SLUG no campo cidade do filho ("RioDeJaneiro")
 * enquanto o hotel guardava o nome real ("Rio de Janeiro").
 *
 * Aqui essa chave é remontada, resolvida para a FK numérica idHotel_* e
 * descartada. Nada que não resolva é jogado fora em silêncio: o que sobra vai
 * para o relatório como órfão, com a razão.
 *
 * Idempotente: os ids de origem são preservados, então rodar duas vezes
 * produz exatamente o mesmo resultado.
 */
final class HotelImportador
{
    private const URL_API = 'https://script.google.com/macros/s/ID_DO_APPS_SCRIPT/exec';

    private const MESES = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    private array $mapaCidades;

    /** Janela, em dias, do último recurso de vínculo por proximidade de data. */
    private const JANELA_PROXIMIDADE = 10;

    /** chaveLegado => [idHotel, ...] */
    private array $porChave = [];
    /** slugCidade|data => [idHotel, ...] */
    private array $porCidadeData = [];
    /** slugCidade|YYYY-MM => [idHotel, ...] */
    private array $porCidadeMes = [];
    /** cidadeNormalizada => [[idHotel, data], ...] — independente do mapa legado */
    private array $porCidadeNorm = [];
    /** idHotel => registro do hotel */
    private array $hoteis = [];

    private array $relatorio = [];

    public function __construct(private HotelRepositorio $repo)
    {
        $this->mapaCidades = require __DIR__ . '/cidades-legado.php';
    }

    // =====================================================================
    // Origem dos dados
    // =====================================================================

    /** Lê o dump de um arquivo local já baixado. */
    public static function deArquivo(string $caminho): array
    {
        if (!is_file($caminho)) {
            throw new RuntimeException("Arquivo de origem não encontrado: {$caminho}");
        }
        $dados = json_decode((string)file_get_contents($caminho), true);
        if (!is_array($dados)) {
            throw new RuntimeException("Origem não é um JSON válido: {$caminho}");
        }
        return $dados;
    }

    /**
     * Busca direto na API do Apps Script. O endpoint responde 302 para
     * script.googleusercontent.com; o curl segue o redirecionamento
     * convertendo em GET, que é justamente o que devolve o corpo.
     */
    public static function daApi(?string $url = null): array
    {
        $ch = curl_init($url ?: self::URL_API);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['action' => 'getAllData']),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 120,
        ]);

        $corpo = curl_exec($ch);
        $erro  = curl_error($ch);
        $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($corpo === false || $erro !== '') {
            throw new RuntimeException("Falha ao consultar a API: {$erro}");
        }

        $dados = json_decode((string)$corpo, true);
        if (!is_array($dados)) {
            throw new RuntimeException("A API respondeu HTTP {$http} com algo que não é JSON.");
        }

        return $dados;
    }

    // =====================================================================
    // Chave sintética do sistema antigo
    // =====================================================================

    /**
     * Reproduz getEventoId() do Apps Script. O fallback do original só troca
     * acentos MINÚSCULOS, então nomes com maiúscula acentuada saem do mapa
     * com o acento — comportamento reproduzido de propósito, senão as chaves
     * gravadas na planilha não seriam reencontradas.
     */
    public function slug(string $cidade): string
    {
        if (isset($this->mapaCidades[$cidade])) {
            return $this->mapaCidades[$cidade];
        }

        $s = preg_replace('/\s+/u', '', $cidade) ?? $cidade;
        return strtr($s, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
    }

    private function chave(string $cidade, ?string $data): string
    {
        return $this->slug($cidade) . '|' . (string)$data;
    }

    /**
     * Normalização neutra do nome da cidade, sem passar pelo mapa legado.
     *
     * É o que permite reencontrar os casos em que o mapa atrapalha: ele manda
     * 'São Carlos' para o slug 'RibeiraoPreto', então um custo gravado como
     * 'SaoCarlos' nunca casa com o seu hotel, mesmo com a data idêntica. Aqui
     * os dois lados viram 'saocarlos'.
     */
    private function cidadeNorm(string $cidade): string
    {
        $s = mb_strtolower(trim($cidade), 'UTF-8');
        $s = strtr($s, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        return preg_replace('/[^a-z0-9]/', '', $s) ?? $s;
    }

    // =====================================================================
    // Importação
    // =====================================================================

    public function importar(array $fonte): array
    {
        $this->relatorio = ['entidades' => [], 'avisos' => [], 'orfaos' => []];

        $this->importarHoteis($fonte['hotels'] ?? []);
        $this->importarEquipamentos($fonte['hotels'] ?? []);
        $this->importarFilhos('videomaker', $fonte['videomakers'] ?? []);
        $this->importarFilhos('transporte', $fonte['voos'] ?? []);
        $this->importarFilhos('frete', $fonte['freteG4'] ?? []);
        $this->importarFilhos('material', $fonte['materiais'] ?? []);

        return $this->relatorio;
    }

    // ---------------------------------------------------------------------

    private function importarHoteis(array $linhas): void
    {
        $registros = [];
        $idsVistos = [];
        $total     = 0.0;

        foreach ($linhas as $h) {
            $idLegado = (int)($h['id'] ?? 0);
            $cidade   = trim((string)($h['cidade'] ?? ''));
            $data     = HotelSchema::parseData($h['data'] ?? null);

            if (isset($idsVistos[$idLegado]) || $idLegado <= 0) {
                $this->aviso("hotel id={$idLegado} duplicado ou inválido na origem; recebeu id novo.");
                $idLegado = max(array_keys($idsVistos) ?: [0]) + 1;
            }
            $idsVistos[$idLegado] = true;

            $reg = HotelSchema::normalizar('hotel', [
                'id_hotel'          => $idLegado,
                'nome'              => $h['nome'] ?? '',
                'cidade'            => $cidade,
                'uf'                => $h['uf'] ?? '',
                'endereco'          => $h['end'] ?? '',
                'capacidade'        => $h['cap'] ?? null,
                'data'              => $data,
                'valor'             => $h['valor'] ?? 0,
                'tipoPag'           => $h['tipopag'] ?? '',
                'rating'            => $h['rating'] ?? 0,
                'status'            => $h['status'] ?? 'negociacao',
                'dataAss'           => $h['dataAss'] ?? null,
                'nf'                => $h['nf'] ?? 0,
                'dataNf'            => $h['dataNf'] ?? null,
                'oc'                => $h['oc'] ?? 0,
                'dataOc'            => $h['dataOc'] ?? null,
                'ocNum'             => $h['ocNum'] ?? '',
                'quartosReserva'    => $h['quartosReserva'] ?? '',
                'quartosConfirmado' => $h['quartosConfirmado'] ?? 0,
                'entregaStatus'     => $h['entregaStatus'] ?? '',
                'entregaData'       => $h['entregaData'] ?? null,
                'entregaMateriais'  => $h['entregaMateriais'] ?? '',
                'entregaObs'        => $h['entregaObs'] ?? '',
                'responsavelAv'     => ($h['equip'] ?? 'hotel') === 'terceirizado' ? 'terceirizado' : 'hotel',
                'obs'               => $h['obs'] ?? '',
                'idLegado'          => 'hotel:' . $idLegado,
            ]);

            $registros[] = $reg;
            $total      += (float)$reg['valor_hotel'];

            // Índices de resolução para os filhos.
            $this->hoteis[$idLegado] = $reg;
            if ($cidade !== '') {
                $this->porChave[$this->chave($cidade, $data)][] = $idLegado;
                if ($data) {
                    $this->porCidadeData[$this->slug($cidade) . '|' . $data][] = $idLegado;
                    $this->porCidadeMes[$this->slug($cidade) . '|' . substr($data, 0, 7)][] = $idLegado;

                    // Indexa sob o nome real E sob o slug legado: o mapa
                    // abrevia vários nomes ('Balneário Camboriú' vira
                    // 'Balneario'), e os custos criados pelo <select> gravaram
                    // justamente o slug no campo cidade. Sem as duas entradas
                    // os dois lados nunca se encontram.
                    foreach (array_unique([$this->cidadeNorm($cidade), $this->cidadeNorm($this->slug($cidade))]) as $k) {
                        $this->porCidadeNorm[$k][] = [$idLegado, $data];
                    }
                }
            }
        }

        foreach ($this->porChave as $chave => $ids) {
            if (count($ids) > 1) {
                $this->aviso("Chave de evento '{$chave}' aponta para " . count($ids) . ' hotéis (ids ' . implode(', ', $ids) . '); custos ligados a ela vão para o primeiro.');
            }
        }

        $this->repo->substituirTudo('hotel', $registros);
        $this->relatorio['entidades']['hotel'] = [
            'origem'     => count($linhas),
            'importados' => count($registros),
            'valor'      => round($total, 2),
        ];
    }

    // ---------------------------------------------------------------------

    /**
     * O A/V era um bloco de colunas dentro do hotel; vira registro próprio.
     * Só gera equipamento quando há fornecedor de verdade — hotel marcado
     * como terceirizado e sem fornecedor é pendência real, e aparece assim
     * no checklist em vez de virar um registro vazio.
     */
    private function importarEquipamentos(array $linhas): void
    {
        $registros = [];
        $id        = 1;
        $total     = 0.0;
        $semForn   = 0;

        foreach ($linhas as $h) {
            if (($h['equip'] ?? '') !== 'terceirizado') {
                continue;
            }

            $idHotel = (int)($h['id'] ?? 0);
            $nome    = $this->limparLixoSheets((string)($h['fNome'] ?? ''), 'nome');

            if ($nome === '') {
                $semForn++;
                continue;
            }

            $reg = HotelSchema::normalizar('equipamento', [
                'id_equipamento' => $id++,
                'idHotel'        => $idHotel,
                'fornecedor'     => $nome,
                'contato'        => $this->limparLixoSheets((string)($h['fContato'] ?? ''), 'contato'),
                'valor'          => $this->limparLixoSheets((string)($h['fValor'] ?? ''), 'valor'),
                'status'         => $h['fStatus'] ?? 'aguardando',
                'oc'             => $h['fOc'] ?? 0,
                'ocNum'          => $h['fOcNum'] ?? '',
                'dataOc'         => $h['fDataOc'] ?? null,
                'rating'         => $h['fRating'] ?? 0,
                'entrega'        => $h['avEntrega'] ?? '',
                'hora'           => $h['avHora'] ?? '',
                'obs'            => $h['fObs'] ?? '',
                'idLegado'       => 'equip:' . $idHotel,
            ]);

            $registros[] = $reg;
            $total      += (float)$reg['valor_equipamento'];
        }

        if ($semForn > 0) {
            $this->aviso("{$semForn} hotéis marcados como A/V terceirizado não têm fornecedor informado; ficam sem registro de equipamento (vira pendência no checklist).");
        }

        $this->repo->substituirTudo('equipamento', $registros);
        $this->relatorio['entidades']['equipamento'] = [
            'origem'     => count($registros) + $semForn,
            'importados' => count($registros),
            'valor'      => round($total, 2),
        ];
    }

    /**
     * Lixo conhecido de célula do Sheets com fórmula quebrada — o próprio
     * sistema antigo já mascarava isto na exibição (sanitizeHotelDisplay).
     * Aqui a limpeza é definitiva, na gravação.
     */
    private function limparLixoSheets(string $v, string $campo): string
    {
        return match (true) {
            $campo === 'contato' && str_contains($v, '#ERROR') => '',
            $campo === 'valor'   && str_contains($v, 'GMT')    => '',
            $campo === 'nome'    && trim($v) === '1'           => '',
            default                                            => $v,
        };
    }

    // ---------------------------------------------------------------------

    /** Mapa entidade => como ler cada campo da coleção de origem. */
    private const DE_PARA = [
        'videomaker' => ['origem' => 'videomakers', 'campos' => [
            'nome' => 'fornec', 'contato' => 'contato', 'cidade' => 'cidade',
            'data' => 'data', 'mes' => 'mes', 'valor' => 'valor',
            'status' => 'status', 'oc' => 'oc', 'tipo' => 'tipo', 'obs' => 'obs',
        ]],
        'transporte' => ['origem' => 'voos', 'campos' => [
            'cidade' => 'cidade', 'dataEvento' => 'data', 'tipo' => 'tipoTransp',
            'cia' => 'cia', 'trecho' => 'trecho', 'dataViagem' => 'dataTransp',
            'qtd' => 'qtd', 'valor' => 'valor', 'status' => 'status',
            'localizador' => 'loc', 'passageiro' => 'pass', 'obs' => 'obs',
        ]],
        'frete' => ['origem' => 'freteG4', 'campos' => [
            'cidade' => 'cidade', 'dataEvento' => 'data', 'doc' => 'doc',
            'dataEnvio' => 'dataFrete', 'dataEntrega' => 'dataEntrega',
            'valor' => 'valor', 'status' => 'status', 'obs' => 'obs',
        ]],
        'material' => ['origem' => 'materiais', 'campos' => [
            'cidade' => 'cidade', 'dataEvento' => 'data', 'qtde' => 'qtde',
            'valor' => 'valor', 'status' => 'status', 'obs' => 'obs',
        ]],
    ];

    private function importarFilhos(string $ent, array $linhas): void
    {
        $mapa      = self::DE_PARA[$ent]['campos'];
        $registros = [];
        $total     = 0.0;
        $porComo   = ['chave' => 0, 'cidadeData' => 0, 'cidadeNorm' => 0, 'mes' => 0, 'proximidade' => 0, 'avulso' => 0, 'semEvento' => 0, 'orfao' => 0];

        foreach ($linhas as $i => $o) {
            $entrada = ['id_' . $ent => (int)($o['id'] ?? ($i + 1)), 'idLegado' => $ent . ':' . ($o['id'] ?? $i)];
            foreach ($mapa as $destino => $origem) {
                $entrada[$destino] = $o[$origem] ?? null;
            }

            $eventoId = trim((string)($o['eventoId'] ?? ''));
            $cidade   = trim((string)($o['cidade'] ?? ''));
            $data     = HotelSchema::parseData($o['data'] ?? null);

            // Material sem evento: o antigo marcava com o eventoId mágico 'geral'.
            if ($ent === 'material' && ($eventoId === '' || $eventoId === 'geral')) {
                $entrada['idHotel'] = null;
                $entrada['avulso']  = 1;
                $porComo['avulso']++;
            } elseif ($eventoId === '' && !$data) {
                // Nem chave nem data: não há o que resolver. É um cadastro que
                // nunca foi ligado a evento nenhum — situação legítima, não
                // falha de vínculo, e por isso fora da lista de órfãos.
                $entrada['idHotel'] = null;
                $porComo['semEvento']++;
            } else {
                [$idHotel, $como] = $this->resolverHotel($eventoId, $cidade, $data, (string)($o['mes'] ?? ''), $ent, $o['id'] ?? null);
                $entrada['idHotel'] = $idHotel;
                $porComo[$como]++;

                if ($idHotel !== null) {
                    // Normaliza cidade e data pelo hotel: no original o filho
                    // guardava o SLUG ('RioDeJaneiro') quando vinha do <select>.
                    $h = $this->hoteis[$idHotel];
                    if (isset($mapa['cidade'])) {
                        $entrada['cidade'] = $h['cidade_hotel'];
                    }
                    if (isset($mapa['dataEvento'])) {
                        $entrada['dataEvento'] = $h['data_hotel'];
                    }
                } else {
                    $this->relatorio['orfaos'][] = [
                        'entidade' => $ent,
                        'id'       => $o['id'] ?? null,
                        'cidade'   => $cidade,
                        'data'     => $data,
                        'eventoId' => $eventoId,
                        'valor'    => HotelSchema::parseMoeda($o['valor'] ?? 0),
                    ];
                }
            }

            $reg        = HotelSchema::normalizar($ent, $entrada);
            $registros[] = $reg;
            $total      += (float)$reg[HotelSchema::campo($ent, 'valor')];
        }

        $this->repo->substituirTudo($ent, $registros);
        $this->relatorio['entidades'][$ent] = [
            'origem'     => count($linhas),
            'importados' => count($registros),
            'valor'      => round($total, 2),
            'vinculo'    => $porComo,
        ];
    }

    /**
     * Cadeia de tentativas, da mais confiável para a menos. O sistema antigo
     * aplicava as suas três estratégias a CADA cálculo, o que fazia um
     * videomaker de janeiro ser somado em todos os eventos daquela cidade
     * naquele mês. Aqui a escolha acontece uma única vez, na carga, e cada
     * recurso menos direto entra no relatório para conferência humana.
     *
     * @return array{0:?int,1:string} [idHotel, como]
     */
    private function resolverHotel(string $eventoId, string $cidade, ?string $data, string $mes, string $ent, mixed $idOrigem = null): array
    {
        // 1) A chave sintética gravada pelo próprio sistema antigo.
        if ($eventoId !== '' && isset($this->porChave[$eventoId])) {
            return [$this->porChave[$eventoId][0], 'chave'];
        }

        // 2) Cidade (pelo slug legado) + data exata.
        if ($cidade !== '' && $data) {
            $k = $this->slug($cidade) . '|' . $data;
            if (isset($this->porCidadeData[$k])) {
                return [$this->porCidadeData[$k][0], 'cidadeData'];
            }
        }

        // 3) Cidade normalizada + data exata. Recupera o que o mapa legado
        //    perde por colisão de slug (o caso São Carlos/Ribeirão Preto).
        if ($cidade !== '' && $data) {
            $candidatos = $this->porCidadeNorm[$this->cidadeNorm($cidade)] ?? [];
            foreach ($candidatos as [$idHotel, $dataHotel]) {
                if ($dataHotel === $data) {
                    return [$idHotel, 'cidadeNorm'];
                }
            }
        }

        // 4) Videomaker costuma ter só o mês, sem o dia.
        if ($ent === 'videomaker' && $cidade !== '' && $mes !== '') {
            $indice = array_search($mes, self::MESES, true);
            if ($indice !== false) {
                $prefixo = $this->slug($cidade) . '|';
                foreach ($this->porCidadeMes as $k => $ids) {
                    if (str_starts_with($k, $prefixo) && (int)substr($k, -2) === $indice + 1) {
                        if (count($ids) > 1) {
                            $this->aviso("videomaker em {$cidade}/{$mes} casa com " . count($ids) . ' eventos; ficou no primeiro (id ' . $ids[0] . ').');
                        }
                        return [$ids[0], 'mes'];
                    }
                }
            }
        }

        // 5) Último recurso: mesma cidade e um único evento dentro da janela.
        //    Cobre os custos que ficaram para trás quando a data do evento foi
        //    alterada no hotel — com chave natural mutável, remarcar um evento
        //    desligava silenciosamente todos os seus custos. Só resolve quando
        //    há exatamente um candidato, e cada caso é relatado.
        if ($cidade !== '' && $data) {
            $proximos = [];
            foreach ($this->porCidadeNorm[$this->cidadeNorm($cidade)] ?? [] as [$idHotel, $dataHotel]) {
                $dias = (int)round((strtotime($dataHotel) - strtotime($data)) / 86400);
                if (abs($dias) <= self::JANELA_PROXIMIDADE) {
                    $proximos[] = [$idHotel, $dias, $dataHotel];
                }
            }

            if (count($proximos) === 1) {
                [$idHotel, $dias, $dataHotel] = $proximos[0];
                $this->aviso(sprintf(
                    '%s id=%s (%s, %s) ligado ao hotel %d por proximidade: o evento está em %s, %+d dia(s). Confira.',
                    $ent,
                    (string)($idOrigem ?? '?'),
                    $cidade,
                    $data,
                    $idHotel,
                    $dataHotel,
                    $dias
                ));
                return [$idHotel, 'proximidade'];
            }

            if (count($proximos) > 1) {
                $this->aviso(sprintf(
                    '%s id=%s (%s, %s) tem %d eventos dentro de %d dias; ficou sem vínculo para não chutar.',
                    $ent,
                    (string)($idOrigem ?? '?'),
                    $cidade,
                    $data,
                    count($proximos),
                    self::JANELA_PROXIMIDADE
                ));
            }
        }

        return [null, 'orfao'];
    }

    // ---------------------------------------------------------------------


    private function aviso(string $msg): void
    {
        $this->relatorio['avisos'][] = $msg;
    }
}
