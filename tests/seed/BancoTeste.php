<?php

return [
    'lerEnv aceita CRLF, comentários e aspas' => function () {
        $arquivo = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($arquivo, "A=1\r\n# comentário\r\n\r\nB=\"dois\"\r\nC = três \r\n");
        try {
            afirmarIgual(['A' => '1', 'B' => 'dois', 'C' => 'três'], Banco::lerEnv($arquivo));
        } finally {
            unlink($arquivo);
        }
    },

    'montarInsert ignora chaves com _' => function () {
        $sql = Banco::montarInsert('evento', [['nome_evento' => 'A', '_interno' => 'x']]);
        afirmar(!str_contains($sql, '_interno'), "a SQL contém a chave interna: $sql");
        afirmarIgual("INSERT INTO `evento` (`nome_evento`) VALUES ('A')", $sql);
    },

    'montarInsert preenche coluna ausente com NULL' => function () {
        $sql = Banco::montarInsert('t', [['a' => 1], ['a' => 2, 'b' => 'z']]);
        afirmarIgual("INSERT INTO `t` (`a`,`b`) VALUES (1,NULL),(2,'z')", $sql);
    },

    'montarInsert escapa aspas, barra e datas' => function () {
        $sql = Banco::montarInsert('t', [[
            'texto' => "D'Ávila \\ teste",
            'quando' => new DateTimeImmutable('2026-11-09 14:05:00'),
            'decimal' => 5.4321,
            'nulo' => null,
        ]]);
        afirmarIgual("INSERT INTO `t` (`texto`,`quando`,`decimal`,`nulo`) VALUES ('D\\'Ávila \\\\ teste','2026-11-09 14:05:00',5.4321,NULL)", $sql);
    },
];
