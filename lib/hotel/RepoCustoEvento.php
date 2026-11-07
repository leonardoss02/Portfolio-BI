<?php

require_once __DIR__ . '/RepoMysql.php';

/**
 * A única escrita deste sistema na tabela `custoEvento`, do CRM.
 *
 * Nasceu só com `inserir()`: a decisão original era não mexer em evento cujo
 * valor divergisse — esses iam para análise manual, e um método ausente não é
 * chamado por engano. **Em 04/09/2026 isso mudou**: o custo do CRM passou a
 * acompanhar automaticamente o que o usuário lança na tela, então existem
 * agora `atualizar()` e `sincronizar()`.
 *
 * `inserir()` seguiu estrito de propósito (recusa valor <= 0 e linha
 * duplicada) porque continua servindo ao botão da tela de conciliação;
 * `sincronizar()` é quem tem as regras da sincronia automática.
 *
 * Três cuidados que a tabela exige:
 *
 *  1. **Nada impede linha duplicada.** Não há índice único em `id_evento`, e o
 *     dashboard de Eventos soma por essa coluna: duas linhas dobrariam o custo
 *     do evento. Por isso `inserir()` recusa se já existir.
 *  2. **O schema difere entre ambientes.** Produção tem 7 colunas; o dev, um
 *     retrato mais antigo, tem 8 (um `status_custoEvento` que produção não tem
 *     mais). Colunas fixas quebrariam num dos dois, então as colunas são
 *     descobertas em tempo de execução.
 *  3. **Erro tem de aparecer.** A conexão vem do `HotelRepoMysql::conectar()`,
 *     que liga `mysqli_report`; nunca `MRPHP::insert()`, que devolveria
 *     `false` em silêncio.
 */
final class RepoCustoEvento
{
    /**
     * Valor para coluna que existe em algum ambiente mas não faz parte da
     * convenção de produção. Sem isto, o INSERT no dev falharia por NOT NULL.
     */
    private const EXTRAS = [
        'status_custoEvento' => 'Em andamento',
    ];

    private mysqli $db;

    /** Colunas reais da tabela neste banco, sem a PK. */
    private array $colunas;

    public function __construct(?mysqli $db = null)
    {
        $this->db      = $db ?? HotelRepoMysql::conectar();
        $this->colunas = $this->descobrirColunas();
    }

    public function conexao(): mysqli
    {
        return $this->db;
    }

    public function colunas(): array
    {
        return $this->colunas;
    }

    /**
     * Grava o custo consolidado de um evento.
     *
     * @param array $evento id, nome, data e identificador vindos de `evento`
     * @param float $valor  SUM(custo_fornecedorEvento), em BRL
     * @return int          o id gravado, para o caso de precisar desfazer
     */
    public function inserir(array $evento, float $valor): int
    {
        $id = (int)($evento['id'] ?? 0);

        if ($id <= 0) {
            throw new RuntimeException('Evento sem id — nada a gravar.');
        }
        if ($valor <= 0) {
            throw new RuntimeException('Evento sem custo lançado: não há o que gravar.');
        }
        if ($this->jaTem($id)) {
            throw new RuntimeException(
                "O evento {$id} já tem linha em custoEvento. Atualizar valor existente "
                . 'é decisão manual, não deste botão.'
            );
        }

        $dados = $this->montar($evento, $valor);

        $cols  = array_keys($dados);
        $tipos = '';
        $vals  = [];
        foreach ($dados as $col => $v) {
            $tipos .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
            $vals[] = $v;
        }

        $sql = 'INSERT INTO custoEvento (' . implode(', ', $cols) . ') VALUES ('
             . implode(', ', array_fill(0, count($cols), '?')) . ')';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipos, ...$vals);
        $stmt->execute();
        $novo = (int)$this->db->insert_id;
        $stmt->close();

        return $novo;
    }

    /** A linha que será gravada, para conferência antes de executar. */
    public function montar(array $evento, float $valor): array
    {
        $conhecidas = [
            'id_evento'                 => (int)$evento['id'],
            'nome_custoEvento'          => (string)($evento['nome'] ?? ''),
            'identificador_custoEvento' => (string)($evento['identificador'] ?? ''),
            'data_custoEvento'          => substr((string)($evento['data'] ?? ''), 0, 10),
            'valor_custoEvento'         => round($valor, 2),
            // Zero nas 129 linhas de produção, e nada no código lê esta coluna.
            'id_ordemCompra'            => 0,
        ];

        $dados = [];
        foreach ($this->colunas as $col) {
            if (array_key_exists($col, $conhecidas)) {
                $dados[$col] = $conhecidas[$col];
            } elseif (array_key_exists($col, self::EXTRAS)) {
                $dados[$col] = self::EXTRAS[$col];
            } else {
                throw new RuntimeException(
                    "A tabela custoEvento tem a coluna '{$col}', que este sistema não sabe "
                    . 'preencher. O schema mudou desde a última verificação.'
                );
            }
        }

        return $dados;
    }

    public function jaTem(int $idEvento): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM custoEvento WHERE id_evento = ? LIMIT 1');
        $stmt->bind_param('i', $idEvento);
        $stmt->execute();
        $achou = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();

        return $achou;
    }

    /**
     * Linha atual de um evento, ou null.
     *
     * @return array{id:int,valor:float}|null
     */
    public function linhaDe(int $idEvento): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id_custoEvento, valor_custoEvento FROM custoEvento WHERE id_evento = ? LIMIT 1'
        );
        $stmt->bind_param('i', $idEvento);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $r ? ['id' => (int)$r['id_custoEvento'], 'valor' => (float)$r['valor_custoEvento']] : null;
    }

    /**
     * Muda o valor de uma linha existente.
     *
     * Não existia até 04/09/2026: o módulo foi desenhado para não tocar em
     * divergência, e um método ausente não é chamado por engano. Passou a
     * existir quando a sincronia automática virou requisito — o custo do CRM
     * agora acompanha o que o usuário lança na tela.
     *
     * Só o valor muda. Nome, identificador e data pertencem ao `evento` e não
     * são deste sistema.
     */
    public function atualizar(int $idCustoEvento, float $valor): bool
    {
        if ($idCustoEvento <= 0) {
            throw new RuntimeException('Linha de custoEvento sem id — nada a atualizar.');
        }

        $v = round($valor, 2);
        $stmt = $this->db->prepare(
            'UPDATE custoEvento SET valor_custoEvento = ? WHERE id_custoEvento = ?'
        );
        $stmt->bind_param('di', $v, $idCustoEvento);
        $stmt->execute();
        $mexeu = $stmt->affected_rows > 0;
        $stmt->close();

        return $mexeu;
    }

    /**
     * Põe o custo do CRM em dia com a soma dos fornecedores do evento.
     *
     * As regras, decididas em 04/09/2026:
     *
     *  - linha existe  → atualiza, inclusive para zero (evento que perdeu
     *    todos os lançamentos custa zero, e esconder isso recria divergência);
     *  - linha não existe e há custo → insere;
     *  - linha não existe e o custo é zero → não faz nada, para não encher a
     *    tabela do CRM de linha vazia.
     *
     * Não escreve quando o valor já está igual: salvar o telefone de um
     * videomaker não é motivo para escrever no CRM.
     *
     * @return string '' se nada mudou, ou uma descrição do que foi feito
     */
    public function sincronizar(int $idEvento, float $total, array $evento): string
    {
        if ($idEvento <= 0) {
            return '';
        }

        $total   = round($total, 2);
        $atual   = $this->linhaDe($idEvento);

        if ($atual === null) {
            if ($total <= 0) {
                return '';
            }
            $novo = $this->inserir($evento + ['id' => $idEvento], $total);
            return "custoEvento {$novo} criado com " . number_format($total, 2, ',', '.');
        }

        if (abs($atual['valor'] - $total) < 0.005) {
            return '';
        }

        $this->atualizar($atual['id'], $total);
        return sprintf(
            'custoEvento %d: %s → %s',
            $atual['id'],
            number_format($atual['valor'], 2, ',', '.'),
            number_format($total, 2, ',', '.')
        );
    }

    /** Desfaz uma gravação — o único caminho de volta, já que /sql/query só lê. */
    public function remover(int $idCustoEvento): bool
    {
        $stmt = $this->db->prepare('DELETE FROM custoEvento WHERE id_custoEvento = ?');
        $stmt->bind_param('i', $idCustoEvento);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return $ok;
    }

    /** @return string[] colunas da tabela neste banco, exceto a PK */
    private function descobrirColunas(): array
    {
        $out = [];
        foreach ($this->db->query('SHOW COLUMNS FROM custoEvento')->fetch_all(MYSQLI_ASSOC) as $c) {
            if ($c['Field'] !== 'id_custoEvento') {
                $out[] = $c['Field'];
            }
        }
        return $out;
    }
}
