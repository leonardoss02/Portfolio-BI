<?php

/**
 * Etapa 11: Gestão de Eventos — fornecedores e custo físico de cada evento.
 *
 * Cada On The Road tem um hotel e os demais fornecedores (videomaker,
 * transporte, frete, material e, quando o A/V é terceirizado, equipamento).
 * Tudo fica na tabela larga fornecedorEvento, separado por categoria; o
 * mapeamento de colunas está em lib/hotel/MapaFornecedor.php. Valores em reais.
 *
 * O custo físico do evento (custoEvento) é a soma dos fornecedores, como o
 * módulo calcula ao salvar.
 */
final class Fornecedores
{
    public static function gerar(Contexto $ctx): void
    {
        foreach ($ctx->base->linhas('evento') as $id => $evento) {
            if (!str_starts_with($evento['nome_evento'], 'On The Road')) {
                continue; // webinar e 1:1 não têm hotel
            }
            $total = self::fornecedoresDoEvento($ctx, $id, $evento);
            $ctx->base->inserir('custoEvento', [
                'id_evento' => $id,
                'nome_custoEvento' => $evento['nome_evento'],
                'identificador_custoEvento' => $evento['nomeForm_evento'],
                'data_custoEvento' => $evento['data_evento'],
                'valor_custoEvento' => round($total, 2),
                'id_ordemCompra' => 0,
            ]);
        }
    }

    /** Insere o hotel e os demais fornecedores do evento e devolve o custo total. */
    private static function fornecedoresDoEvento(Contexto $ctx, int $idEvento, array $evento): float
    {
        $s = $ctx->sorteio;
        $data = new DateTimeImmutable($evento['data_evento']);
        $passado = $data <= $ctx->hoje;
        $assinatura = $data->modify('-' . $s->inteiro(20, 60) . ' days');
        $terceirizado = $s->chance(0.3);
        $comum = fn(string $categoria, string $dataLinha, float $custo) => [
            'categoria_fornecedorEvento' => $categoria,
            'idEvento_fornecedorEvento' => $idEvento,
            'data_fornecedorEvento' => $dataLinha,
            'custo_fornecedorEvento' => $custo,
            'idLegado_fornecedorEvento' => null,
            'criadoEm_fornecedorEvento' => $assinatura->setTime(10, 0),
            'atualizadoEm_fornecedorEvento' => $assinatura->setTime(10, 0),
            'deletado_fornecedorEvento' => 0,
        ];
        $linhas = [];

        $situacao = $passado
            ? ($s->chance(0.02) ? 'cancelado' : 'assinado')
            : $s->um(['negociacao', 'aguardando', 'assinado']);
        $documentado = $passado && $s->chance(0.9);
        $linhas[] = $comum('hotel', $evento['data_evento'], $s->decimal(5000, 15000)) + [
            'nome_fornecedorEvento' => $evento['local_evento'],
            'uf_fornecedorEvento' => $ctx->config['eventos']['cidades'][$evento['cidade_evento']] ?? null,
            'capacidade_fornecedorEvento' => $s->inteiro(8, 30) * 10,
            'tipoPagamento_fornecedorEvento' => $s->um(['antecipado', 'faturado', 'parcial', 'credito']),
            'avaliacao_fornecedorEvento' => $passado ? $s->inteiro(3, 5) : 0,
            'situacaoContrato_fornecedorEvento' => $situacao,
            'dataAssinatura_fornecedorEvento' => $situacao === 'assinado' ? $assinatura->format('Y-m-d') : null,
            'nf_fornecedorEvento' => $documentado ? 1 : 0,
            'dataNf_fornecedorEvento' => $documentado ? $data->modify('+' . $s->inteiro(1, 10) . ' days')->format('Y-m-d') : null,
            'oc_fornecedorEvento' => $documentado ? 1 : 0,
            'dataOc_fornecedorEvento' => $documentado ? $assinatura->modify('+' . $s->inteiro(1, 7) . ' days')->format('Y-m-d') : null,
            'numeroOc_fornecedorEvento' => $documentado ? 'OC-' . $s->inteiro(1000, 9999) : null,
            'reservaQuarto_fornecedorEvento' => 'RES-' . $s->inteiro(10000, 99999),
            'quartoConfirmado_fornecedorEvento' => $passado || $s->chance(0.5) ? 1 : 0,
            'statusEntrega_fornecedorEvento' => $passado ? $s->ponderado(['no_prazo' => 80, 'atrasado' => 12, 'problema' => 8]) : 'pendente',
            'responsavelAv_fornecedorEvento' => $terceirizado ? 'terceirizado' : 'hotel',
        ];

        $pago = fn() => $passado ? $s->ponderado(['pago' => 85, 'parcial' => 10, 'pendente' => 5]) : $s->um(['aguardando', 'pendente']);
        if ($terceirizado) {
            for ($i = $s->inteiro(1, 2); $i > 0; $i--) {
                $linhas[] = $comum('equipamento', $evento['data_evento'], $s->decimal(800, 3000)) + [
                    'nome_fornecedorEvento' => 'Áudio e Vídeo ' . $s->um($ctx->config['clientes']['sobrenomes']),
                    'contato_fornecedorEvento' => $s->um($ctx->config['clientes']['nomes']),
                    'status_fornecedorEvento' => $pago(),
                    'avaliacao_fornecedorEvento' => $passado ? $s->inteiro(3, 5) : 0,
                    'statusEntrega_fornecedorEvento' => $passado ? $s->ponderado(['no_prazo' => 85, 'atrasado' => 10, 'problema' => 5]) : '',
                    'horaEntrega_fornecedorEvento' => '0' . $s->inteiro(6, 9) . ':00',
                ];
            }
        }
        $linhas[] = $comum('videomaker', $evento['data_evento'], $s->decimal(600, 2000)) + [
            'nome_fornecedorEvento' => $s->um($ctx->config['clientes']['nomes']) . ' ' . $s->um($ctx->config['clientes']['sobrenomes']) . ' Filmes',
            'contato_fornecedorEvento' => sprintf('(11) 9%04d-%04d', $s->inteiro(0, 9999), $s->inteiro(0, 9999)),
            'tipoMidia_fornecedorEvento' => $s->um(['video', 'foto', 'video+foto']),
            'status_fornecedorEvento' => $pago(),
        ];
        for ($i = $s->inteiro(1, 3); $i > 0; $i--) {
            $viagem = $data->modify('-1 day')->format('Y-m-d');
            $linhas[] = $comum('transporte', $viagem, $s->decimal(400, 2500)) + [
                'modal_fornecedorEvento' => $s->ponderado(['aviao' => 70, 'onibus' => 20, 'uber' => 10]),
                'companhia_fornecedorEvento' => $s->um(['Aerovia Horizonte', 'VoaSul', 'Alto Voo', 'Expresso Serra Azul']),
                'trecho_fornecedorEvento' => 'São Paulo → ' . $evento['cidade_evento'],
                'dataViagem_fornecedorEvento' => $viagem,
                'quantidade_fornecedorEvento' => $s->inteiro(1, 3),
                'bilhete_fornecedorEvento' => strtoupper(substr(md5((string) $s->inteiro(1, PHP_INT_MAX)), 0, 6)),
                'status_fornecedorEvento' => $passado ? 'pago' : $s->um(['pendente', 'reservado']),
            ];
        }
        $envio = $data->modify('-' . $s->inteiro(5, 10) . ' days');
        $linhas[] = $comum('frete', $envio->format('Y-m-d'), $s->decimal(300, 1200)) + [
            'documento_fornecedorEvento' => 'CT-e ' . $s->inteiro(100000, 999999),
            'dataEnvio_fornecedorEvento' => $envio->format('Y-m-d'),
            'dataEntrega_fornecedorEvento' => $envio->modify('+3 days')->format('Y-m-d'),
            'status_fornecedorEvento' => $passado ? 'entregue' : 'pendente',
        ];
        $linhas[] = $comum('material', $evento['data_evento'], $s->decimal(300, 1500)) + [
            'quantidade_fornecedorEvento' => $s->inteiro(100, 400),
            'avulso_fornecedorEvento' => 0,
            'status_fornecedorEvento' => $passado ? 'entregue' : 'pendente',
        ];
        for ($i = $s->inteiro(0, 2); $i > 0; $i--) {
            $linhas[] = $comum('custoExtra', $evento['data_evento'], $s->decimal(300, 1500)) + [
                'nome_fornecedorEvento' => $s->um(['Coffee break extra', 'Banner e sinalização', 'Estacionamento', 'Internet dedicada']),
                'status_fornecedorEvento' => $pago(),
            ];
        }

        $total = 0.0;
        foreach ($linhas as $linha) {
            $ctx->base->inserir('fornecedorEvento', $linha);
            $total += $linha['custo_fornecedorEvento'];
        }
        return $total;
    }
}
