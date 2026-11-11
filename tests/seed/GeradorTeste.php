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

    'a hora em que se gera não muda o resto da base' => function () {
        $gerar = function (string $agora): Contexto {
            $ctx = Contexto::criar('2026-11-09', configDeTeste(0.05), new DateTimeImmutable($agora));
            Gerador::rodar($ctx);
            return $ctx;
        };
        $manha = $gerar('2026-11-09 10:00:00');
        $noite = $gerar('2026-11-09 23:59:59');
        afirmarIgual(
            array_column($manha->base->linhas('leadV2'), 'nome_leadV2'),
            array_column($noite->base->linhas('leadV2'), 'nome_leadV2'),
            'os leads mudaram com a hora'
        );
        afirmarIgual($manha->base->total('dealV2'), $noite->base->total('dealV2'), 'a quantidade de cards mudou com a hora');
    },

    'horários de hoje não se acumulam todos em agora' => function () {
        $agora = new DateTimeImmutable('2026-11-09 10:30:00');
        $ctx = Contexto::criar('2026-11-09', configDeTeste(0.1), $agora);
        Gerador::rodar($ctx);
        foreach (['conversaCW' => 'criadoEm_conversaCW', 'conversaCW ' => 'ultimaAtividadeEm_conversaCW', 'dealV2' => 'criadoEm_dealV2'] as $tabela => $campo) {
            $exatos = array_filter($ctx->base->linhas(trim($tabela)), fn($l) => ($l[$campo] ?? null) instanceof DateTimeInterface && $l[$campo] == $agora);
            afirmar(count($exatos) <= 2, count($exatos) . " registros de " . trim($tabela) . ".$campo exatamente em agora");
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
