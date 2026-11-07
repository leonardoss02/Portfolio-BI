<?php

/**
 * Cliente SMTP mínimo por socket — sem lib externa (projeto não usa Composer).
 *
 * Único uso hoje: alertaeventos_controle.php envia o email de pendência de
 * evento (quarto/A/V) através de uma conta do Google Workspace com senha de
 * app (SMTP normal não funciona com 2FA ligado, que é o padrão do Workspace).
 * Credenciais vêm do `.env` via `HotelRepo::lerEnv()` — mesma leitura direta
 * de arquivo que o resto do módulo já usa para funcionar fora do bootstrap
 * do framework.
 */
final class SmtpMailer
{
    /** @throws RuntimeException em qualquer falha de conexão, TLS, auth ou envio */
    public static function enviar(string $destinatario, string $assunto, string $htmlBody): void
    {
        $host           = (string)HotelRepo::lerEnv('SMTP_HOST');
        $porta          = (int)(HotelRepo::lerEnv('SMTP_PORT') ?: 587);
        $usuario        = (string)HotelRepo::lerEnv('SMTP_USER');
        $senha          = (string)HotelRepo::lerEnv('SMTP_PASS');
        $remetenteEmail = (string)(HotelRepo::lerEnv('SMTP_FROM_EMAIL') ?: $usuario);
        $remetenteNome  = (string)(HotelRepo::lerEnv('SMTP_FROM_NOME') ?: $remetenteEmail);

        if ($host === '' || $usuario === '' || $senha === '') {
            throw new RuntimeException('SMTP não configurado no .env (SMTP_HOST/SMTP_USER/SMTP_PASS).');
        }

        $socket = @stream_socket_client("tcp://{$host}:{$porta}", $errno, $errstr, 15);
        if (!$socket) {
            throw new RuntimeException("Falha ao conectar em {$host}:{$porta} — {$errstr} ({$errno})");
        }
        stream_set_timeout($socket, 15);

        try {
            $heloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';

            self::ler($socket, 220);
            self::comando($socket, "EHLO {$heloHost}", 250);
            self::comando($socket, 'STARTTLS', 220);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Falha ao negociar TLS com o servidor SMTP.');
            }

            self::comando($socket, "EHLO {$heloHost}", 250);
            self::comando($socket, 'AUTH LOGIN', 334);
            self::comando($socket, base64_encode($usuario), 334);
            self::comando($socket, base64_encode($senha), 235);

            self::comando($socket, "MAIL FROM:<{$remetenteEmail}>", 250);
            self::comando($socket, "RCPT TO:<{$destinatario}>", 250);
            self::comando($socket, 'DATA', 354);

            $headers = [
                'From: ' . self::codificarPalavra($remetenteNome) . " <{$remetenteEmail}>",
                "To: <{$destinatario}>",
                'Subject: ' . self::codificarPalavra($assunto),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'Date: ' . date('r'),
            ];

            // Corpo em base64: evita por completo o dot-stuffing (RFC 5321) e
            // o limite prático de tamanho de linha do protocolo SMTP — o
            // alfabeto base64 nunca produz uma linha começando com ".".
            $corpoBase64 = chunk_split(base64_encode($htmlBody), 76, "\r\n");

            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $corpoBase64 . "\r\n.\r\n");
            self::ler($socket, 250);

            self::comando($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    /** @return resource */
    private static function comando($socket, string $linha, int $codigoEsperado): string
    {
        fwrite($socket, $linha . "\r\n");
        return self::ler($socket, $codigoEsperado);
    }

    private static function ler($socket, int $codigoEsperado): string
    {
        $resposta = '';
        while (($linha = fgets($socket, 515)) !== false) {
            $resposta .= $linha;
            // Resposta multi-linha do SMTP: continuação tem "-" na 4ª posição
            // ("250-..."), a última linha tem espaço ("250 ...").
            if (isset($linha[3]) && $linha[3] === ' ') {
                break;
            }
        }

        $codigo = (int)substr($resposta, 0, 3);
        if ($codigo !== $codigoEsperado) {
            throw new RuntimeException("Resposta SMTP inesperada (esperado {$codigoEsperado}): {$resposta}");
        }
        return $resposta;
    }

    private static function codificarPalavra(string $texto): string
    {
        return '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }
}
