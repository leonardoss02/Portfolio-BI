<?php

/**
 * Etapa 8: faturas (espelho do QuickBooks), em dólar.
 *
 * Cada combo é cobrado numa entrada de 20% mais parcelas mensais. A situação
 * de cada fatura depende do vencimento: as vencidas foram pagas ou estão em
 * atraso; as que ainda vão vencer estão "Em dia" com saldo em aberto. Parte
 * das faturas é de clientes sem card no CRM (como acontece na prática).
 */
final class Financeiro
{
    public static function gerar(Contexto $ctx): void
    {
        $cfg = $ctx->config['financeiro'];
        $clientes = [];

        foreach ($ctx->base->linhas('dealV2') as $combo) {
            if (!in_array($combo['faseId_dealV2'], ['C5:WON', 'C1:WON', 'C29:WON', 'C31:WON'], true)) {
                continue;
            }
            $cliente = $clientes[$combo['contactId_dealV2']] ??= $ctx->base->novoIdBitrix('clienteQb');
            self::cobrar($ctx, $cliente, $combo['nome_dealV2'], $combo['idBitrix_dealV2'],
                $combo['dataContratoComboAssinado_dealV2'], (float) $combo['valorComboLiquido_dealV2']);
        }

        // Clientes sem card no CRM: proporção fixa do total de faturas.
        $comCard = $ctx->base->total('invoice');
        $alvo = (int) round($comCard * $cfg['proporcaoSemCard'] / (1 - $cfg['proporcaoSemCard']));
        $s = $ctx->sorteio;
        while ($ctx->base->total('invoice') - $comCard < $alvo) {
            $contrato = $ctx->calendario->inicio()->modify('+' . $s->inteiro(0, (int) $ctx->calendario->inicio()->diff($ctx->hoje)->format('%a')) . ' days');
            self::cobrar($ctx, $ctx->base->novoIdBitrix('clienteQb'), Pessoas::novoCliente($ctx)['nome'], null,
                $contrato->format('Y-m-d'), $s->decimal(5000, 20000));
        }
    }

    /** Entrada de 20% na assinatura e o restante em parcelas mensais. */
    private static function cobrar(Contexto $ctx, string $cliente, string $nome, ?string $idDeal, string $contrato, float $valor): void
    {
        $parcelas = $ctx->sorteio->inteiro(3, 10);
        $entrada = round($valor * 0.2, 2);
        $parcela = round(($valor - $entrada) / $parcelas, 2);
        $inicio = new DateTimeImmutable($contrato);

        self::fatura($ctx, $cliente, $nome, $idDeal, $inicio, $entrada);
        for ($i = 1; $i <= $parcelas; $i++) {
            self::fatura($ctx, $cliente, $nome, $idDeal, $inicio->modify("+$i month"), $parcela);
        }
    }

    private static function fatura(Contexto $ctx, string $cliente, string $nome, ?string $idDeal, DateTimeImmutable $vencimento, float $valor): void
    {
        $cfg = $ctx->config['financeiro'];
        $s = $ctx->sorteio;
        $status = 'Em dia';
        $saldo = $valor;
        $pagamento = null;

        if ($vencimento <= $ctx->hoje) {
            if ($s->chance($cfg['chancePaga'])) {
                $atraso = $s->chance($cfg['chancePagaComAtraso']) ? $s->inteiro(30, 60) : max(-5, (int) round($s->normal(2, 5)));
                $pagamento = min($vencimento->modify("$atraso days"), $ctx->hoje)->format('Y-m-d');
                $saldo = 0.0;
            } else {
                $diasVencida = (int) $vencimento->diff($ctx->hoje)->format('%a');
                $status = match (true) {
                    $s->chance($cfg['chanceCongelada']) => 'Congelado',
                    $diasVencida > 60 => 'Inadimplente',
                    default => 'Atrasado',
                };
                $saldo = $s->chance(0.1) ? round($valor / 2, 2) : $valor;
            }
        }

        $ctx->base->inserir('invoice', [
            'id_empresa' => 1,
            'idQB_invoice' => (string) (1000 + $ctx->base->total('invoice') + 1),
            'customerQB_invoice' => $cliente,
            'nomeQB_invoice' => $nome,
            'amountQB_invoice' => $valor,
            'ballancedQB_invoice' => $saldo,
            'dueDateQB_invoice' => $vencimento->format('Y-m-d'),
            'dataQBPagamento_invoice' => $pagamento,
            'status_invoice' => $status,
            'idDealBitrix_invoice' => $idDeal,
        ]);
    }
}
