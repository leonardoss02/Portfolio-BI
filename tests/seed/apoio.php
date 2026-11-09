<?php
/**
 * Funções de apoio para os testes do gerador.
 *
 * gerarAte() memoriza o resultado por (etapa, hoje, escala): vários testes
 * da mesma etapa reaproveitam a mesma base em vez de gerá-la de novo.
 */

function configDeTeste(float $escala = 0.1): array
{
    $config = require dirname(__DIR__, 2) . '/database/seed/config.php';
    $config['escala'] = $escala;
    return $config;
}

function contextoDeTeste(string $hoje = '2026-11-09', float $escala = 0.1): Contexto
{
    return Contexto::criar($hoje, configDeTeste($escala));
}

/** Roda o gerador até a etapa indicada (null = todas). */
function gerarAte(?string $etapa, string $hoje = '2026-11-09', float $escala = 0.1): Contexto
{
    static $memoria = [];
    $chave = ($etapa ?? '*') . "|$hoje|$escala";
    if (!isset($memoria[$chave])) {
        $ctx = contextoDeTeste($hoje, $escala);
        Gerador::rodar($ctx, $etapa);
        $memoria[$chave] = $ctx;
    }
    return $memoria[$chave];
}

/** Linhas de uma tabela que satisfazem o filtro. */
function filtrar(Contexto $ctx, string $tabela, callable $filtro): array
{
    return array_filter($ctx->base->linhas($tabela), $filtro);
}
