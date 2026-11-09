<?php

/**
 * Etapa 1: eventos e câmbio.
 *
 * O carro-chefe da empresa é o "On The Road": um evento presencial por
 * semana, sempre no sábado, numa cidade diferente, com um dos vendedores
 * como palestrante. A cada trimestre há também um webinar e um encontro 1:1.
 * Os eventos vão até 45 dias no futuro (os próximos já estão vendendo cadeira).
 *
 * O câmbio é uma linha por dia corrido: custos em reais são convertidos para
 * dólar pela cotação do mesmo dia.
 */
final class Eventos
{
    public static function gerar(Contexto $ctx): void
    {
        self::gerarEventos($ctx);
        self::gerarCambio($ctx);
    }

    private static function gerarEventos(Contexto $ctx): void
    {
        $cfg = $ctx->config['eventos'];
        $palestrantes = $ctx->config['equipe']['palestrantes'];
        $cidades = array_keys($cfg['cidades']);
        $fim = $ctx->hoje->modify("+{$cfg['diasFuturos']} days");
        $recentes = [];

        $semana = 0;
        for ($sabado = new DateTimeImmutable($cfg['primeiroSabado']); $sabado <= $fim; $sabado = $sabado->modify('+7 days')) {
            // On The Road: cidade que não apareceu nas últimas semanas.
            $cidade = $ctx->sorteio->um(array_diff($cidades, $recentes));
            $recentes[] = $cidade;
            $recentes = array_slice($recentes, -$cfg['semanasSemRepetirCidade']);
            self::inserirEvento($ctx, $sabado, "On The Road $cidade", "OTR $cidade", $cidade, $palestrantes[$semana % count($palestrantes)]);

            // Webinar e 1:1 caem na quarta-feira da mesma semana.
            $quarta = $sabado->modify('-3 days');
            if ($semana % $cfg['intervaloWebinar'] === 0) {
                self::inserirEvento($ctx, $quarta, 'Webinar', 'WEB Imigração', 'Online', $ctx->sorteio->um($palestrantes));
            } elseif ($semana % $cfg['intervaloWebinar'] === $cfg['deslocamento1a1']) {
                $cidade1a1 = $ctx->sorteio->um($cidades);
                self::inserirEvento($ctx, $quarta, "1:1 $cidade1a1", "INA $cidade1a1", $cidade1a1, $ctx->sorteio->um($palestrantes));
            }
            $semana++;
        }
    }

    private static function inserirEvento(Contexto $ctx, DateTimeImmutable $data, string $nome, string $prefixoForm, string $cidade, string $palestrante): void
    {
        $online = $cidade === 'Online';
        $sobrenome = $ctx->sorteio->um($ctx->config['clientes']['sobrenomes']);
        $ctx->base->inserir('evento', [
            'nome_evento' => $nome,
            'data_evento' => $data->format('Y-m-d'),
            'cidade_evento' => $cidade,
            'local_evento' => $online ? 'Online' : "Hotel $sobrenome $cidade",
            'endereco_evento' => $online ? null : "Av. $sobrenome, " . $ctx->sorteio->inteiro(10, 3500) . " - $cidade",
            'palestrante_evento' => $palestrante,
            'nomeForm_evento' => $prefixoForm . ' ' . $data->format('Y-m-d'),
            'status_evento' => 1,
            'deletado_evento' => 0,
        ]);
    }

    /** Passeio aleatório entre o mínimo e o máximo, rebatendo nos limites. */
    private static function gerarCambio(Contexto $ctx): void
    {
        $cfg = $ctx->config['cambio'];
        $fim = $ctx->hoje->modify("+{$ctx->config['eventos']['diasFuturos']} days");
        $valor = $cfg['inicial'];
        foreach (self::diasEntre($ctx->calendario->inicio(), $fim) as $dia) {
            $valor += $ctx->sorteio->normal(0, $cfg['passo']);
            if ($valor > $cfg['maximo']) {
                $valor = 2 * $cfg['maximo'] - $valor;
            } elseif ($valor < $cfg['minimo']) {
                $valor = 2 * $cfg['minimo'] - $valor;
            }
            $ctx->base->inserir('cambio', [
                'data_cambio' => $dia->format('Y-m-d'),
                'valor_cambio' => round($valor, 4),
            ]);
        }
    }

    private static function diasEntre(DateTimeImmutable $inicio, DateTimeImmutable $fim): Generator
    {
        for ($dia = $inicio; $dia <= $fim; $dia = $dia->modify('+1 day')) {
            yield $dia;
        }
    }
}
