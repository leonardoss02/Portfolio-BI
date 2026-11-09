<?php

/**
 * Etapa 10: a jornada do cliente no checkout (tabela lead_tag).
 *
 * Cada passo do lead vira uma tag com data e hora: lead criado, carrinho
 * acessado, forma de pagamento escolhida, pagamento confirmado, carrinho
 * abandonado, lead descartado. Quem comprou cadeira ou consulta percorre o
 * caminho até o pagamento; os demais seguem uma cadeia de probabilidades
 * (a mesma do modo demonstração de paginas/helenacastro/jornada.php), sem
 * nunca chegar ao pagamento.
 */
final class Jornada
{
    public static function gerar(Contexto $ctx): void
    {
        $cfg = $ctx->config['jornada'];
        $equipe = $ctx->config['equipe'];
        $s = $ctx->sorteio;
        $responsaveis = array_merge([$equipe['helena'], $equipe['jornada_extra']], $equipe['comercial1_digital']);

        $vendas = [];
        foreach ($ctx->base->linhas('dealV2') as $d) {
            if (isset($d['_idLead']) && in_array($d['faseId_dealV2'], ['C9:WON', 'C11:WON'], true)) {
                $vendas[$d['_idLead']] = $d;
            }
        }

        $primeiro = true;
        foreach ($ctx->base->linhas('leadV2') as $id => $lead) {
            $entra = in_array($lead['responsavel_leadV2'], $responsaveis, true)
                || ($lead['_canal'] === 'evento' && $s->chance($cfg['chanceLeadDeEvento']));
            // O primeiro lead sempre tem jornada: o teste de fumaça abre a trilha dele.
            if ($entra || $primeiro || isset($vendas[$id])) {
                self::trilha($ctx, (int) $lead['idBitrix_leadV2'], $id, $lead['dataUltForm_leadV2'], $vendas[$id] ?? null);
            }
            $primeiro = false;
        }

        // Leads que ainda não foram espelhados no leadV2 (só existem na jornada).
        $extras = (int) round($ctx->base->total('leadV2') * $cfg['proporcaoSoNaJornada']);
        foreach ($s->chaves($ctx->base->linhas('leadV2'), max(1, $extras)) as $idAleatorio) {
            $momento = $ctx->base->linha('leadV2', $idAleatorio)['dataUltForm_leadV2'];
            self::trilha($ctx, (int) $ctx->base->novoIdBitrix('lead'), null, $momento, null);
        }
    }

    private static function trilha(Contexto $ctx, int $idBitrix, ?int $idLead, DateTimeImmutable $inicio, ?array $venda): void
    {
        $s = $ctx->sorteio;
        $cfg = $ctx->config['jornada'];
        $produto = $venda && $venda['faseId_dealV2'] === 'C9:WON' ? 'Ingresso On The Road · R$ 100,00' : 'Consulta de imigração · R$ 690,00';
        $tags = [['lead-criado', $inicio, $s->um($cfg['origens'])]];

        if ($venda) {
            $confirmado = max($venda['dataVenda_dealV2'], $inicio->modify('+3 minutes'));
            $pagamento = $confirmado->modify('-' . $s->inteiro(1, 2) . ' minutes');
            $acessado = max($inicio->modify('+1 minute'), $pagamento->modify('-' . $s->inteiro(2, 40) . ' minutes'));
            $forma = $s->chance(0.7) ? 'Pix' : 'Cartão de crédito';
            array_push($tags,
                ['carrinho-acessado', $acessado, $produto],
                ['carrinho-pagamento', $pagamento, "Escolheu $forma"],
                ['pagamento-confirmado', $confirmado, $forma . ' ' . substr($produto, strpos($produto, 'R$'))],
            );
        } else {
            $tags = array_merge($tags, self::semCompra($ctx, $inicio, $produto));
        }
        if ($s->chance($cfg['chanceWebinar'])) {
            $tags[] = ['webinar-assistido', $inicio->modify('-' . $s->inteiro(1, 10) . ' days'), 'Webinar Imigração'];
        }

        $limite = $ctx->agora;
        foreach ($tags as [$tag, $quando, $descricao]) {
            if ($quando <= $limite) {
                $ctx->base->inserir('lead_tag', [
                    'idBitrix_lead' => $idBitrix,
                    'id_leadV2' => $idLead,
                    'tag' => $tag,
                    'descricao' => $descricao,
                    'dataCadastro' => $quando,
                ]);
            }
        }
    }

    /** Cadeia de probabilidades de quem não comprou (para no carrinho, abandona ou é descartado). */
    private static function semCompra(Contexto $ctx, DateTimeImmutable $inicio, string $produto): array
    {
        $s = $ctx->sorteio;
        $tags = [];
        $agora = $inicio;
        $estado = 'criado';
        $voltou = false;
        $mais = fn(int $min, int $max, string $unidade) => $agora->modify('+' . $s->inteiro($min, $max) . " $unidade");

        while (true) {
            $proximo = match ($estado) {
                'criado' => $s->ponderado(['acessado' => 50, 'descartado' => 17, 'fim' => 33]),
                'acessado' => $s->ponderado(['pagamento' => 50, 'abandonado' => 38, 'descartado' => 2, 'fim' => 10]),
                'pagamento' => $s->ponderado(['abandonado' => 95, 'fim' => 5]),
                'abandonado' => $voltou
                    ? $s->ponderado(['descartado' => 30, 'fim' => 70])
                    : $s->ponderado(['acessado' => 16, 'descartado' => 14, 'fim' => 70]),
                default => 'fim',
            };
            if ($proximo === 'fim') {
                return $tags;
            }
            if ($estado === 'abandonado' && $proximo === 'acessado') {
                $voltou = true;
            }
            [$agora, $tag, $descricao] = match ($proximo) {
                'acessado' => [$s->chance(0.4) ? $mais(1, 10, 'minutes') : $mais(1, 72, 'hours'), 'carrinho-acessado', $produto],
                'pagamento' => [$mais(2, 40, 'minutes'), 'carrinho-pagamento', 'Escolheu ' . ($s->chance(0.7) ? 'Pix' : 'Cartão de crédito')],
                'abandonado' => [$mais(60, 120, 'minutes'), 'carrinho-abandonado', 'Sem pagamento 1 h depois de entrar no carrinho'],
                'descartado' => [$mais(2, 120, 'hours'), 'lead-descartado', 'Motivo: ' . $s->um($ctx->config['motivosDescarte'])],
            };
            $tags[] = [$tag, $agora, $descricao];
            $estado = $proximo;
        }
    }
}
