<?php

/**
 * Etapa 12: pesquisas — NPS dos eventos e a planilha de consultas do time.
 *
 * No sistema original as duas vinham de planilhas do Google; aqui são as
 * tabelas npsEvento e planilhaConsulta.
 *
 * NPS: participantes do On The Road respondem depois do evento, separados
 * entre quem comprou consulta e quem não comprou.
 *
 * Planilha: o time do Comercial 2 anota as consultas realizadas. O
 * ConsultaCom2 confere a planilha contra o CRM, então parte das linhas tem
 * e-mail digitado errado (casa só pelo nome) e algumas não existem no CRM.
 */
final class Pesquisas
{
    private const NOTAS = ['expGeral', 'local', 'coffee', 'palestrante', 'recepcao', 'brindes', 'abordagem', 'indicacao'];

    public static function gerar(Contexto $ctx): void
    {
        self::nps($ctx);
        self::planilha($ctx);
    }

    private static function nps(Contexto $ctx): void
    {
        $cfg = $ctx->config['nps'];
        $s = $ctx->sorteio;
        $nota = fn(float $media, float $desvio) => (int) max(0, min(10, round($s->normal($media, $desvio))));

        foreach ($ctx->base->linhas('evento') as $id => $evento) {
            $data = new DateTimeImmutable($evento['data_evento']);
            if (!str_starts_with($evento['nome_evento'], 'On The Road') || $data > $ctx->hoje) {
                continue;
            }
            for ($i = $s->inteiro($cfg['respostasPorEvento'][0], $cfg['respostasPorEvento'][1]); $i > 0; $i--) {
                $comprou = $s->chance($cfg['chanceComprou']);
                [$media, $desvio] = $comprou ? [9, 1] : [7, 1.8];
                $resposta = [
                    'idEvento_npsEvento' => $id,
                    'tipo_npsEvento' => $comprou ? 'comprou' : 'nao_comprou',
                    'respondidoEm_npsEvento' => min($ctx->calendario->momento($data->modify('+' . $s->inteiro(0, 3) . ' days'), $s), $ctx->agora),
                    'nome_npsEvento' => Pessoas::novoCliente($ctx)['nome'],
                    'cidade_npsEvento' => $evento['cidade_evento'],
                ];
                foreach (self::NOTAS as $campo) {
                    $resposta["{$campo}_npsEvento"] = $nota($media, $desvio);
                }
                if ($comprou) {
                    $resposta['atendimento_npsEvento'] = $nota(9, 1);
                    $resposta['fatorFechamento_npsEvento'] = $s->um($cfg['fatoresFechamento']);
                } else {
                    $resposta['chanceImigrar_npsEvento'] = $nota(6, 2.5);
                    $resposta['motivoNaoFechou_npsEvento'] = $s->um($cfg['motivosNaoFechou']);
                    $resposta['interessePosEvento_npsEvento'] = $s->ponderado($cfg['interessePosEvento']);
                }
                $ctx->base->inserir('npsEvento', $resposta);
            }
        }
    }

    private static function planilha(Contexto $ctx): void
    {
        $cfg = $ctx->config['planilha'];
        $s = $ctx->sorteio;
        $equipe = ConstantesDoCodigo::ler('consultacom2', 'EQUIPE');
        $desde = $ctx->hoje->modify('first day of last month')->format('Y-m-d');
        $hoje = $ctx->hoje->format('Y-m-d');

        $posicao = 0;
        $anotadas = 0;
        foreach ($ctx->base->linhas('dealV2') as $d) {
            $realizada = $d['dataConsultaRealizada_dealV2'] ?? null;
            if (!str_starts_with($d['faseId_dealV2'], 'C5:') || $realizada === null || $realizada < $desde
                || !in_array($d['responsavel_dealV2'], $equipe, true)) {
                continue;
            }
            // Uma a cada N consultas não foi anotada (aparece como "Pendente" no
            // ConsultaCom2). As de hoje sempre entram: a tela abre no mês corrente.
            if ($realizada !== $hoje && ++$posicao % $cfg['umaACadaSemAnotacao'] === 0) {
                continue;
            }
            // Uma a cada N linhas tem o e-mail digitado errado (casa só pelo nome: "OK (nome)").
            $email = ++$anotadas % $cfg['umaACadaComEmailErrado'] === 0 ? 'x' . $d['email_dealV2'] : $d['email_dealV2'];
            self::linha($ctx, $d['nome_dealV2'], $email, $realizada, $d['responsavel_dealV2']);
        }

        for ($i = 0; $i < $cfg['linhasForaDoCrm']; $i++) {
            $cliente = Pessoas::novoCliente($ctx);
            self::linha($ctx, $cliente['nome'], $cliente['email'], $hoje, $s->um($equipe));
        }
    }

    private static function linha(Contexto $ctx, string $nome, string $email, string $data, string $comercial): void
    {
        $ctx->base->inserir('planilhaConsulta', [
            'nomeCliente_planilhaConsulta' => $nome,
            'email_planilhaConsulta' => $email,
            'dataConsulta_planilhaConsulta' => $data,
            'nomeComercial_planilhaConsulta' => $comercial,
            'obs_planilhaConsulta' => $ctx->sorteio->chance(0.2) ? $ctx->sorteio->um(['Cliente pediu retorno', 'Remarcou uma vez', 'Veio com o cônjuge']) : '',
        ]);
    }
}
