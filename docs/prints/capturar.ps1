# Captura os prints das telas para o README.
#
# Ferramenta de quem mantém o projeto (Windows + Microsoft Edge). Quem só quer
# ver o projeto não precisa dela: os PNGs já estão nesta pasta.
#
# Uso, com o projeto no ar (docker compose up -d):
#   powershell -ExecutionPolicy Bypass -File docs/prints/capturar.ps1
#   powershell -ExecutionPolicy Bypass -File docs/prints/capturar.ps1 -Porta 8080

param(
    [int]$Porta = 8000
)

# arquivo = rota, altura da janela (px) — a altura acompanha o conteúdo de cada tela.
$telas = [ordered]@{
    'executivo.png'      = @('/executive', 1040)
    'comercial.png'      = @('/comercial', 1160)
    'marketing.png'      = @('/marketing', 1300)
    'eventos.png'        = @('/eventos', 1260)
    'financeiro.png'     = @('/financeiro', 1100)
    'atendimento.png'    = @('/chatwoot', 950)
    'jornada.png'        = @('/helenacastro/jornada', 1500)
    'gestao-eventos.png' = @('/hotel', 1420)
}

$edge = @(
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $edge) {
    Write-Error 'Microsoft Edge não encontrado.'
    exit 1
}

$pasta = $PSScriptRoot
# Duas passadas: na primeira o cache dos dashboards está frio e alguma tela pode
# sair ainda carregando; a segunda grava os prints definitivos.
foreach ($passada in 1, 2) {
    foreach ($arquivo in $telas.Keys) {
        $rota, $altura = $telas[$arquivo]
        $destino = Join-Path $pasta $arquivo
        $url = "http://demo:demo@localhost:$Porta$rota"
        Write-Host "[$passada/2] $rota -> $arquivo"
        & $edge --headless=new --disable-gpu --hide-scrollbars "--window-size=1440,$altura" `
            --virtual-time-budget=20000 --screenshot="$destino" $url 2>$null | Out-Null
    }
}
Write-Host 'Pronto.'
