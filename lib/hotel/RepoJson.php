<?php

require_once __DIR__ . '/Repositorio.php';

/**
 * Persistência em arquivo JSON — a fase de desenvolvimento, antes de o
 * schema ir para o MySQL.
 *
 * Um arquivo por entidade em .mrphp/hotel/, no formato:
 *
 *   {"entidade":"hotel","proximoId":72,"atualizadoEm":"...","registros":[ ... ]}
 *
 * Cada registro já usa os nomes de coluna finais (`nome_hotel`, `valor_hotel`)
 * com valores no tipo final. O arquivo é, literalmente, a tabela futura.
 *
 * Concorrência: toda escrita é um ciclo ler-alterar-gravar sob `flock(LOCK_EX)`
 * mantido do começo ao fim. Escrever em arquivo temporário e renomear — o
 * padrão usado em controle/biexplorer_controle.php — serve para substituir um
 * documento inteiro, mas aqui perderia a alteração de quem gravou no meio do
 * caminho, porque a leitura aconteceria fora do lock.
 */
final class HotelRepoJson implements HotelRepositorio
{
    private string $dir;

    /** Cache por requisição: evita reler o mesmo arquivo em cada agregado. */
    private array $memo = [];

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?: __DIR__ . '/../../.mrphp/hotel';
    }

    public function diretorio(): string
    {
        return $this->dir;
    }

    public function caminho(string $ent): string
    {
        if (!HotelSchema::existe($ent)) {
            throw new InvalidArgumentException("Entidade desconhecida: {$ent}");
        }
        return $this->dir . '/' . $ent . '.json';
    }

    // =====================================================================
    // Leitura
    // =====================================================================

    public function todos(string $ent, bool $incluirExcluidos = false): array
    {
        $doc  = $this->ler($ent);
        $cols = HotelSchema::campo($ent, 'deletado');

        $regs = $doc['registros'];
        if (!$incluirExcluidos) {
            $regs = array_values(array_filter($regs, static fn(array $r): bool => empty($r[$cols])));
        }

        return $regs;
    }

    public function buscar(string $ent, int $id, bool $incluirExcluidos = false): ?array
    {
        $pk = HotelSchema::pk($ent);
        foreach ($this->todos($ent, $incluirExcluidos) as $r) {
            if ((int)($r[$pk] ?? 0) === $id) {
                return $r;
            }
        }
        return null;
    }

    public function proximoId(string $ent): int
    {
        return (int)$this->ler($ent)['proximoId'];
    }

    // =====================================================================
    // Escrita
    // =====================================================================

    public function salvar(string $ent, array $registro): array
    {
        $pk    = HotelSchema::pk($ent);
        $agora = date('Y-m-d H:i:s');

        return $this->comLock($ent, function (array $doc) use ($ent, $registro, $pk, $agora): array {
            $id = (int)($registro[$pk] ?? 0);

            if ($id > 0) {
                $indice = null;
                foreach ($doc['registros'] as $i => $r) {
                    if ((int)($r[$pk] ?? 0) === $id) {
                        $indice = $i;
                        break;
                    }
                }

                if ($indice === null) {
                    // Id informado que não existe: trata como inserção com id
                    // fixo (é o que a importação faz ao preservar o id de
                    // origem). Nunca gravar em índice inexistente — o sistema
                    // antigo fazia arr[-1] em silêncio quando não achava.
                    $registro[HotelSchema::campo($ent, 'criadoEm')]     = $registro[HotelSchema::campo($ent, 'criadoEm')] ?? $agora;
                    $registro[HotelSchema::campo($ent, 'atualizadoEm')] = $agora;
                    $doc['registros'][] = $registro;
                } else {
                    $registro[HotelSchema::campo($ent, 'criadoEm')]     = $doc['registros'][$indice][HotelSchema::campo($ent, 'criadoEm')] ?? $agora;
                    $registro[HotelSchema::campo($ent, 'atualizadoEm')] = $agora;
                    $doc['registros'][$indice] = $registro;
                }

                $doc['proximoId'] = max((int)$doc['proximoId'], $id + 1);
            } else {
                $id = (int)$doc['proximoId'];
                $registro[$pk] = $id;
                $registro[HotelSchema::campo($ent, 'criadoEm')]     = $agora;
                $registro[HotelSchema::campo($ent, 'atualizadoEm')] = $agora;
                $doc['registros'][] = $registro;
                $doc['proximoId']   = $id + 1;
            }

            return ['doc' => $doc, 'retorno' => $registro];
        });
    }

    public function excluir(string $ent, int $id): bool
    {
        $pk       = HotelSchema::pk($ent);
        $colDel   = HotelSchema::campo($ent, 'deletado');
        $colAtual = HotelSchema::campo($ent, 'atualizadoEm');
        $agora    = date('Y-m-d H:i:s');

        return $this->comLock($ent, function (array $doc) use ($id, $pk, $colDel, $colAtual, $agora): array {
            $ok = false;
            foreach ($doc['registros'] as $i => $r) {
                if ((int)($r[$pk] ?? 0) === $id) {
                    $doc['registros'][$i][$colDel]   = 1;
                    $doc['registros'][$i][$colAtual] = $agora;
                    $ok = true;
                    break;
                }
            }
            return ['doc' => $doc, 'retorno' => $ok];
        });
    }

    public function substituirTudo(string $ent, array $registros): void
    {
        $pk  = HotelSchema::pk($ent);
        $max = 0;
        foreach ($registros as $r) {
            $max = max($max, (int)($r[$pk] ?? 0));
        }

        $this->comLock($ent, static function (array $doc) use ($registros, $max): array {
            $doc['registros'] = array_values($registros);
            $doc['proximoId'] = $max + 1;
            return ['doc' => $doc, 'retorno' => null];
        });
    }

    // =====================================================================
    // Arquivo
    // =====================================================================

    private function documentoVazio(string $ent): array
    {
        return [
            'entidade'     => $ent,
            'proximoId'    => 1,
            'atualizadoEm' => null,
            'registros'    => [],
        ];
    }

    private function ler(string $ent): array
    {
        if (isset($this->memo[$ent])) {
            return $this->memo[$ent];
        }

        $caminho = $this->caminho($ent);
        $doc     = $this->documentoVazio($ent);

        if (is_file($caminho)) {
            $conteudo = (string)file_get_contents($caminho);
            $lido     = json_decode($conteudo, true);
            if (is_array($lido) && isset($lido['registros']) && is_array($lido['registros'])) {
                $doc = $lido + $doc;
                $doc['registros'] = $lido['registros'];
            }
        }

        return $this->memo[$ent] = $doc;
    }

    /**
     * Ciclo ler-alterar-gravar com lock exclusivo mantido do início ao fim.
     * O callback recebe o documento e devolve ['doc'=>..., 'retorno'=>...].
     */
    private function comLock(string $ent, callable $fn): mixed
    {
        $caminho = $this->caminho($ent);
        $this->garantirDiretorio();

        $fh = fopen($caminho, 'c+');
        if ($fh === false) {
            throw new RuntimeException("Não foi possível abrir {$caminho}");
        }

        try {
            if (!flock($fh, LOCK_EX)) {
                throw new RuntimeException("Não foi possível travar {$caminho}");
            }

            $conteudo = (string)stream_get_contents($fh);
            $lido     = json_decode($conteudo, true);
            $doc      = $this->documentoVazio($ent);
            if (is_array($lido) && isset($lido['registros']) && is_array($lido['registros'])) {
                $doc = $lido + $doc;
                $doc['registros'] = $lido['registros'];
            }

            $res = $fn($doc);
            $novo = $res['doc'];
            $novo['entidade']     = $ent;
            $novo['atualizadoEm'] = date('c');

            $json = json_encode($novo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException("Falha ao serializar {$ent}: " . json_last_error_msg());
            }

            rewind($fh);
            ftruncate($fh, 0);
            if (fwrite($fh, $json) === false) {
                throw new RuntimeException("Falha ao gravar {$caminho}");
            }
            fflush($fh);

            $this->memo[$ent] = $novo;

            return $res['retorno'];
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private function garantirDiretorio(): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    /** Descarta o cache de leitura — necessário entre etapas da importação. */
    public function limparMemo(): void
    {
        $this->memo = [];
    }
}
