<?php

require_once __DIR__ . '/Schema.php';

/**
 * Contrato de persistência do sistema de Gestão de Eventos (hotéis).
 *
 * A implementação em uso é HotelRepoMysql, sobre a tabela `fornecedorEvento`.
 * HotelRepoJson continua existindo com o MESMO formato de registro, mas só
 * como fonte da migração (lib/hotel/migrar.php) — a aplicação não o instancia
 * mais. É por os dois falarem o mesmo formato — nomes `{campo}_{tabela}`,
 * valores numéricos, datas ISO ou null — que a troca não alterou controller,
 * view nem JS.
 */
interface HotelRepositorio
{
    /** @return array<int,array> registros da entidade, sem os excluídos por padrão */
    public function todos(string $ent, bool $incluirExcluidos = false): array;

    public function buscar(string $ent, int $id, bool $incluirExcluidos = false): ?array;

    /**
     * Insere (id nulo) ou atualiza (id preenchido). O id é gerado pelo
     * servidor — no sistema antigo era gerado no cliente, o que fazia duas
     * sessões abertas ao mesmo tempo colidirem no mesmo número.
     *
     * @return array o registro gravado, já com o id
     */
    public function salvar(string $ent, array $registro): array;

    /** Exclusão lógica: marca `deletado_{ent}` = 1. */
    public function excluir(string $ent, int $id): bool;

    /** Substitui a coleção inteira. Usado só pela importação. */
    public function substituirTudo(string $ent, array $registros): void;

    /** Maior id em uso, para a importação preservar os ids de origem. */
    public function proximoId(string $ent): int;
}

final class HotelRepo
{
    /**
     * O repositório da aplicação: banco, sempre.
     *
     * Já teve uma chave de `.env` para alternar entre arquivo e banco, de
     * quando a tabela ainda não existia. Não tem mais: a fase de arquivo
     * acabou, e uma chave que só podia ter um valor certo era um jeito de o
     * sistema subir lendo o lugar errado se alguém esquecesse de defini-la.
     *
     * `$db` só é passado em teste e em script de linha de comando, para
     * apontar outra conexão; a aplicação usa a padrão.
     */
    public static function criar(?mysqli $db = null): HotelRepositorio
    {
        require_once __DIR__ . '/RepoMysql.php';
        return new HotelRepoMysql($db);
    }

    /**
     * Leitura direta do .env. O framework já carrega o .env em
     * MRPHP::initialize(), mas o importador roda por CLI, fora do
     * framework — por isso a leitura própria aqui.
     */
    public static function lerEnv(string $chave): ?string
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];
            $path  = __DIR__ . '/../../.env';
            if (file_exists($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
                    $linha = trim($linha);
                    if ($linha === '' || str_starts_with($linha, '#')) {
                        continue;
                    }
                    $pos = strpos($linha, '=');
                    if ($pos === false) {
                        continue;
                    }
                    $cache[trim(substr($linha, 0, $pos))] = trim(trim(substr($linha, $pos + 1)), '"\'');
                }
            }
        }

        return $cache[$chave] ?? null;
    }
}
