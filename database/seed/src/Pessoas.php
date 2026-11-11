<?php

/**
 * Pessoas da base: a equipe interna (nomes fixos no código dos dashboards)
 * e os clientes fictícios.
 */
final class Pessoas
{
    private const DDDS = ['11', '11', '11', '21', '31', '41', '47', '48', '51', '61', '62', '71', '81', '85', '19', '27'];

    /** Nomes de um papel da equipe (ver config['equipe']). */
    public static function equipe(array $config, string $papel): array
    {
        if (!isset($config['equipe'][$papel])) {
            throw new InvalidArgumentException("Papel desconhecido na equipe: $papel");
        }
        return (array) $config['equipe'][$papel];
    }

    /**
     * Um cliente fictício. O e-mail usa o domínio reservado exemplo.test (o TLD .test não existe na internet) e um
     * número sequencial, então nunca se repete.
     *
     * @return array{primeiroNome: string, sobrenome: string, nome: string, email: string, telefone: string}
     */
    public static function novoCliente(Contexto $ctx): array
    {
        $s = $ctx->sorteio;
        $primeiro = $s->um($ctx->config['clientes']['nomes']);
        $sobrenome = $s->um($ctx->config['clientes']['sobrenomes']);
        $numero = $ctx->proximoCliente++;

        return [
            'primeiroNome' => $primeiro,
            'sobrenome' => $sobrenome,
            'nome' => "$primeiro $sobrenome",
            'email' => self::semAcento($primeiro) . '.' . self::semAcento($sobrenome) . $numero . '@exemplo.test',
            'telefone' => sprintf('+55 (%s) 9%04d-%04d', $s->um(self::DDDS), $s->inteiro(0, 9999), $s->inteiro(0, 9999)),
        ];
    }

    /** Minúsculas, sem acento e sem espaço (para montar e-mails). */
    public static function semAcento(string $texto): string
    {
        $mapa = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'É' => 'e', 'Ê' => 'e', 'Í' => 'i',
            'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ç' => 'c',
        ];
        return preg_replace('/[^a-z0-9]/', '', strtolower(strtr($texto, $mapa)));
    }
}
