<?php

/**
 * Etapa 9: conversas e mensagens do atendimento (espelho do Chatwoot).
 *
 * Parte dos leads conversa pelo WhatsApp ou Instagram. A conversa ganha os
 * rótulos de venda quando o lead comprou cadeira ou consulta, e alguns
 * clientes nunca recebem resposta (o Chatwoot grava NULL ou a data
 * "1969-12-31 21:00:00" nesse caso).
 */
final class Atendimento
{
    private const SEM_RESPOSTA = '1969-12-31 21:00:00';

    public static function gerar(Contexto $ctx): void
    {
        $cfg = $ctx->config['atendimento'];
        $s = $ctx->sorteio;

        // Rótulos de venda por lead.
        $vendas = [];
        foreach ($ctx->base->linhas('dealV2') as $d) {
            if (isset($d['_idLead']) && $d['faseId_dealV2'] === 'C9:WON') {
                $vendas[$d['_idLead']]['venda-ingresso'] = true;
            } elseif (isset($d['_idLead']) && in_array($d['faseId_dealV2'], ['WON', 'C11:WON'], true)) {
                $vendas[$d['_idLead']]['venda-consulta'] = true;
            }
        }

        foreach ($ctx->base->linhas('leadV2') as $id => $lead) {
            if ($s->chance($cfg['chanceConversa'])) {
                $inicio = $lead['dataUltForm_leadV2']->modify('+' . $s->inteiro(0, 120) . ' minutes');
                self::conversa($ctx, $lead, min($inicio, $ctx->agora), array_keys($vendas[$id] ?? []));
            }
        }

        // O dashboard abre no mês corrente: garante movimento também no dia de hoje.
        $leads = array_values($ctx->base->linhas('leadV2'));
        for ($i = 0; $i < $cfg['minimoConversasHoje']; $i++) {
            self::conversa($ctx, $s->um($leads), $ctx->calendario->momento($ctx->hoje, $s, 8, 12), [], true);
        }
    }

    private static function conversa(Contexto $ctx, array $lead, DateTimeImmutable $criadaEm, array $rotulos, bool $doTime = false): void
    {
        $cfg = $ctx->config['atendimento'];
        $s = $ctx->sorteio;
        $equipe = $ctx->config['equipe'];

        $resposta = match (true) {
            !$doTime && $s->chance($cfg['chanceSemResposta']) => null,
            !$doTime && $s->chance($cfg['chanceSentinela']) => self::SEM_RESPOSTA,
            default => min($criadaEm->modify('+' . $s->inteiro(1, 240) . ' minutes'), $ctx->agora),
        };
        if ($s->chance(0.15)) {
            $rotulos[] = 'lead-quente';
        }

        $id = $ctx->base->inserir('conversaCW', [
            'idCw_conversaCW' => (int) $ctx->base->novoIdBitrix('conversa'),
            'idLeadBitrix_conversaCW' => (int) $lead['idBitrix_leadV2'],
            'responsavel_conversaCW' => $doTime || $s->chance(0.9) ? $s->um($equipe['atendimento']) : $equipe['atendimento_fora'],
            'status_conversaCW' => $s->ponderado(['resolved' => 55, 'open' => 25, 'pending' => 12, 'snoozed' => 8]),
            'rotulos_conversaCW' => implode(',', $rotulos),
            'canal_conversaCW' => $s->chance(0.85) ? 'Channel::Whatsapp' : 'Channel::Instagram',
            'nomeRemetenteMeta_conversaCW' => $lead['nome_leadV2'],
            'telefoneRemetenteMeta_conversaCW' => $lead['telefoneTexto_leadV2'],
            'criadoEm_conversaCW' => $criadaEm,
            'primeiraRespostaEm_conversaCW' => $resposta,
            'ultimaAtividadeEm_conversaCW' => min($criadaEm->modify('+' . $s->inteiro(1, 72) . ' hours'), $ctx->agora),
        ]);

        // Mensagens: a primeira é sempre do cliente na maioria das conversas.
        $recebeuMensagem = $s->chance($cfg['chanceMensagemRecebida']);
        for ($i = 0, $n = $s->inteiro(2, 10); $i < $n; $i++) {
            $recebida = $i === 0 ? $recebeuMensagem : ($recebeuMensagem && $i % 2 === 0);
            $ctx->base->inserir('msgCW', [
                'id_conversaCW' => $id,
                'tipo_msgCW' => $recebida ? $s->um(['incoming', '0']) : $s->um(['outgoing', '1']),
                'criadoEm_msgCW' => min($criadaEm->modify("+$i minutes"), $ctx->agora),
            ]);
        }
    }
}
