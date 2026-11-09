<?php
/**
 * Asserções mínimas para os testes do projeto (sem PHPUnit, sem Composer).
 *
 * Cada função lança FalhaDeTeste quando a condição não vale. O runner
 * (tests/rodar.php) captura a exceção e mostra a mensagem.
 */

final class FalhaDeTeste extends Exception {}

function afirmar(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new FalhaDeTeste($mensagem);
    }
}

function afirmarIgual(mixed $esperado, mixed $obtido, string $mensagem = ''): void
{
    if ($esperado !== $obtido) {
        $detalhe = 'esperado ' . descreverValor($esperado) . ', obtido ' . descreverValor($obtido);
        throw new FalhaDeTeste($mensagem === '' ? $detalhe : "$mensagem ($detalhe)");
    }
}

function afirmarEntre(float $min, float $max, float $obtido, string $mensagem = ''): void
{
    if ($obtido < $min || $obtido > $max) {
        $detalhe = "esperado entre $min e $max, obtido $obtido";
        throw new FalhaDeTeste($mensagem === '' ? $detalhe : "$mensagem ($detalhe)");
    }
}

function descreverValor(mixed $valor): string
{
    $texto = var_export($valor, true);
    return strlen($texto) > 200 ? substr($texto, 0, 200) . '…' : $texto;
}
