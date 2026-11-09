<?php

/**
 * Cria cards (dealV2) a partir de um lead ou de outro card da mesma pessoa.
 *
 * Um cliente tem vários cards (um por pipeline). Todos repetem os dados da
 * pessoa e da origem — contato, UTMs, formulário, evento — porque é assim
 * que o CRM guarda e é por esses campos que os dashboards filtram.
 */
final class Cards
{
    /** Campos copiados de um card para outro da mesma pessoa. */
    private const HERDADOS = [
        'contactId_dealV2', 'nome_dealV2', 'email_dealV2', 'telefone_dealV2', 'tipoLead_dealV2',
        'formPreenchido_dealV2', 'utmCampaign_dealV2', 'utmContent_dealV2', 'utmTerm_dealV2',
        'idEvento_dealV2', 'idAfiliado_dealV2', 'profissao_dealV2', 'formacaoAcademica_dealV2',
        'dataUltForm_dealV2', '_idLead', '_canal',
    ];

    /** Novo card com os dados do lead. $campos completa ou sobrescreve. */
    public static function doLead(Contexto $ctx, array $lead, array $campos): int
    {
        return self::inserir($ctx, array_merge([
            'contactId_dealV2' => $lead['_idContato'],
            'nome_dealV2' => $lead['nome_leadV2'],
            'email_dealV2' => $lead['email_leadV2'],
            'telefone_dealV2' => $lead['telefoneTexto_leadV2'],
            'tipoLead_dealV2' => $lead['tipoLead_leadV2'],
            'formPreenchido_dealV2' => $lead['formPreenchido_leadV2'],
            'utmCampaign_dealV2' => $lead['utmCampaign_leadV2'],
            'utmContent_dealV2' => $lead['utmContent_leadV2'],
            'utmTerm_dealV2' => $lead['utmTerm_leadV2'],
            'idEvento_dealV2' => $lead['idEvento_leadV2'],
            'idAfiliado_dealV2' => $lead['idAfiliado_leadV2'],
            'profissao_dealV2' => $lead['profissao_leadV2'],
            'formacaoAcademica_dealV2' => $lead['formacaoAcademica_leadV2'],
            'dataUltForm_dealV2' => $lead['dataUltForm_leadV2'],
            '_idLead' => $lead['id_leadV2'],
            '_canal' => $lead['_canal'],
        ], $campos));
    }

    /** Novo card da mesma pessoa de $card (ex.: o C5 que nasce da consulta vendida). */
    public static function copiar(Contexto $ctx, array $card, array $campos): int
    {
        $herdados = array_intersect_key($card, array_flip(self::HERDADOS));
        return self::inserir($ctx, array_merge($herdados, $campos));
    }

    /** Card avulso, sem lead nem card de origem (ex.: segmento Dentistas). */
    public static function novo(Contexto $ctx, array $card): int
    {
        return self::inserir($ctx, $card);
    }

    private static function inserir(Contexto $ctx, array $card): int
    {
        foreach (['faseId_dealV2', 'fase_dealV2', 'criadoEm_dealV2'] as $obrigatorio) {
            if (empty($card[$obrigatorio])) {
                throw new InvalidArgumentException("Card sem $obrigatorio.");
            }
        }
        // motivoDescarte é NOT NULL no banco: sem motivo, fica vazio.
        $card += ['motivoDescarte_dealV2' => ''];
        // Nenhum horário depois de agora (ex.: card criado minutos depois de uma venda de hoje).
        foreach (['criadoEm_dealV2', 'dataVenda_dealV2', 'dataUltForm_dealV2'] as $campo) {
            if (($card[$campo] ?? null) instanceof DateTimeInterface && $card[$campo] > $ctx->agora) {
                $card[$campo] = $ctx->agora;
            }
        }
        $card['idBitrix_dealV2'] = $ctx->base->novoIdBitrix('deal');
        return $ctx->base->inserir('dealV2', $card);
    }
}
