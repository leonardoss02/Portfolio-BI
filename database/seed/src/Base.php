<?php

/**
 * As tabelas em memória, antes de irem para o banco.
 *
 * Cada etapa do gerador insere linhas aqui. Só no final o Banco grava tudo
 * de uma vez, o que deixa as checagens de coerência rodarem antes.
 *
 * Chaves que começam com "_" são anotações internas do gerador (ex.: de qual
 * lead veio um card) e não vão para o banco.
 */
final class Base
{
    /** Onde começa cada série de IDs do Bitrix (e do QuickBooks/Chatwoot). */
    private const INICIO_SERIES = [
        'deal'      => 100001,
        'lead'      => 500001,
        'contato'   => 300001,
        'orcamento' => 70001,
        'conversa'  => 1,
        'clienteQb' => 4001,
    ];

    /** @var array<string, array<int, array>> */
    private array $tabelas = [];

    /** @var array<string, int> */
    private array $proximoId = [];

    /** @var array<string, int> */
    private array $series = [];

    /** Insere a linha, define "id_{tabela}" e devolve o id. */
    public function inserir(string $tabela, array $linha): int
    {
        $id = $this->proximoId[$tabela] = ($this->proximoId[$tabela] ?? 0) + 1;
        $linha['id_' . $tabela] = $id;
        $this->tabelas[$tabela][$id] = $linha;
        return $id;
    }

    public function atualizar(string $tabela, int $id, array $campos): void
    {
        if (!isset($this->tabelas[$tabela][$id])) {
            throw new OutOfBoundsException("Linha $id não existe em $tabela.");
        }
        $this->tabelas[$tabela][$id] = array_merge($this->tabelas[$tabela][$id], $campos);
    }

    /** @return array<int, array> linhas indexadas pelo id */
    public function linhas(string $tabela): array
    {
        return $this->tabelas[$tabela] ?? [];
    }

    public function linha(string $tabela, int $id): array
    {
        if (!isset($this->tabelas[$tabela][$id])) {
            throw new OutOfBoundsException("Linha $id não existe em $tabela.");
        }
        return $this->tabelas[$tabela][$id];
    }

    public function total(string $tabela): int
    {
        return count($this->tabelas[$tabela] ?? []);
    }

    /** Nomes das tabelas que têm ao menos uma linha. */
    public function tabelas(): array
    {
        return array_keys($this->tabelas);
    }

    /** Próximo ID da série (como texto, igual ao Bitrix). */
    public function novoIdBitrix(string $serie): string
    {
        if (!isset(self::INICIO_SERIES[$serie])) {
            throw new InvalidArgumentException("Série de ID desconhecida: $serie");
        }
        $valor = $this->series[$serie] ?? self::INICIO_SERIES[$serie];
        $this->series[$serie] = $valor + 1;
        return (string) $valor;
    }
}
