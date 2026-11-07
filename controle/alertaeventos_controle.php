<?php

require_once __DIR__ . '/../lib/hotel/Repositorio.php';
require_once __DIR__ . '/../lib/hotel/Calculo.php';
require_once __DIR__ . '/../lib/hotel/SmtpMailer.php';

/**
 * Alerta por email de pendência de evento (Gestão de Eventos / `/hotel`).
 *
 * Endpoint de automação, não uma tela: pensado para ser chamado 1x/dia por
 * um cron job do cPanel (`curl` com o token no header), sem depender de
 * GitHub Actions nem de acesso ao servidor além do próprio cPanel.
 *
 * Reaproveita a mesma fonte única de verdade do banner "Eventos nos próximos
 * 7 dias" do dashboard: HotelCalculo::alertas() (janela de 7 dias) e
 * HotelCalculo::checklist() (pendências por evento) — nenhuma regra nova de
 * negócio é criada aqui, só a leitura e o envio do email.
 *
 * Sem estado de "já avisado": reenvia todo dia enquanto a pendência de
 * quarto ou de equipamento A/V persistir dentro da janela de 7 dias —
 * decisão do usuário, mais simples que guardar histórico de envio.
 *
 * Autenticação própria (ALERTA_EVENTOS_TOKEN), independente do Basic Auth do
 * dashboard (exigirAcesso()) e do gate global API_MODE/API_USER — mesmo
 * raciocínio do SQL_API_TOKEN em sql_controle.php.
 */
class alertaeventos extends MRPHP
{
    /** Checklist de HotelCalculo cujas chaves disparam o alerta de pendência. */
    private const CHAVES_PENDENCIA = ['quartos', 'av', 'avPago'];

    public function enviar()
    {
        try {
            $this->rateLimit(10, 60);
            $this->autenticar();

            $destinatario = (string)HotelRepo::lerEnv('ALERTA_EVENTOS_DESTINATARIO');
            if ($destinatario === '') {
                $this->jsonOut(['error' => 'ALERTA_EVENTOS_DESTINATARIO não configurado no .env'], 500);
            }

            $repo = HotelRepo::criar();
            $calc = new HotelCalculo($repo);

            $proximos = $calc->alertas()['proximos'];

            $enviados = [];
            foreach ($proximos as $item) {
                $hotel = $repo->buscar('hotel', $item['id']);
                if (!$hotel) {
                    continue;
                }

                $checklist = $calc->checklist($hotel);
                $secoes    = $this->secoesPendencia($checklist['pendencias']);
                if (empty($secoes)) {
                    continue;
                }

                $assunto = 'Pêndencias Evento: ' . $this->nomeParaAssunto($item);
                $html    = $this->montarEmailHtml($item, $secoes);

                SmtpMailer::enviar($destinatario, $assunto, $html);
                $enviados[] = $assunto;
            }

            $this->jsonOut([
                'verificados' => count($proximos),
                'enviados'    => count($enviados),
                'eventos'     => $enviados,
            ]);
        } catch (\Throwable $e) {
            $this->jsonError($e);
        }
    }

    // =====================================================================
    // Regras do alerta
    // =====================================================================

    /**
     * Filtra as pendências do checklist para só as que este alerta cobre
     * (quarto reservado, equipamento A/V) e as agrupa em, no máximo, duas
     * seções de email — `av` e `avPago` viram uma seção só, porque quando o
     * fornecedor de A/V não está definido as duas sempre falham juntas e
     * mostrar dois avisos seria redundante.
     *
     * @param array<int,array{chave:string,rotulo:string,ok:bool,detalhe:string}> $pendencias
     * @return array<int,array{titulo:string,detalhe:string}>
     */
    private function secoesPendencia(array $pendencias): array
    {
        $porChave = [];
        foreach ($pendencias as $p) {
            if (in_array($p['chave'], self::CHAVES_PENDENCIA, true)) {
                $porChave[$p['chave']] = $p;
            }
        }

        $secoes = [];
        if (isset($porChave['quartos'])) {
            $secoes[] = [
                'titulo'  => 'Quartos não confirmados',
                'detalhe' => $porChave['quartos']['detalhe'],
            ];
        }
        if (isset($porChave['av']) || isset($porChave['avPago'])) {
            $secoes[] = [
                'titulo'  => 'Equipamento A/V não confirmado',
                'detalhe' => $porChave['av']['detalhe'] ?? $porChave['avPago']['detalhe'],
            ];
        }
        return $secoes;
    }

    private function nomeParaAssunto(array $item): string
    {
        if (!empty($item['nomeEvento'])) {
            return $item['nomeEvento'];
        }
        return trim($item['cidade'] . ' - ' . $this->data($item['data']));
    }

    // =====================================================================
    // Email
    // =====================================================================

    /** @param array<int,array{titulo:string,detalhe:string}> $secoes */
    private function montarEmailHtml(array $item, array $secoes): string
    {
        $nomeEvento = htmlspecialchars($this->nomeParaAssunto($item), ENT_QUOTES, 'UTF-8');
        $cidade     = htmlspecialchars((string)$item['cidade'], ENT_QUOTES, 'UTF-8');
        $nomeHotel  = htmlspecialchars((string)$item['nome'], ENT_QUOTES, 'UTF-8');
        $data       = htmlspecialchars((string)$this->data($item['data']), ENT_QUOTES, 'UTF-8');
        $quando     = htmlspecialchars((string)$item['quando'], ENT_QUOTES, 'UTF-8');

        $caixas = '';
        foreach ($secoes as $secao) {
            $titulo  = htmlspecialchars($secao['titulo'], ENT_QUOTES, 'UTF-8');
            $detalhe = htmlspecialchars((string)$secao['detalhe'], ENT_QUOTES, 'UTF-8');
            $caixas .= <<<HTML
                <div style="background:#fff8e1;border-left:4px solid #f5a623;border-radius:4px;padding:12px 16px;margin-top:16px;">
                    <div style="font-weight:bold;color:#8a5a00;font-size:14px;">
                        ⚠️ Evento {$quando} — {$titulo}!
                    </div>
                    <div style="font-size:13px;color:#7a4b00;margin-top:4px;">
                        {$detalhe}
                    </div>
                </div>
                HTML;
        }

        $timestamp = htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <div style="max-width:600px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;background:#f4f6f9;padding:24px;">
                <div style="background:#0b2545;color:#ffffff;padding:20px 24px;border-radius:8px 8px 0 0;">
                    <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:.75;">
                        ACME EUA &middot; {$nomeEvento}
                    </div>
                    <div style="font-size:19px;font-weight:bold;margin-top:6px;">
                        🏨 Alerta &mdash; Pendências do evento
                    </div>
                </div>
                <div style="background:#ffffff;padding:24px;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
                    <div style="font-size:15px;color:#0b2545;font-weight:bold;">📍 {$cidade}</div>
                    <div style="font-size:13px;color:#555555;margin-top:2px;">{$nomeHotel} &middot; {$data}</div>
                    {$caixas}
                    <div style="font-size:11px;color:#999999;margin-top:24px;text-align:center;">
                        Alerta automático &middot; Gestão de Eventos &middot; {$timestamp}
                    </div>
                </div>
            </div>
            HTML;
    }

    // =====================================================================
    // Infraestrutura
    // =====================================================================

    private function autenticar(): void
    {
        $token = HotelRepo::lerEnv('ALERTA_EVENTOS_TOKEN');
        if (empty($token)) {
            $this->jsonOut(['error' => 'ALERTA_EVENTOS_TOKEN não configurado no .env'], 500);
        }

        $enviado = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($enviado, 'Bearer ')) {
            $enviado = substr($enviado, 7);
        } elseif ($enviado === '') {
            $enviado = (string)($_REQUEST['token'] ?? '');
        }

        if (!hash_equals((string)$token, (string)$enviado)) {
            $this->jsonOut(['error' => 'Acesso negado'], 401);
        }
    }

    private function jsonOut(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(\Throwable $e, int $status = 500): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $isDev   = strtoupper($this->config['app']['env'] ?? '') === 'DEV';
        $payload = ['error' => $e->getMessage()];
        if ($isDev) {
            $payload['file'] = $e->getFile();
            $payload['line'] = $e->getLine();
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
