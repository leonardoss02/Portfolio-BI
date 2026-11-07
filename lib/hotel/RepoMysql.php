<?php

require_once __DIR__ . '/Repositorio.php';
require_once __DIR__ . '/MapaFornecedor.php';

/**
 * Persistência em MySQL, sobre a tabela `fornecedorEvento`.
 *
 * Implementa a mesma interface de `HotelRepoJson` e devolve os registros no
 * MESMO formato lógico — `nome_videomaker`, `valor_frete`, `idHotel_transporte`.
 * Tudo acima daqui (controller, views, cálculos, JavaScript) continua vendo
 * seis entidades separadas e não sabe que existe uma tabela só.
 *
 * Duas traduções acontecem aqui, e são a razão de esta classe existir:
 *
 * 1. **Categoria.** Cada entidade lógica vira `categoria_fornecedorEvento`.
 *
 * 2. **O vínculo.** A aplicação pendura os filhos no HOTEL (`idHotel_*`); a
 *    tabela os pendura no EVENTO do CRM (`idEvento_fornecedorEvento`). Ao ler,
 *    o `idHotel` é reconstruído como o id da linha de hotel que compartilha o
 *    mesmo evento; ao gravar, o caminho inverso. Sem isso, `Calculo::porHotel()`
 *    e as telas precisariam ser reescritas.
 *
 * Cidade e endereço não são colunas: vêm de `evento` por JOIN, porque são do
 * evento e não do fornecedor.
 */
final class HotelRepoMysql implements HotelRepositorio
{
    private mysqli $db;

    /** Cache por requisição: idEvento => id da linha de hotel. */
    private ?array $hotelPorEvento = null;

    /** Cache por requisição: id da linha de hotel => ['evento','data']. */
    private array $cacheHotel = [];

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? self::conectar();
        $this->db->set_charset('utf8mb4');
    }

    /**
     * Abre conexão a partir do .env. `$conexao` segue o esquema do framework:
     * 'DEFAULT' para a principal, '2' para a de desenvolvimento.
     */
    public static function conectar(string $conexao = 'DEFAULT'): mysqli
    {
        $ler = static fn(string $k): string => (string)HotelRepo::lerEnv("DB_{$conexao}_{$k}");

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = new mysqli($ler('HOST'), $ler('USER'), $ler('PASS'), $ler('NAME'));
        $db->set_charset($ler('CHARSET') ?: 'utf8mb4');
        return $db;
    }

    public function conexao(): mysqli
    {
        return $this->db;
    }

    // =====================================================================
    // Leitura
    // =====================================================================

    public function todos(string $ent, bool $incluirExcluidos = false): array
    {
        if (!MapaFornecedor::suporta($ent)) {
            return [];   // entidade fora desta tabela
        }

        $filtroDel = $incluirExcluidos ? '' : ' AND f.deletado_fornecedorEvento = 0';

        if ($ent === 'hotel') {
            // Cidade, endereço e nome do evento vêm do evento do CRM.
            $sql = "SELECT f.*, e.cidade_evento, e.endereco_evento, e.nome_evento
                    FROM fornecedorEvento f
                    LEFT JOIN evento e ON e.id_evento = f.idEvento_fornecedorEvento
                    WHERE f.categoria_fornecedorEvento = ?{$filtroDel}
                    ORDER BY f.data_fornecedorEvento, f.id_fornecedorEvento";
        } else {
            // O idHotel lógico é o id da linha de hotel do mesmo evento.
            $sql = "SELECT f.*, h.id_fornecedorEvento AS _idHotel, e.cidade_evento, e.data_evento
                    FROM fornecedorEvento f
                    LEFT JOIN fornecedorEvento h
                           ON h.idEvento_fornecedorEvento = f.idEvento_fornecedorEvento
                          AND h.categoria_fornecedorEvento = 'hotel'
                          AND h.deletado_fornecedorEvento = 0
                    LEFT JOIN evento e ON e.id_evento = f.idEvento_fornecedorEvento
                    WHERE f.categoria_fornecedorEvento = ?{$filtroDel}
                    ORDER BY f.data_fornecedorEvento, f.id_fornecedorEvento";
        }

        $stmt = $this->db->prepare($sql);
        $cat  = MapaFornecedor::categoria($ent);
        $stmt->bind_param('s', $cat);
        $stmt->execute();
        $linhas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(fn(array $l): array => $this->paraLogico($ent, $l), $linhas);
    }

    public function buscar(string $ent, int $id, bool $incluirExcluidos = false): ?array
    {
        foreach ($this->todos($ent, $incluirExcluidos) as $r) {
            if ((int)$r[HotelSchema::pk($ent)] === $id) {
                return $r;
            }
        }
        return null;
    }

    public function proximoId(string $ent): int
    {
        $r = $this->db->query('SELECT COALESCE(MAX(id_fornecedorEvento), 0) + 1 AS n FROM fornecedorEvento');
        return (int)$r->fetch_assoc()['n'];
    }

    // =====================================================================
    // Sincronia com custoEvento (CRM)
    // =====================================================================

    /** Desligada durante carga em lote — ver semSincronia(). */
    private bool $sincronia = true;

    /** Avisos da última escrita, quando a sincronia falhou. @var string[] */
    private array $avisosSync = [];

    /** O que a última escrita mudou no CRM. @var string[] */
    private array $mudancasSync = [];

    /**
     * Roda algo sem tocar no `custoEvento`.
     *
     * Existe para carga em lote: uma migração de 400 linhas dispararia 400
     * escritas no CRM, uma por linha, todas menos a última descartáveis.
     */
    public function semSincronia(callable $fn)
    {
        $antes = $this->sincronia;
        $this->sincronia = false;
        try {
            return $fn();
        } finally {
            $this->sincronia = $antes;
        }
    }

    /** @return string[] avisos de falha de sincronia da última escrita */
    public function avisosSync(): array
    {
        return $this->avisosSync;
    }

    /** @return string[] o que a última escrita mudou no CRM */
    public function mudancasSync(): array
    {
        return $this->mudancasSync;
    }

    /** A que evento uma linha pertence, ou 0. Lê direto, sem cache. */
    private function eventoDaLinha(int $id): int
    {
        if ($id <= 0) {
            return 0;
        }
        $stmt = $this->db->prepare(
            'SELECT idEvento_fornecedorEvento FROM fornecedorEvento WHERE id_fornecedorEvento = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_row();
        $stmt->close();

        return (int)($r[0] ?? 0);
    }

    /** SUM(custo) do evento, ignorando o que está deletado. */
    private function totalDoEvento(int $idEvento): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(custo_fornecedorEvento), 0)
               FROM fornecedorEvento
              WHERE idEvento_fornecedorEvento = ? AND deletado_fornecedorEvento = 0'
        );
        $stmt->bind_param('i', $idEvento);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_row();
        $stmt->close();

        return round((float)($r[0] ?? 0), 2);
    }

    /** Nome, identificador e data do evento — só para montar uma linha nova. */
    private function dadosDoEvento(int $idEvento): array
    {
        $stmt = $this->db->prepare(
            'SELECT nome_evento, nomeForm_evento, DATE(data_evento) FROM evento WHERE id_evento = ?'
        );
        $stmt->bind_param('i', $idEvento);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_row();
        $stmt->close();

        return [
            'nome'          => (string)($r[0] ?? ''),
            'identificador' => (string)($r[1] ?? ''),
            'data'          => (string)($r[2] ?? ''),
        ];
    }

    /**
     * Põe o `custoEvento` em dia para os eventos afetados por uma escrita.
     *
     * **Nunca derruba o salvamento.** O usuário salvou um fornecedor; se o CRM
     * recusar a escrita, o trabalho dele não se perde. Mas também não falha em
     * silêncio: o motivo fica em `avisosSync()`, que o controller devolve para
     * a tela — divergência descoberta semanas depois foi o que gerou toda a
     * conciliação de setembro/2026.
     *
     * @param int[] $eventos ids, com repetição e zeros tolerados
     */
    private function sincronizarCusto(array $eventos): void
    {
        $this->avisosSync   = [];
        $this->mudancasSync = [];

        if (!$this->sincronia) {
            return;
        }

        $ids = array_values(array_unique(array_filter($eventos, static fn($e) => (int)$e > 0)));
        if (!$ids) {
            return;   // compra avulsa: não pertence a evento nenhum
        }

        try {
            require_once __DIR__ . '/RepoCustoEvento.php';
            $repo = new RepoCustoEvento($this->db);

            foreach ($ids as $idEvento) {
                $mudou = $repo->sincronizar(
                    (int)$idEvento,
                    $this->totalDoEvento((int)$idEvento),
                    $this->dadosDoEvento((int)$idEvento)
                );
                if ($mudou !== '') {
                    $this->mudancasSync[] = $mudou;
                }
            }
        } catch (Throwable $e) {
            $this->avisosSync[] = 'O custo do CRM não foi atualizado: ' . $e->getMessage();
        }
    }

    // =====================================================================
    // Escrita
    // =====================================================================

    public function salvar(string $ent, array $registro): array
    {
        if (!MapaFornecedor::suporta($ent)) {
            throw new RuntimeException("Entidade não persistida nesta tabela: {$ent}");
        }

        $this->exigirEvento($ent, $registro);

        $fisico = $this->paraFisico($ent, $registro);
        $id     = (int)($registro[HotelSchema::pk($ent)] ?? 0);
        $agora  = date('Y-m-d H:i:s');

        // Evento de antes: se a edição mudar o registro de evento, os DOIS
        // mudam de valor. Sem guardar este, o de origem ficaria com o custo
        // inflado para sempre.
        $eventoAntes = ($id > 0) ? $this->eventoDaLinha($id) : 0;

        if ($id > 0 && $this->existe($id)) {
            $fisico['atualizadoEm'] = $agora;
            $this->update($id, $fisico);
        } else {
            $fisico['criadoEm']     = $agora;
            $fisico['atualizadoEm'] = $agora;
            if ($id > 0) {
                $fisico['id'] = $id;   // preserva o id de origem, na migração
            }
            $id = $this->insert($fisico);
        }

        $this->hotelPorEvento = null;
        $this->cacheHotel = [];

        $this->sincronizarCusto([$eventoAntes, $this->eventoDaLinha($id)]);

        return $this->buscar($ent, $id, true) ?? $registro;
    }

    /**
     * Recusa gravar o que ficaria sem vínculo.
     *
     * O filho reencontra o pai por `h.idEvento = f.idEvento`. Se os dois forem
     * nulos o JOIN não casa — `NULL = NULL` não é verdadeiro em SQL —, e o
     * custo vira órfão em silêncio, sem erro e sem aparecer em tela. Por isso
     * o hotel precisa do evento do CRM antes de existir, e um filho só se
     * pendura em hotel que já o tenha.
     *
     * Compra avulsa é a exceção legítima: não tem hotel nem evento, e a coluna
     * `avulso` é quem a identifica.
     */
    private function exigirEvento(string $ent, array $registro): void
    {
        if ($ent === 'hotel') {
            if (empty($registro['idEventoCrm_hotel'])) {
                throw new RuntimeException(
                    'Escolha o evento do CRM antes de salvar o hotel: é ele que liga o hotel aos seus custos.'
                );
            }
            return;
        }

        $idHotel = $registro[HotelSchema::campo($ent, 'idHotel')] ?? null;
        if ($idHotel === null || $idHotel === '') {
            return;   // avulso
        }

        if (!$this->dadosDoHotel((int)$idHotel)['evento']) {
            throw new RuntimeException(
                'O hotel escolhido ainda não está vinculado a um evento do CRM. '
                . 'Ajuste o hotel primeiro — sem isso este custo não ficaria acessível.'
            );
        }
    }
    public function excluir(string $ent, int $id): bool
    {
        // Antes de marcar deletado: a soma depois vai excluir esta linha, e
        // é justamente por isso que o evento precisa ser recalculado.
        $evento = $this->eventoDaLinha($id);

        $stmt = $this->db->prepare(
            'UPDATE fornecedorEvento
                SET deletado_fornecedorEvento = 1, atualizadoEm_fornecedorEvento = ?
              WHERE id_fornecedorEvento = ? AND categoria_fornecedorEvento = ?'
        );
        $agora = date('Y-m-d H:i:s');
        $cat   = MapaFornecedor::categoria($ent);
        $stmt->bind_param('sis', $agora, $id, $cat);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        $this->hotelPorEvento = null;
        $this->cacheHotel = [];

        if ($ok) {
            $this->sincronizarCusto([$evento]);
        }

        return $ok;
    }

    /**
     * Substitui a coleção inteira de uma categoria. Usado só pela migração —
     * apaga de verdade, ao contrário do excluir(), que é lógico.
     */
    public function substituirTudo(string $ent, array $registros): void
    {
        $cat  = MapaFornecedor::categoria($ent);
        $stmt = $this->db->prepare('DELETE FROM fornecedorEvento WHERE categoria_fornecedorEvento = ?');
        $stmt->bind_param('s', $cat);
        $stmt->execute();
        $stmt->close();

        $this->hotelPorEvento = null;
        $this->cacheHotel = [];

        // Sem sincronia: são centenas de linhas, e sincronizar a cada uma
        // escreveria no CRM uma vez por lançamento, todas menos a última
        // descartáveis. Depois da carga, a tela de conciliação mostra o
        // que ficou fora de compasso.
        $this->semSincronia(function () use ($ent, $registros): void {
            foreach ($registros as $r) {
                $this->salvar($ent, $r);
                $this->hotelPorEvento = null;
                $this->cacheHotel = [];
            }
        });
    }

    /**
     * Eventos do CRM disponíveis para vínculo, do mais recente ao mais antigo.
     *
     * Traz também quais já têm hotel: a tela usa isso para separar os que ainda
     * estão livres dos que não estão, já que é um hotel por evento.
     */
    public function eventosCrm(): array
    {
        $sql = "SELECT e.id_evento                AS id,
                       e.nome_evento              AS nome,
                       DATE(e.data_evento)        AS data,
                       e.cidade_evento            AS cidade,
                       e.endereco_evento          AS endereco,
                       e.local_evento             AS local,
                       MAX(h.id_fornecedorEvento) AS idHotel
                  FROM evento e
                  LEFT JOIN fornecedorEvento h
                         ON h.idEvento_fornecedorEvento = e.id_evento
                        AND h.categoria_fornecedorEvento = 'hotel'
                        AND h.deletado_fornecedorEvento = 0
                 WHERE e.deletado_evento = 0
                 GROUP BY e.id_evento
                 ORDER BY e.data_evento DESC";

        $out = [];
        foreach ($this->db->query($sql)->fetch_all(MYSQLI_ASSOC) as $r) {
            $r['id']      = (int)$r['id'];
            $r['idHotel'] = $r['idHotel'] !== null ? (int)$r['idHotel'] : null;
            $out[] = $r;
        }
        return $out;
    }
    // =====================================================================
    // Tradução
    // =====================================================================

    /** Linha do banco => registro no formato lógico que a aplicação espera. */
    private function paraLogico(string $ent, array $l): array
    {
        $reg = HotelSchema::registroVazio($ent);
        $reg[HotelSchema::pk($ent)] = (int)$l['id_fornecedorEvento'];

        foreach (MapaFornecedor::campos($ent) as $logico => $fisico) {
            $col = MapaFornecedor::coluna($fisico);
            if (array_key_exists($col, $l)) {
                $reg[HotelSchema::campo($ent, $logico)] = $l[$col];
            }
        }

        // Cidade e endereço vêm do evento, não da tabela.
        if (isset($l['cidade_evento'])) {
            $chave = HotelSchema::campo($ent, 'cidade');
            if (array_key_exists($chave, $reg)) {
                $reg[$chave] = (string)$l['cidade_evento'];
            }
        }
        if ($ent === 'hotel' && isset($l['endereco_evento'])) {
            $reg['endereco_hotel'] = (string)$l['endereco_evento'];
        }
        if ($ent === 'hotel' && isset($l['nome_evento'])) {
            $reg['nomeEvento_hotel'] = (string)$l['nome_evento'];
        }
        if (isset($l['data_evento']) && array_key_exists(HotelSchema::campo($ent, 'dataEvento'), $reg)) {
            $reg[HotelSchema::campo($ent, 'dataEvento')] = substr((string)$l['data_evento'], 0, 10);
        }

        // Vínculo lógico: o id da linha de hotel do mesmo evento.
        $colIdHotel = HotelSchema::campo($ent, 'idHotel');
        if (array_key_exists($colIdHotel, $reg)) {
            $reg[$colIdHotel] = isset($l['_idHotel']) ? (int)$l['_idHotel'] : null;
        }

        $reg[HotelSchema::campo($ent, 'idLegado')]     = $l['idLegado_fornecedorEvento'];
        $reg[HotelSchema::campo($ent, 'criadoEm')]     = $l['criadoEm_fornecedorEvento'];
        $reg[HotelSchema::campo($ent, 'atualizadoEm')] = $l['atualizadoEm_fornecedorEvento'];
        $reg[HotelSchema::campo($ent, 'deletado')]     = (int)$l['deletado_fornecedorEvento'];

        // Normaliza tipos: o mysqli devolve tudo como string.
        return HotelSchema::normalizar($ent, $reg, $reg);
    }

    /** Registro lógico => colunas físicas (sem o sufixo). */
    private function paraFisico(string $ent, array $reg): array
    {
        $out = ['categoria' => MapaFornecedor::categoria($ent)];

        foreach (MapaFornecedor::campos($ent) as $logico => $fisico) {
            $chave = HotelSchema::campo($ent, $logico);
            if (array_key_exists($chave, $reg)) {
                $out[$fisico] = $reg[$chave];
            }
        }

        // O evento: no hotel vem do próprio campo; nos filhos, do hotel pai.
        if ($ent === 'hotel') {
            $out['idEvento'] = $reg['idEventoCrm_hotel'] ?? null;
        } else {
            $idHotel = $reg[HotelSchema::campo($ent, 'idHotel')] ?? null;
            $out['idEvento'] = $idHotel ? $this->dadosDoHotel((int)$idHotel)['evento'] : null;
        }

        // Data principal da linha. Vem do campo de data da própria entidade;
        // `equipamento` não tem nenhum, e nem todo transporte traz `dataViagem`.
        // Nesses casos herda a data do hotel — que é a do evento —, senão a
        // linha ficaria fora de qualquer consulta por período e o índice de
        // data não a alcançaria. É preenchimento de apoio, não dado de origem:
        // os campos específicos continuam vazios se vazios estavam.
        if (empty($out['data'])) {
            $campoData   = MapaFornecedor::campoData($ent);
            $out['data'] = $campoData ? ($reg[HotelSchema::campo($ent, $campoData)] ?: null) : null;
        }
        if (empty($out['data']) && $ent !== 'hotel' && !empty($idHotel)) {
            $out['data'] = $this->dadosDoHotel((int)$idHotel)['data'];
        }

        $out['idLegado'] = $reg[HotelSchema::campo($ent, 'idLegado')] ?? null;
        $out['deletado'] = (int)($reg[HotelSchema::campo($ent, 'deletado')] ?? 0);

        return $out;
    }

    /**
     * Evento e data da linha de hotel a que um filho pertence.
     *
     * @return array{evento:?int,data:?string}
     */
    private function dadosDoHotel(int $idHotel): array
    {
        if (isset($this->cacheHotel[$idHotel])) {
            return $this->cacheHotel[$idHotel];
        }

        $stmt = $this->db->prepare(
            'SELECT idEvento_fornecedorEvento AS ev, data_fornecedorEvento AS d
               FROM fornecedorEvento
              WHERE id_fornecedorEvento = ? AND categoria_fornecedorEvento = \'hotel\''
        );
        $stmt->bind_param('i', $idHotel);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $this->cacheHotel[$idHotel] = [
            'evento' => $r && $r['ev'] !== null ? (int)$r['ev'] : null,
            'data'   => $r ? $r['d'] : null,
        ];
    }

    // =====================================================================
    // SQL
    // =====================================================================

    private function existe(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM fornecedorEvento WHERE id_fornecedorEvento = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $achou = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $achou;
    }

    private function insert(array $fisico): int
    {
        [$cols, $tipos, $vals] = $this->preparar($fisico);
        $sql = 'INSERT INTO fornecedorEvento (' . implode(', ', $cols) . ') VALUES ('
             . implode(', ', array_fill(0, count($cols), '?')) . ')';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipos, ...$vals);
        $stmt->execute();
        $id = (int)($fisico['id'] ?? $this->db->insert_id);
        $stmt->close();
        return $id;
    }

    private function update(int $id, array $fisico): void
    {
        unset($fisico['id'], $fisico['criadoEm']);
        [$cols, $tipos, $vals] = $this->preparar($fisico);

        $sql = 'UPDATE fornecedorEvento SET ' . implode(' = ?, ', $cols) . ' = ? WHERE id_fornecedorEvento = ?';
        $vals[] = $id;
        $tipos .= 'i';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipos, ...$vals);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Monta colunas, string de tipos e valores para o bind_param.
     *
     * Tipo 'i' para inteiro e 'd' para decimal, não 's' para tudo: o
     * `insert()` do framework manda tudo como string, o que faz o MySQL
     * converter no meio do caminho e pode arredondar valor monetário.
     */
    private function preparar(array $fisico): array
    {
        $cols = $tipos = [];
        $vals = [];

        foreach ($fisico as $nome => $valor) {
            $cols[] = MapaFornecedor::coluna($nome);

            if (is_int($valor) || is_bool($valor)) {
                $tipos[] = 'i';
                $vals[]  = (int)$valor;
            } elseif (is_float($valor)) {
                $tipos[] = 'd';
                $vals[]  = $valor;
            } else {
                $tipos[] = 's';
                $vals[]  = $valor;   // null continua null e vira NULL na coluna
            }
        }

        return [$cols, implode('', $tipos), $vals];
    }
}
