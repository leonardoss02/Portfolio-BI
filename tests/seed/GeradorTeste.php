<?php

require_once __DIR__ . '/apoio.php';

return [
    'nenhum horário da base passa de agora' => function () {
        $agora = new DateTimeImmutable('2026-11-09 10:30:00');
        $ctx = Contexto::criar('2026-11-09', configDeTeste(0.1), $agora);
        Gerador::rodar($ctx);
        $colunas = [
            'leadV2' => ['dataUltForm_leadV2'],
            'dealV2' => ['criadoEm_dealV2', 'dataVenda_dealV2', 'dataUltForm_dealV2'],
            'conversaCW' => ['criadoEm_conversaCW', 'primeiraRespostaEm_conversaCW', 'ultimaAtividadeEm_conversaCW'],
            'msgCW' => ['criadoEm_msgCW'],
            'lead_tag' => ['dataCadastro'],
            'npsEvento' => ['respondidoEm_npsEvento'],
            'orcamentoV2' => ['criadoEm_orcamentoV2'],
        ];
        foreach ($colunas as $tabela => $campos) {
            foreach ($ctx->base->linhas($tabela) as $linha) {
                foreach ($campos as $campo) {
                    $valor = $linha[$campo] ?? null;
                    if ($valor instanceof DateTimeInterface) {
                        afirmar($valor <= $agora, "$tabela.$campo no futuro: " . $valor->format('Y-m-d H:i'));
                    }
                }
            }
        }
    },

    'a mesma data gera exatamente a mesma base' => function () {
        $assinatura = function (): string {
            $ctx = contextoDeTeste('2026-11-09', 0.05);
            Gerador::rodar($ctx);
            $partes = [];
            foreach ($ctx->base->tabelas() as $tabela) {
                $partes[$tabela] = md5(serialize($ctx->base->linhas($tabela)));
            }
            return json_encode($partes);
        };
        afirmarIgual($assinatura(), $assinatura(), 'duas gerações com a mesma data diferem');
    },
];
