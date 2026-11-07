/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/core.js — núcleo do sistema de Gestão de Eventos (hotéis)
 *
 * O projeto não tem bundler nem npm, então o padrão é o mesmo de
 * js/biexplorer.js: script clássico, um namespace global (GH), cada arquivo
 * um IIFE que apenas DEFINE — nada roda no parse. A view chama init() no fim.
 *
 * Substitui as 3.306 linhas de JS monolítico do sistema antigo, onde uma
 * função de render enxergava e alterava o DOM de qualquer aba.
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.core = (function () {
  'use strict';

  // ── Escape ────────────────────────────────────────────────────────────
  // O sistema antigo interpolava valores crus em innerHTML em todas as
  // telas. Aqui a regra é: texto livre vai por textContent; quando template
  // string for inevitável, passa por esc() — incluindo a aspa simples, que a
  // versão de paginas/eventos/index.php esquece e deixa escapar atributo.
  var MAPA_ESC = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

  function esc(v) {
    if (v === null || v === undefined) return '';
    return String(v).replace(/[&<>"']/g, function (c) { return MAPA_ESC[c]; });
  }

  // ── Formatação ────────────────────────────────────────────────────────

  function brl(v, zeroComoTraco) {
    var n = Number(v) || 0;
    if (n === 0 && zeroComoTraco !== false) return '—';
    return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function num(v, casas) {
    return (Number(v) || 0).toLocaleString('pt-BR', {
      minimumFractionDigits: casas || 0, maximumFractionDigits: casas || 0
    });
  }

  // Formatação puramente textual, sem new Date(): interpretar '2026-01-15'
  // como Date e reformatar reintroduz o deslocamento de um dia por fuso.
  function data(d, vazio) {
    if (!d || String(d).length < 10) return vazio === undefined ? '—' : vazio;
    var s = String(d);
    return s.slice(8, 10) + '/' + s.slice(5, 7) + '/' + s.slice(0, 4);
  }

  function dias(de, ate) {
    if (!de || !ate) return null;
    var a = Date.parse(de + 'T00:00:00'), b = Date.parse(ate + 'T00:00:00');
    if (isNaN(a) || isNaN(b)) return null;
    return Math.round((b - a) / 86400000);
  }

  function hoje() {
    var d = new Date();
    return d.getFullYear() + '-' +
      String(d.getMonth() + 1).padStart(2, '0') + '-' +
      String(d.getDate()).padStart(2, '0');
  }

  function icone(nome, classe) {
    return '<svg class="' + esc(classe || 'i') + '" aria-hidden="true"><use href="#i-' + esc(nome) + '"></use></svg>';
  }

  // ── URLs ──────────────────────────────────────────────────────────────

  var BASE = (function () {
    // O controller é sempre o primeiro segmento; tudo antes dele é o basePath
    // da instalação, que difere entre o servidor embutido e a produção.
    var p = window.location.pathname;
    var i = p.indexOf('/hotel');
    return i >= 0 ? p.slice(0, i + 6) : '/hotel';
  })();

  function url(rota, params) {
    var u = new URL(BASE + (rota ? '/' + String(rota).replace(/^\//, '') : ''), window.location.origin);
    if (new URLSearchParams(window.location.search).get('to') === '1') {
      u.searchParams.set('to', '1');
    }
    Object.keys(params || {}).forEach(function (k) {
      if (params[k] !== null && params[k] !== undefined && params[k] !== '') {
        u.searchParams.set(k, params[k]);
      }
    });
    return u.toString();
  }

  // ── HTTP ──────────────────────────────────────────────────────────────

  function ErroHttp(mensagem, status, corpo) {
    this.name = 'ErroHttp';
    this.message = mensagem;
    this.status = status || 0;
    this.corpo = corpo || null;
    this.erros = (corpo && corpo.erros) || null;
  }
  ErroHttp.prototype = Object.create(Error.prototype);

  async function lerResposta(res) {
    var texto = await res.text();
    var dados = null;
    try { dados = JSON.parse(texto); } catch (e) { /* não era JSON */ }

    if (dados === null) {
      // Acontece quando a sessão expira (o servidor devolve a página de
      // login) ou quando o framework redireciona um POST. Melhor dizer isso
      // do que deixar vazar um "Unexpected token '<'".
      throw new ErroHttp(
        res.status === 401
          ? 'Sessão expirada. Recarregue a página e entre de novo.'
          : 'O servidor respondeu algo inesperado (HTTP ' + res.status + ').',
        res.status
      );
    }

    if (!res.ok || dados.ok === false) {
      throw new ErroHttp(dados.erro || ('Falha na requisição (HTTP ' + res.status + ').'), res.status, dados);
    }

    return dados;
  }

  var http = {
    get: async function (rota, params) {
      var res = await fetch(url(rota, params), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
      });
      return lerResposta(res);
    },

    /**
     * POST de escrita. Dois cuidados obrigatórios com o framework:
     *
     * 1. Corpo em x-www-form-urlencoded, nunca JSON. MRPHP::bloqueiaPostDuplicado()
     *    calcula a impressão digital do POST com md5(serialize($_POST) . REQUEST_URI);
     *    com corpo JSON o $_POST fica vazio, então dois salvamentos diferentes
     *    na mesma rota têm a MESMA impressão e o segundo, dentro de 5 s, é
     *    engolido com um redirect.
     * 2. Um nonce na querystring, que entra no REQUEST_URI e torna cada
     *    requisição distinta mesmo com o mesmo corpo.
     */
    post: async function (rota, dados) {
      var corpo = new URLSearchParams();
      Object.keys(dados || {}).forEach(function (k) {
        var v = dados[k];
        corpo.set(k, v === null || v === undefined ? '' : v);
      });

      var res = await fetch(url(rota, { _n: Date.now().toString(36) + Math.random().toString(36).slice(2, 8) }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'Accept': 'application/json',
          'X-Requested-With': 'fetch'
        },
        body: corpo.toString()
      });
      return lerResposta(res);
    }
  };

  // ── Dados iniciais ────────────────────────────────────────────────────
  // O controller injeta o payload da aba num <script type="application/json">,
  // então a primeira pintura não depende de fetch. É o que elimina a tela de
  // carregamento com carrossel de mensagens e o timeout de 8 s do original —
  // que existiam só por causa da lentidão do Apps Script.
  function boot(id) {
    var el = document.getElementById(id || 'gh-boot');
    if (!el) return {};
    try {
      return JSON.parse(el.textContent) || {};
    } catch (e) {
      console.error('Payload inicial inválido:', e);
      return {};
    }
  }

  // ── Eventos ───────────────────────────────────────────────────────────
  // Delegação em vez de onclick inline: onclick="ver('${nome}')" é
  // exatamente o vetor de injeção do sistema antigo, e quebra a cada
  // re-render. Aqui o handler é registrado uma vez, no container.
  function on(container, evento, seletor, fn) {
    var raiz = typeof container === 'string' ? document.querySelector(container) : container;
    if (!raiz) return;
    raiz.addEventListener(evento, function (ev) {
      var alvo = ev.target.closest(seletor);
      if (alvo && raiz.contains(alvo)) fn.call(alvo, ev, alvo);
    });
  }

  function debounce(fn, ms) {
    var t;
    return function () {
      var args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, ms || 250);
    };
  }

  /**
   * Cor de token do CSS — mantém a paleta com uma fonte de verdade só.
   *
   * Lê do `.dash-hotel` (o <body>), não do <html>: os tokens deste sistema
   * (--gh-*) são escopados em `.dash-hotel`, e ler do documentElement devolvia
   * string vazia para todos eles — o gráfico de gasto mensal saía inteiramente
   * cinza, no fallback '#888' de charts.js, enquanto a rosca de contratos
   * continuava colorida porque os tokens dela vivem em :root.
   *
   * Custom property é herdada, então o body enxerga as duas camadas: os tokens
   * globais do bi-theme e os locais deste dashboard.
   */
  function corToken(nome) {
    var base = document.querySelector('.dash-hotel') || document.documentElement;
    return getComputedStyle(base).getPropertyValue(nome).trim();
  }

  return {
    esc: esc, brl: brl, num: num, data: data, dias: dias, hoje: hoje,
    icone: icone, url: url, http: http, boot: boot, on: on,
    debounce: debounce, corToken: corToken, ErroHttp: ErroHttp
  };
})();
