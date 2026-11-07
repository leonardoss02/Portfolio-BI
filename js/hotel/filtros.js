/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/filtros.js — painel de filtros da Gestão de Eventos
 *
 * Monta os dropdowns do painel com createMultiDropdown() de
 * js/bi-dashboard.js — a mesma factory do Executive e do Marketing, para o
 * comportamento (abrir, marcar, "3 selecionados", fechar ao clicar fora) ser
 * idêntico ao dos demais dashboards em vez de uma reimplementação parecida.
 *
 * Dois tipos de campo, um botão só:
 *
 *   - de SERVIDOR (ano, mês, cidade) → "Aplicar" navega para a URL nova.
 *     O recorte mora na querystring desde sempre, e é o que faz o botão
 *     voltar e o link compartilhável funcionarem.
 *   - de TELA (situação, busca) → filtram as linhas já renderizadas.
 *
 * Antes cada tela trazia o seu próprio bloco de filtro local inline, com a
 * mesma lógica copiada quatro vezes e divergindo nos detalhes (uma zerava o
 * contador, outra não; uma tratava "todos", outra usava string vazia).
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.filtros = (function () {
  'use strict';

  var cfg      = null;
  var dds      = {};   // chave -> dropdown
  var locais   = [];   // campos de tela
  var servidor = [];   // campos que viajam na URL
  var campoBusca = null;

  /** Elementos filtráveis: tudo que declara data-busca (cartão ou linha). */
  function alvos() {
    return Array.prototype.slice.call(document.querySelectorAll('[data-busca]'));
  }

  function aplicarLocal() {
    var termo = campoBusca ? campoBusca.value.trim().toLowerCase() : '';

    // Cada campo local vira {dataset, valores[]}. Sem valor marcado, o campo
    // não restringe nada — não é o mesmo que "nenhum resultado".
    var criterios = locais.map(function (f) {
      var v = dds[f.chave].getValue();
      return { dataset: f.dataset, valores: v ? v.split(',') : [] };
    }).filter(function (c) { return c.valores.length > 0; });

    var visiveis = 0;

    alvos().forEach(function (el) {
      var ok = !termo || (el.dataset.busca || '').indexOf(termo) >= 0;

      for (var i = 0; ok && i < criterios.length; i++) {
        var bruto = el.dataset[criterios[i].dataset] || '';
        // data-tags traz várias tags separadas por espaço; data-status, uma só.
        var doItem = bruto.split(' ').filter(Boolean);
        ok = criterios[i].valores.some(function (v) { return doItem.indexOf(v) >= 0; });
      }

      el.hidden = !ok;
      if (ok) { visiveis++; }
    });

    var contador = document.getElementById('gh-contador');
    if (contador) { contador.textContent = visiveis; }

    avisarVazio(visiveis);
  }

  /**
   * Uma lista que ficou vazia por causa do filtro precisa dizer isso — sem a
   * mensagem, a tela parece quebrada ou parece que o recorte não tem dados.
   */
  function avisarVazio(visiveis) {
    var vazio = document.getElementById('gh-filtro-vazio');
    var total = alvos().length;

    if (visiveis === 0 && total > 0) {
      if (!vazio) {
        var ancora = document.querySelector('.gh-cards') || document.querySelector('.gh-tabela-wrap');
        if (!ancora) { return; }
        vazio = document.createElement('div');
        vazio.id = 'gh-filtro-vazio';
        vazio.className = 'gh-vazio';
        vazio.textContent = 'Nenhum registro corresponde ao filtro.';
        ancora.parentNode.insertBefore(vazio, ancora.nextSibling);
      }
      vazio.hidden = false;
    } else if (vazio) {
      vazio.hidden = true;
    }
  }

  /** URL com os campos de servidor que estiverem selecionados. */
  function urlServidor() {
    var url = new URL(cfg.rota, window.location.origin);

    var anos  = dds.anos.getValue();
    var meses = dds.meses.getValue();
    if (anos)  { url.searchParams.set('anos', anos); }
    if (meses) { url.searchParams.set('meses', meses); }

    servidor.forEach(function (f) {
      var v = dds[f.chave].getValue();
      if (v) { url.searchParams.set(f.chave, v); }
    });

    return url.toString();
  }

  function aplicar() {
    var destino = urlServidor();
    // Navegar só quando o recorte de servidor mudou de fato; senão um clique
    // em "Aplicar" recarregaria a página para reaplicar filtro de cliente.
    if (destino !== window.location.href) {
      window.location.href = destino;
      return;
    }
    aplicarLocal();
  }

  function limpar() {
    Object.keys(dds).forEach(function (k) { dds[k].reset(); });
    if (campoBusca) { campoBusca.value = ''; }

    if (cfg.rota !== window.location.href) {
      window.location.href = cfg.rota;
      return;
    }
    aplicarLocal();
  }

  function montar(chave, opcoes, selecionados) {
    var dd = createMultiDropdown(
      'ff-' + chave + '-wrap',
      'ff-' + chave + '-btn',
      'ff-' + chave + '-list',
      opcoes,
      function (o) {
        // A contagem entra como sufixo do rótulo, como nos demais dashboards.
        return o.n === null || o.n === undefined ? o.r : o.r + ' (' + o.n + ')';
      },
      function (o) { return o.v; }
    );
    if (selecionados && selecionados.length) {
      dd.select(selecionados.map(String));
    }
    dds[chave] = dd;
    return dd;
  }

  function iniciar() {
    var el = document.getElementById('gh-filtros-cfg');
    if (!el || typeof createMultiDropdown !== 'function') { return; }

    cfg = JSON.parse(el.textContent);

    montar('anos',  cfg.anos,  cfg.anosSel);
    montar('meses', cfg.meses, cfg.mesesSel);

    cfg.tela.forEach(function (f) {
      montar(f.chave, f.opcoes, f.valores || []);
      (f.servidor ? servidor : locais).push(f);
    });

    campoBusca = document.getElementById('ff-busca');

    document.getElementById('ff-aplicar').addEventListener('click', aplicar);
    document.getElementById('ff-limpar').addEventListener('click', limpar);

    // Enter no campo de busca aplica, como em qualquer formulário.
    if (campoBusca) {
      campoBusca.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); aplicar(); }
      });
      // Digitar filtra na hora: esperar o "Aplicar" para uma busca textual
      // seria mais lento que só varrer a lista.
      campoBusca.addEventListener('input', GH.core.debounce(aplicarLocal, 150));
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }

  return { aplicarLocal: aplicarLocal };
})();
