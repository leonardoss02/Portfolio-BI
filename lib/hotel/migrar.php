<?php

/**
 * Migra os lançamentos do arquivo JSON para a tabela `fornecedorEvento`.
 *
 *   php lib/hotel/migrar.php --simular              # relatório, sem gravar
 *   php lib/hotel/migrar.php --destino=DEFAULT      # grava em produção
 *   php lib/hotel/migrar.php --destino=2            # grava no banco de dev
 *
 * `--destino` não tem padrão de propósito. Já teve — apontava para dev —, e
 * um migrador que grava em algum lugar sem que ninguém tenha dito qual é o
 * tipo de ferramenta que enche o banco errado numa tarde distraída.
 *
 * A conciliação com o CRM roda contra a conexão padrão (produção), porque é
 * ela que tem a base de eventos completa. Os ids de `evento` são idênticos
 * nos dois ambientes — dev é um retrato mais antigo —, então o id resolvido
 * aqui continua válido quando o sistema for para produção.
 *
 * Cada hotel precisa de um evento no CRM. Os que não tiverem ficam de fora,
 * junto com os seus custos, e aparecem no relatório com o motivo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este script só roda por linha de comando.\n");
}

require_once __DIR__ . '/RepoJson.php';
require_once __DIR__ . '/RepoMysql.php';
require_once __DIR__ . '/Calculo.php';

$opts     = getopt('', ['simular', 'destino::', 'eventos::']);
$simular  = array_key_exists('simular', $opts);
$destino  = $opts['destino'] ?? null;
$conexEv  = $opts['eventos'] ?? 'api';

if (!$simular && $destino === null) {
    fwrite(STDERR, "Informe onde gravar: --destino=DEFAULT (produção) ou --destino=2 (dev).
"
                 . "Para só ver o relatório, use --simular.
");
    exit(1);
}

$brl = static fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');

// ── Conciliação ──────────────────────────────────────────────────────────

/** Minúsculas, sem acento e sem pontuação — para comparar nomes de cidade. */
function dobrar(?string $v): string
{
    $s = html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
    ]);
    return preg_replace('/[^a-z0-9]/', '', $s) ?? $s;
}

function diasEntre(string $a, string $b): int
{
    return (int)abs(round((strtotime($a) - strtotime($b)) / 86400));
}

/**
 * Encontra o evento do CRM correspondente a um hotel, em três tentativas.
 *
 * A segunda existe porque `evento.local_evento` guarda o nome do hotel — foi
 * ela que recuperou Florianópolis (evento em 01/04, hotel em 05/04), Ribeirão
 * Preto (14/04 x 15/04) e Natal (23/09 x 21/09): mesmos hotéis, datas
 * ligeiramente diferentes entre os dois sistemas.
 *
 * @return array{0:?array,1:string}
 */
function casarEvento(array $hotel, array $eventos): array
{
    $cidade = dobrar($hotel['cidade_hotel']);
    $nome   = dobrar($hotel['nome_hotel']);
    $data   = $hotel['data_hotel'];

    if (!$data) {
        return [null, 'hotel sem data'];
    }

    foreach ($eventos as $e) {
        if (dobrar($e['cidade_evento']) === $cidade && $e['d'] === $data) {
            return [$e, 'cidade+data'];
        }
    }

    if ($nome !== '') {
        $cand = array_values(array_filter($eventos, static function (array $e) use ($nome, $data): bool {
            $local = dobrar($e['local_evento']);
            if ($local === '' || diasEntre($e['d'], $data) > 10) {
                return false;
            }
            $p = substr($nome, 0, 18);
            $q = substr($local, 0, 18);
            return str_starts_with($local, $p) || str_starts_with($nome, $q);
        }));
        if (count($cand) === 1) {
            $delta = (int)round((strtotime($cand[0]['d']) - strtotime($data)) / 86400);
            return [$cand[0], sprintf('nome do hotel (%+d dias)', $delta)];
        }
    }

    $cand = array_values(array_filter(
        $eventos,
        static fn(array $e): bool => dobrar($e['cidade_evento']) === $cidade && diasEntre($e['d'], $data) <= 10
    ));
    if (count($cand) === 1) {
        $delta = (int)round((strtotime($cand[0]['d']) - strtotime($data)) / 86400);
        return [$cand[0], sprintf('cidade + data próxima (%+d dias)', $delta)];
    }

    return [null, 'sem evento no CRM'];
}

const SQL_EVENTOS = "SELECT id_evento, cidade_evento, local_evento, DATE(data_evento) AS d
                       FROM evento WHERE deletado_evento = 0";

/**
 * Lista de eventos do CRM.
 *
 * `--eventos=api` busca em produção pela rota /sql/query, e é o modo correto
 * para a carga inicial: a base de dev é um retrato de maio/2026, e os hotéis
 * vão até outubro — conciliar contra ela deixaria 36 dos 71 sem evento.
 * Os ids de `evento` são os mesmos nos dois ambientes, então o id resolvido
 * contra produção continua valendo quando o sistema subir.
 *
 * A resposta fica em cache no arquivo: a conciliação é rodada várias vezes até
 * fechar, e não há motivo para bater em produção a cada tentativa.
 */
function carregarEventos(string $fonte): array
{
    if ($fonte !== 'api') {
        return HotelRepoMysql::conectar($fonte)->query(SQL_EVENTOS)->fetch_all(MYSQLI_ASSOC);
    }

    $cache = __DIR__ . '/../../.mrphp/hotel/eventos-crm.json';
    if (is_file($cache) && filemtime($cache) > time() - 3600) {
        return json_decode((string)file_get_contents($cache), true) ?: [];
    }

    $token = (string)HotelRepo::lerEnv('SQL_API_TOKEN');
    if ($token === '') {
        throw new RuntimeException('SQL_API_TOKEN não configurado no .env.');
    }

    $ch = curl_init('https://bi.exemplo.com.br/sql/query');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['sql' => SQL_EVENTOS]),
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $corpo  = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($corpo === false || $status !== 200) {
        throw new RuntimeException("API SQL respondeu {$status}.");
    }

    $json = json_decode((string)$corpo, true);
    if (!isset($json['resultado'])) {
        throw new RuntimeException('Resposta inesperada da API SQL: ' . substr((string)$corpo, 0, 200));
    }

    @mkdir(dirname($cache), 0775, true);
    file_put_contents($cache, json_encode($json['resultado']));
    return $json['resultado'];
}
// ── Origem e destino ─────────────────────────────────────────────────────

try {
    $origem = new HotelRepoJson();
    $calc   = new HotelCalculo($origem);

    $eventos = carregarEventos($conexEv);

    $dbDestino = $simular ? null : HotelRepoMysql::conectar($destino);
    $repo      = $dbDestino ? new HotelRepoMysql($dbDestino) : null;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO ao conectar: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\n== Migração para fornecedorEvento ==\n";
echo 'Origem  : arquivo em ' . $origem->diretorio() . "\n";
echo 'Eventos : conexão ' . $conexEv . ' (' . count($eventos) . " eventos no CRM)\n";
echo 'Destino : ' . ($simular ? 'SIMULAÇÃO — nada será gravado' : 'conexão ' . $destino) . "\n\n";

// ── Passo 1: hotéis ──────────────────────────────────────────────────────

$mapaHotel = [];      // id do hotel no JSON => id da linha gravada
$porComo   = [];
$semEvento = [];
$gravados  = ['hotel' => 0];
$valores   = ['hotel' => 0.0];

foreach ($calc->hoteis() as $h) {
    [$evento, $como] = casarEvento($h, $eventos);
    $chave = explode(' (', $como)[0];
    $porComo[$chave] = ($porComo[$chave] ?? 0) + 1;

    if (!$evento) {
        $semEvento[] = ['hotel' => $h, 'motivo' => $como, 'custo' => $calc->custoDoHotel($h)['total']];
        continue;
    }

    $reg = $h;
    $reg['idEventoCrm_hotel'] = (int)$evento['id_evento'];
    $reg['id_hotel'] = null;      // id novo, atribuído pela tabela

    if ($repo) {
        $salvo = $repo->salvar('hotel', $reg);
        $mapaHotel[(int)$h['id_hotel']] = (int)$salvo['id_hotel'];
    } else {
        $mapaHotel[(int)$h['id_hotel']] = -1;
    }

    $gravados['hotel']++;
    $valores['hotel'] += (float)$h['valor_hotel'];
}

// ── Passo 2: os custos de cada hotel ─────────────────────────────────────

$orfaos = [];

foreach (MapaFornecedor::filhos() as $ent) {
    $gravados[$ent] = 0;
    $valores[$ent]  = 0.0;
    $colHotel = HotelSchema::campo($ent, 'idHotel');
    $colValor = HotelSchema::campo($ent, 'valor');

    foreach ($origem->todos($ent) as $r) {
        $idJson = $r[$colHotel] ?? null;

        // Avulso: sem evento por definição, e a tabela aceita.
        if ($idJson === null) {
            if ($repo) {
                $reg = $r;
                $reg[HotelSchema::pk($ent)] = null;
                $reg[$colHotel] = null;
                $repo->salvar($ent, $reg);
            }
            $gravados[$ent]++;
            $valores[$ent] += (float)$r[$colValor];
            continue;
        }

        if (!isset($mapaHotel[(int)$idJson])) {
            $orfaos[] = ['ent' => $ent, 'id' => $r[HotelSchema::pk($ent)], 'valor' => (float)$r[$colValor]];
            continue;
        }

        if ($repo) {
            $reg = $r;
            $reg[HotelSchema::pk($ent)] = null;
            $reg[$colHotel] = $mapaHotel[(int)$idJson];
            $repo->salvar($ent, $reg);
        }

        $gravados[$ent]++;
        $valores[$ent] += (float)$r[$colValor];
    }
}

// ── Relatório ────────────────────────────────────────────────────────────

echo "COMO OS HOTÉIS CASARAM COM O CRM\n";
foreach ($porComo as $como => $n) {
    printf("  %-28s %d\n", $como, $n);
}

echo "\nGRAVADO\n";
printf("  %-14s %8s %16s\n", 'CATEGORIA', 'LINHAS', 'VALOR');
echo '  ', str_repeat('-', 40), "\n";
$totLinhas = 0;
$totValor  = 0.0;
foreach ($gravados as $ent => $n) {
    printf("  %-14s %8d %16s\n", $ent, $n, $brl($valores[$ent]));
    $totLinhas += $n;
    $totValor  += $valores[$ent];
}
echo '  ', str_repeat('-', 40), "\n";
printf("  %-14s %8d %16s\n", 'TOTAL', $totLinhas, $brl($totValor));

if ($semEvento) {
    $soma = array_sum(array_column($semEvento, 'custo'));
    echo "\nFORA DA MIGRAÇÃO — hotéis sem evento no CRM (" . count($semEvento) . "):\n";
    foreach ($semEvento as $s) {
        printf("  %-22s %-12s %-14s %s\n",
            mb_substr($s['hotel']['cidade_hotel'], 0, 22),
            $s['hotel']['data_hotel'],
            $s['motivo'],
            $brl($s['custo']));
    }
    printf("  %-50s %s\n", 'custo que fica de fora', $brl($soma));
}

if ($orfaos) {
    $soma = array_sum(array_column($orfaos, 'valor'));
    echo "\nCUSTOS QUE SAÍRAM JUNTO — filhos dos hotéis acima (" . count($orfaos) . "): " . $brl($soma) . "\n";
    foreach (array_slice($orfaos, 0, 10) as $o) {
        printf("  %-12s id=%-5s %s\n", $o['ent'], $o['id'], $brl($o['valor']));
    }
}

echo "\n";
if ($simular) {
    echo "Simulação: nada foi gravado. Rode sem --simular para migrar.\n\n";
}
