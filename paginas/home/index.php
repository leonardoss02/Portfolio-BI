<div class="main-container">
    <div class="card">
        <h1>Bancos de Dados</h1>
        <p>Bem-vindo ao MRPHP Framework</p>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <h3>Banco 1</h3>
                <?php
                $queryTables = $this->con->query("SHOW TABLES");
                while ($resTables = $queryTables->fetch_row()) {
                    echo "<p>" . $resTables[0] . "</p>";
                }
                ?>
            </div>

            <div style="flex: 1;">
                <h3>Banco 2</h3>
                <?php
                $con2 = $this->connectDatabase('2');
                if ($con2) {
                    $queryTables2 = $con2->query("SHOW TABLES");
                    while ($resTables2 = $queryTables2->fetch_row()) {
                        echo "<p>" . $resTables2[0] . "</p>";
                    }
                } else {
                    echo "<p>Erro ao conectar ao Banco 2</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>