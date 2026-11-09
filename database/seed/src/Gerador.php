<?php

/**
 * A ordem das etapas do gerador.
 *
 * Cada etapa lê o que as anteriores inseriram na Base e acrescenta as suas
 * tabelas. A ordem importa: não há venda sem lead, nem fatura sem combo.
 */
final class Gerador
{
    public const ETAPAS = [];

    /** Roda as etapas em ordem. Com $ate, para depois da etapa indicada. */
    public static function rodar(Contexto $ctx, ?string $ate = null): void
    {
        if ($ate !== null && !array_key_exists($ate, self::ETAPAS)) {
            throw new InvalidArgumentException("Etapa desconhecida: $ate");
        }
        foreach (self::ETAPAS as $nome => $etapa) {
            $etapa($ctx);
            if ($nome === $ate) {
                return;
            }
        }
    }
}
