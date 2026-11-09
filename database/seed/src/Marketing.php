<?php

/**
 * Etapa 2: campanhas de marketing e o gasto diário de cada anúncio.
 *
 * O catálogo de campanhas fica em $ctx->campanhas e é dele que os leads tiram
 * as UTMs (campanha, conjunto e anúncio) e o formulário. Por isso os nomes
 * batem entre custoMkt, leadV2 e dealV2 — o dashboard de Marketing junta as
 * três tabelas pelo texto.
 *
 * Classes de campanha (pelas 3 primeiras letras do nome, como no código):
 *   evento     OTR / WEB / INA — uma campanha por evento, ativa nos 30 dias antes
 *   digital    qualquer outro prefixo — sempre ativas
 *   organico   ORG ou "Negado Cliente" — sem custo
 *   indicacao  IND, "F&F" ou "MGM" — sem custo
 *
 * Moeda: origem meta usa as contas "Dollar 1"/"Dollar 2" (USD); origem google
 * usa "Conta BRL" (BRL). Assim valem as duas regras do código: a de 2025
 * (por origem) e a de 2026 em diante (por conta).
 */
final class Marketing
{
    public static function gerar(Contexto $ctx): void
    {
        $ctx->campanhas = array_merge(
            self::campanhasDeEvento($ctx),
            self::campanhasFixas($ctx),
        );
        self::gerarCustos($ctx);
    }

    /** Uma campanha da classe pedida que esteja ativa no dia (ou null). */
    public static function campanhaPara(Contexto $ctx, DateTimeImmutable $dia, string $classe): ?array
    {
        $data = $dia->format('Y-m-d');
        $ativas = array_filter(
            $ctx->campanhas,
            fn($c) => $c['classe'] === $classe && $c['inicio'] <= $data && $c['fim'] >= $data
        );
        return $ativas ? $ctx->sorteio->um($ativas) : null;
    }

    private static function campanhasDeEvento(Contexto $ctx): array
    {
        $cfg = $ctx->config['marketing'];
        $inicioJanela = $ctx->calendario->inicio()->format('Y-m-d');
        $campanhas = [];
        foreach ($ctx->base->linhas('evento') as $id => $evento) {
            $data = new DateTimeImmutable($evento['data_evento']);
            $inicioDivulgacao = $data->modify("-{$cfg['diasDivulgacaoEvento']} days")->format('Y-m-d');
            $campanhas[] = [
                'nome' => $evento['nomeForm_evento'],
                'classe' => 'evento',
                'form' => $evento['nomeForm_evento'],
                'idEvento' => $id,
                // Os primeiros eventos começariam a divulgar antes da janela de dados.
                'inicio' => max($inicioDivulgacao, $inicioJanela),
                'fim' => $evento['data_evento'],
                'origem' => $id % 2 === 0 ? 'meta' : 'google',
                'conta' => $id % 4 === 0 ? 'Dollar 2' : 'Dollar 1',
                'gastoDiario' => $cfg['gastoDiarioEvento'],
                'conjuntos' => $cfg['conjuntosEvento'],
            ];
        }
        return $campanhas;
    }

    private static function campanhasFixas(Contexto $ctx): array
    {
        $cfg = $ctx->config['marketing'];
        $inicio = $ctx->calendario->inicio()->format('Y-m-d');
        $fim = $ctx->hoje->format('Y-m-d');
        $campanhas = [];
        foreach ($cfg['digitais'] as $i => $digital) {
            $campanhas[] = [
                'nome' => $digital['nome'],
                'classe' => 'digital',
                'form' => $cfg['formDigital'],
                'idEvento' => null,
                'inicio' => $inicio,
                'fim' => $fim,
                'origem' => $digital['origem'],
                'conta' => $i % 2 === 0 ? 'Dollar 1' : 'Dollar 2',
                'gastoDiario' => $cfg['gastoDiarioDigital'],
                'conjuntos' => $digital['conjuntos'],
            ];
        }
        foreach (['organico' => $cfg['organicas'], 'indicacao' => $cfg['indicacoes']] as $classe => $nomes) {
            foreach ($nomes as $nome) {
                $campanhas[] = [
                    'nome' => $nome, 'classe' => $classe, 'form' => $nome, 'idEvento' => null,
                    'inicio' => $inicio, 'fim' => $fim, 'conjuntos' => [],
                ];
            }
        }
        return $campanhas;
    }

    /** Uma linha por anúncio por dia ativo (até hoje), só nas campanhas pagas. */
    private static function gerarCustos(Contexto $ctx): void
    {
        $cfg = $ctx->config['marketing'];
        $cambio = array_column($ctx->base->linhas('cambio'), 'valor_cambio', 'data_cambio');
        $hoje = $ctx->hoje->format('Y-m-d');
        $s = $ctx->sorteio;

        foreach ($ctx->campanhas as $c) {
            if (!in_array($c['classe'], ['evento', 'digital'], true)) {
                continue;
            }
            $brl = $c['origem'] === 'google';
            for ($dia = new DateTimeImmutable($c['inicio']); $dia->format('Y-m-d') <= min($c['fim'], $hoje); $dia = $dia->modify('+1 day')) {
                $data = $dia->format('Y-m-d');
                foreach ($c['conjuntos'] as $conjunto) {
                    foreach ($conjunto['anuncios'] as $anuncio) {
                        $usd = $s->decimal($c['gastoDiario'][0], $c['gastoDiario'][1]);
                        $semIdentificador = $c['classe'] === 'evento' && $s->chance($cfg['chanceSemIdentificador']);
                        $ctx->base->inserir('custoMkt', [
                            'data_custoMkt' => $data,
                            'valor_custoMkt' => $brl ? round($usd * $cambio[$data], 2) : $usd,
                            'origem_custoMkt' => $c['origem'],
                            'conta_custoMkt' => $brl ? 'Conta BRL' : $c['conta'],
                            'identificador_custoMkt' => $semIdentificador ? '' : $c['form'],
                            'nomeCampanha_custoMkt' => $c['nome'],
                            'nomeConjunto_custoMkt' => $conjunto['nome'],
                            'nomeAnuncio_custoMkt' => $anuncio,
                            'clicks_custoMkt' => $s->inteiro($cfg['cliquesDiarios'][0], $cfg['cliquesDiarios'][1]),
                        ]);
                    }
                }
            }
        }
    }
}
