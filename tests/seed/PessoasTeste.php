<?php

require_once __DIR__ . '/apoio.php';

return [
    'cada painel de vendedor usa um nome da equipe' => function () {
        $equipe = configDeTeste()['equipe'];
        foreach (['beatrizlemos', 'camiladuarte', 'julianaprado', 'rafaeltorres'] as $painel) {
            $nome = ConstantesDoCodigo::ler($painel, 'RESPONSAVEL');
            afirmar(in_array($nome, $equipe['especialistas'], true), "$painel usa '$nome', que não está em especialistas");
        }
        afirmarIgual(ConstantesDoCodigo::ler('helenacastro', 'RESPONSAVEL'), $equipe['helena']);
    },

    'times do código batem com o config' => function () {
        $equipe = configDeTeste()['equipe'];
        afirmarIgual(ConstantesDoCodigo::ler('chatwoot', 'CW_TEAM'), $equipe['atendimento'], 'atendimento');
        afirmarIgual(ConstantesDoCodigo::ler('comercial', 'EQUIPE_CADEIRAS'), $equipe['cadeiras'], 'cadeiras');
        afirmarIgual(ConstantesDoCodigo::ler('comercial', 'EQUIPE_COMERCIAL1_EVENTO'), $equipe['comercial1_evento'], 'comercial1_evento');
        afirmarIgual(ConstantesDoCodigo::ler('comercial', 'EQUIPE_COMERCIAL1_DIGITAL'), $equipe['comercial1_digital'], 'comercial1_digital');
        afirmarIgual(ConstantesDoCodigo::ler('comercial', 'EQUIPE_COMERCIAL2'), $equipe['comercial2'], 'comercial2');
        afirmarIgual(ConstantesDoCodigo::ler('comercial', 'EQUIPE_COMERCIAL3'), $equipe['comercial3'], 'comercial3');
        afirmarIgual(ConstantesDoCodigo::ler('executive', 'ESPECIALISTAS_ANALITICO'), $equipe['especialistas'], 'especialistas');
    },

    'time do ConsultaCom2 existe no config' => function () {
        $equipe = configDeTeste()['equipe'];
        $conhecidos = array_merge($equipe['comercial2'], $equipe['especialistas'], $equipe['comercial2_outros']);
        foreach (ConstantesDoCodigo::ler('consultacom2', 'EQUIPE') as $nome) {
            afirmar(in_array($nome, $conhecidos, true), "'$nome' do ConsultaCom2 não está no config");
        }
    },

    'CSMs da campanha de metas batem com o código' => function () {
        $csm = configDeTeste()['equipe']['csm'];
        $doCodigo = ConstantesDoCodigo::ler('comercial4', 'LIGA_CSM');
        afirmarIgual(array_keys($doCodigo), array_keys($csm));
        foreach ($doCodigo as $id => $primeiroNome) {
            afirmar(str_starts_with($csm[$id], $primeiroNome . ' '), "CSM $id: '{$csm[$id]}' não começa com '$primeiroNome'");
        }
    },

    'nenhum nome da equipe contém vírgula' => function () {
        array_walk_recursive(configDeTeste()['equipe'], function ($nome) {
            afirmar(!str_contains($nome, ','), "nome com vírgula: $nome");
        });
    },

    'novoCliente usa @exemplo.com e não repete e-mail' => function () {
        $ctx = contextoDeTeste();
        $emails = [];
        for ($i = 0; $i < 5000; $i++) {
            $cliente = Pessoas::novoCliente($ctx);
            afirmar(str_ends_with($cliente['email'], '@exemplo.com'), "e-mail fora do domínio: {$cliente['email']}");
            afirmar(preg_match('/^[a-z0-9.]+@exemplo\.com$/', $cliente['email']) === 1, "e-mail com caractere inválido: {$cliente['email']}");
            afirmarIgual($cliente['primeiroNome'] . ' ' . $cliente['sobrenome'], $cliente['nome']);
            afirmar(preg_match('/^\+55 \(\d{2}\) 9\d{4}-\d{4}$/', $cliente['telefone']) === 1, "telefone fora do padrão: {$cliente['telefone']}");
            $emails[$cliente['email']] = true;
        }
        afirmarIgual(5000, count($emails), 'e-mails repetidos');
    },

    'equipe() devolve a lista do papel' => function () {
        $config = configDeTeste();
        afirmarIgual($config['equipe']['cadeiras'], Pessoas::equipe($config, 'cadeiras'));
    },
];
