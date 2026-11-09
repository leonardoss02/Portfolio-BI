<?php

/**
 * Etapa 3: leads (formulários preenchidos) e contatos.
 *
 * Cada dia recebe um número de leads proporcional ao peso do calendário. Cada
 * lead vem de um canal (evento, digital, orgânico, indicação), de uma campanha
 * do catálogo e é uma pessoa nova (um contato).
 *
 * Anotações internas, que não vão para o banco:
 *   _canal      canal de origem
 *   _idContato  idBitrix do contato, para ligar os cards da mesma pessoa
 *   _campanha   nome da campanha
 *   _dia        dia do lead (Y-m-d)
 */
final class Leads
{
    public static function gerar(Contexto $ctx): void
    {
        $cfg = $ctx->config['leads'];
        foreach ($ctx->calendario->dias() as $dia) {
            $quantidade = (int) round($ctx->escalar($cfg['porDia'] * $ctx->calendario->peso($dia)));
            for ($i = 0; $i < $quantidade; $i++) {
                self::novoLead($ctx, $dia);
            }
        }
    }

    private static function novoLead(Contexto $ctx, DateTimeImmutable $dia): void
    {
        $cfg = $ctx->config['leads'];
        $s = $ctx->sorteio;

        $canal = $s->ponderado($cfg['canais']);
        $campanha = Marketing::campanhaPara($ctx, $dia, $canal);
        if ($campanha === null) {
            // Sem evento divulgando neste dia: o lead chega pelo digital.
            $canal = 'digital';
            $campanha = Marketing::campanhaPara($ctx, $dia, $canal);
        }
        $conjunto = $campanha['conjuntos'] ? $s->um($campanha['conjuntos']) : null;

        $cliente = Pessoas::novoCliente($ctx);
        $idContato = $ctx->base->novoIdBitrix('contato');
        $ctx->base->inserir('contactV2', [
            'idBitrix_contactV2' => $idContato,
            'primeiroNome_contactV2' => $cliente['primeiroNome'],
            'sobrenome_contactV2' => $cliente['sobrenome'],
        ]);

        $qualificado = $s->chance($cfg['chanceQualificado']);
        $descartado = !$qualificado && $s->chance($cfg['chanceDescarteNaoQualificado']);

        $ctx->base->inserir('leadV2', [
            'idBitrix_leadV2' => $ctx->base->novoIdBitrix('lead'),
            'nome_leadV2' => $cliente['nome'],
            'email_leadV2' => $cliente['email'],
            'telefoneTexto_leadV2' => $cliente['telefone'],
            'responsavel_leadV2' => self::responsavel($ctx, $canal),
            'formPreenchido_leadV2' => $campanha['form'],
            'tipoLead_leadV2' => self::tipoLead($ctx, $canal),
            'qualificado_leadV2' => $qualificado ? 'Sim' : 'Não',
            'etapa_leadV2' => $qualificado ? 'Qualificado' : ($descartado ? 'Lead Descartado' : 'Em atendimento'),
            'motivoDescarte_leadV2' => $descartado ? $s->um($ctx->config['motivosDescarte']) : null,
            'utmCampaign_leadV2' => $campanha['nome'],
            'utmContent_leadV2' => $conjunto['nome'] ?? null,
            'utmTerm_leadV2' => $conjunto ? $s->um($conjunto['anuncios']) : null,
            'idEvento_leadV2' => $campanha['idEvento'] !== null ? (string) $campanha['idEvento'] : null,
            'idAfiliado_leadV2' => $s->chance($cfg['chanceAfiliado']) ? sprintf('AF%03d', $s->inteiro(1, 120)) : null,
            'profissao_leadV2' => self::comVazios($ctx, $cfg['profissoes'], ['-' => 0.05, '' => 0.05]),
            'tempoExp_leadV2' => $s->um($cfg['temposExperiencia']),
            'formacaoAcademica_leadV2' => self::comVazios($ctx, $cfg['formacoes'], ['' => 0.04]),
            'dataUltForm_leadV2' => $ctx->calendario->momento($dia, $s),
            '_canal' => $canal,
            '_idContato' => $idContato,
            '_campanha' => $campanha['nome'],
            '_dia' => $dia->format('Y-m-d'),
        ]);
    }

    private static function responsavel(Contexto $ctx, string $canal): string
    {
        $equipe = $ctx->config['equipe'];
        return match ($canal) {
            'evento' => $ctx->sorteio->um($equipe['cadeiras']),
            'digital' => $ctx->sorteio->chance($ctx->config['leads']['chanceHelenaNoDigital'])
                ? $equipe['helena']
                : $ctx->sorteio->um($equipe['comercial1_digital']),
            default => $equipe['jornada_extra'],
        };
    }

    private static function tipoLead(Contexto $ctx, string $canal): ?string
    {
        $tipos = $ctx->config['leads']['tipoLeadPorCanal'][$canal];
        $tipo = $ctx->sorteio->ponderado($tipos);
        return $tipo === '(nulo)' ? null : $tipo;
    }

    /** Sorteia da lista, mas com uma chance fixa de cair num valor "vazio". */
    private static function comVazios(Contexto $ctx, array $valores, array $vazios): string
    {
        foreach ($vazios as $vazio => $chance) {
            if ($ctx->sorteio->chance($chance)) {
                return (string) $vazio;
            }
        }
        return $ctx->sorteio->um($valores);
    }
}
