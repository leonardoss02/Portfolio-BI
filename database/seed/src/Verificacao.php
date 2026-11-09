<?php

/**
 * Conferências rápidas antes de gravar a base, e o resumo que o gerador
 * imprime no final.
 *
 * Não repete os testes: só pega o que deixaria uma tela vazia ou com números
 * incoerentes. Se alguma falhar, o gerador para sem tocar no banco.
 */
final class Verificacao
{
    /** Tabelas que podem ficar vazias de propósito. */
    private const PODEM_FICAR_VAZIAS = ['deal'];

    /** @return string[] mensagens de falha (vazio = tudo certo) */
    public static function checar(Contexto $ctx): array
    {
        $base = $ctx->base;
        $falhas = [];

        foreach (self::tabelasDoSchema() as $tabela) {
            if (!in_array($tabela, self::PODEM_FICAR_VAZIAS, true) && $base->total($tabela) === 0) {
                $falhas[] = "A tabela $tabela ficou vazia.";
            }
        }

        $soma = [];
        foreach ($base->linhas('fornecedorEvento') as $f) {
            $soma[$f['idEvento_fornecedorEvento']] = ($soma[$f['idEvento_fornecedorEvento']] ?? 0) + $f['custo_fornecedorEvento'];
        }
        foreach ($base->linhas('custoEvento') as $c) {
            if (abs(($soma[$c['id_evento']] ?? 0) - $c['valor_custoEvento']) > 0.01) {
                $falhas[] = "custoEvento do evento {$c['id_evento']} não é a soma dos fornecedores.";
            }
        }

        foreach ($base->linhas('dealV2') as $d) {
            if (str_starts_with($d['faseId_dealV2'], 'C5:') && trim($d['fase_dealV2']) === '') {
                $falhas[] = "Card C5 {$d['idBitrix_dealV2']} sem fase (some dos cálculos de FP).";
                break;
            }
        }

        $equipe = $ctx->config['equipe'];
        if (ConstantesDoCodigo::ler('chatwoot', 'CW_TEAM') !== $equipe['atendimento']) {
            $falhas[] = 'O time de atendimento do config não bate com controle/chatwoot_controle.php.';
        }
        if (ConstantesDoCodigo::ler('comercial', 'EQUIPE_COMERCIAL2') !== $equipe['comercial2']) {
            $falhas[] = 'O time do Comercial 2 do config não bate com controle/comercial_controle.php.';
        }

        return $falhas;
    }

    /** Linhas por tabela e o funil comercial, para quem roda o gerador. */
    public static function resumo(Base $base): string
    {
        $linhas = [];
        foreach ($base->tabelas() as $tabela) {
            $linhas[] = sprintf('  %-18s %8d linhas', $tabela, $base->total($tabela));
        }

        $conta = fn(callable $filtro) => count(array_filter($base->linhas('dealV2'), $filtro));
        $leads = $base->total('leadV2');
        $qualificados = count(array_filter($base->linhas('leadV2'), fn($l) => $l['qualificado_leadV2'] === 'Sim'));
        $vendidas = $conta(fn($d) => in_array($d['faseId_dealV2'], ['WON', 'C11:WON'], true));
        $realizadas = $conta(fn($d) => str_starts_with($d['faseId_dealV2'], 'C5:') && !empty($d['dataConsultaRealizada_dealV2']));
        $fp = $conta(fn($d) => str_starts_with($d['faseId_dealV2'], 'C5:') && !empty($d['dataFpRealizado_dealV2']));
        $combos = $conta(fn($d) => in_array($d['faseId_dealV2'], ['C5:WON', 'C1:WON', 'C29:WON', 'C31:WON'], true));

        $linhas[] = sprintf(
            'Funil: %d leads → %d qualificados → %d consultas vendidas → %d realizadas → %d FP → %d combos',
            $leads, $qualificados, $vendidas, $realizadas, $fp, $combos
        );
        return implode("\n", $linhas);
    }

    /** Nomes das tabelas declaradas em database/schema.sql. */
    private static function tabelasDoSchema(): array
    {
        preg_match_all('/CREATE TABLE (\w+)/', file_get_contents(dirname(__DIR__, 2) . '/schema.sql'), $m);
        return $m[1];
    }
}
