<?php

/**
 * Etapa 6: segmento Dentistas (pipeline C33).
 *
 * Segmento novo, que começou em 31/08/2026. Os cards nascem direto no C33
 * (sem lead), vindos dos "Encontros de Dentistas". Parte confirma presença
 * no encontro, parte faz a consulta e uma fração assina contrato.
 */
final class Dentistas
{
    public static function gerar(Contexto $ctx): void
    {
        $cfg = $ctx->config['dentistas'];
        $hoje = $ctx->hoje->format('Y-m-d');
        $s = $ctx->sorteio;

        for ($dia = new DateTimeImmutable($cfg['inicio']); $dia <= $ctx->hoje; $dia = $dia->modify('+1 day')) {
            if ((int) $dia->format('N') >= 6) {
                continue; // só dias úteis
            }
            $quantidade = (int) round($ctx->escalar($cfg['porDiaUtil'] * $ctx->calendario->peso($dia)));
            for ($i = 0; $i < $quantidade; $i++) {
                $criadoEm = $ctx->calendario->momento($dia, $s);
                $cliente = Pessoas::novoCliente($ctx);
                $idContato = $ctx->base->novoIdBitrix('contato');
                $ctx->base->inserir('contactV2', [
                    'idBitrix_contactV2' => $idContato,
                    'primeiroNome_contactV2' => $cliente['primeiroNome'],
                    'sobrenome_contactV2' => $cliente['sobrenome'],
                ]);

                $card = [
                    'faseId_dealV2' => 'C33:NEW',
                    'fase_dealV2' => 'Novo contato',
                    'contactId_dealV2' => $idContato,
                    'nome_dealV2' => $cliente['nome'],
                    'email_dealV2' => $cliente['email'],
                    'telefone_dealV2' => $cliente['telefone'],
                    'responsavel_dealV2' => $s->um($ctx->config['equipe']['especialistas']),
                    'criadoEm_dealV2' => $criadoEm,
                    'dataUltForm_dealV2' => $criadoEm,
                    'dentistaEvento_dealV2' => 'Encontro de Dentistas ' . $s->um($cfg['cidades']),
                    'utmTerm_dealV2' => $s->um($cfg['anuncios']),
                    'profissao_dealV2' => $s->um($cfg['profissoes']),
                    'formacaoAcademica_dealV2' => $s->um($ctx->config['leads']['formacoes']),
                    'checkin_dealV2' => $s->chance($cfg['chanceCheckin']) ? '1' : null,
                ];

                $realizada = $dia->modify('+' . $s->inteiro(3, 20) . ' days')->format('Y-m-d');
                if ($s->chance($cfg['chanceConsulta']) && $realizada <= $hoje) {
                    $card['dataConsultaRealizada_dealV2'] = $realizada;
                    $card['fase_dealV2'] = 'Consulta realizada';
                    $contrato = (new DateTimeImmutable($realizada))->modify('+' . $s->inteiro(5, 30) . ' days')->format('Y-m-d');
                    if ($s->chance($cfg['chanceContratoAposConsulta']) && $contrato <= $hoje) {
                        $card['faseId_dealV2'] = 'C33:WON';
                        $card['fase_dealV2'] = 'Contrato assinado';
                        $card['dataContratoComboAssinado_dealV2'] = $contrato;
                    }
                }
                Cards::novo($ctx, $card);
            }
        }
    }
}
