<?php

/**
 * Lê constantes declaradas nos controllers dos dashboards.
 *
 * Nomes da equipe, IDs de CSM e produtos da campanha de metas já estão
 * escritos no código dos dashboards. Ler de lá (em vez de copiar) garante
 * que o gerador e as telas usem exatamente os mesmos valores.
 */
final class ConstantesDoCodigo
{
    /** Ex.: ler('comercial4', 'LIGA_PRODUTOS'). Funciona com constantes privadas. */
    public static function ler(string $controller, string $constante): mixed
    {
        $raiz = dirname(__DIR__, 3);
        require_once $raiz . '/mrphp.php';
        require_once $raiz . "/controle/{$controller}_controle.php";

        $classe = new ReflectionClass($controller);
        if (!$classe->hasConstant($constante)) {
            throw new InvalidArgumentException("O controller $controller não tem a constante $constante.");
        }
        return $classe->getConstant($constante);
    }
}
