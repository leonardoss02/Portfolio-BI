/* ═══════════════════════════════════════════════════════════════════════
 * js/hotel/charts.js — gráficos da visão geral
 *
 * Chart.js entra só onde a FORMA exige canvas: rosca, barras empilhadas e
 * histograma. Barra proporcional simples continua em div + CSS, que é mais
 * leve, tem texto de verdade (legível por leitor de tela e pesquisável),
 * imprime certo no relatório e não precisa ser redesenhada ao trocar o tema.
 *
 * As cores saem das CSS vars, então existe uma fonte de verdade cromática
 * só — e os gráficos se redesenham quando o tema muda.
 * ═══════════════════════════════════════════════════════════════════════ */

window.GH = window.GH || {};

GH.charts = (function () {
  'use strict';

  var core = GH.core;
  var instancias = [];

  function disponivel() { return typeof Chart !== 'undefined'; }

  function cor(token, alfa) {
    var c = core.corToken(token) || '#888';
    if (alfa === undefined) return c;
    // Aceita hex de 6 dígitos; qualquer outra coisa volta como está.
    var m = /^#([0-9a-f]{6})$/i.exec(c);
    if (!m) return c;
    var n = parseInt(m[1], 16);
    return 'rgba(' + [(n >> 16) & 255, (n >> 8) & 255, n & 255].join(',') + ',' + alfa + ')';
  }

  function baseOpcoes() {
    var texto = core.corToken('--text') || '#293244';
    var grade = core.corToken('--border') || '#dde2ec';
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: texto, font: { family: 'Fira Sans', size: 11 }, boxWidth: 12 } },
        tooltip: {
          callbacks: {}
        }
      },
      scales: {},
      _grade: grade,
      _texto: texto
    };
  }

  function registrar(c) { instancias.push(c); return c; }

  // ── Rosca ─────────────────────────────────────────────────────────────

  function rosca(canvas, rotulos, valores, cores) {
    if (!disponivel() || !canvas) return null;
    var o = baseOpcoes();

    return registrar(new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: rotulos,
        datasets: [{
          data: valores,
          backgroundColor: cores.map(function (t) { return cor(t); }),
          borderColor: core.corToken('--card'),
          borderWidth: 2
        }]
      },
      options: {
        responsive: o.responsive,
        maintainAspectRatio: false,
        cutout: '58%',
        /*
         * 'dataset' traz TODAS as fatias, não só a que está sob o cursor.
         *
         * Numa rosca de situação a pergunta é sempre a divisão inteira — ver
         * "Pago: 20 (91%)" sozinho não diz o que são os 9% restantes, e as
         * fatias finas, que são justamente as que interessam, são as mais
         * difíceis de acertar com o mouse.
         *
         * Aqui é 'dataset' e não 'index' (o modo do gráfico de barras) porque
         * a rosca tem um dataset só: as fatias são pontos dele, então é o
         * dataset inteiro que reúne o conjunto.
         */
        interaction: { mode: 'dataset', intersect: true },
        plugins: {
          legend: { position: 'right', labels: o.plugins.legend.labels },
          tooltip: {
            callbacks: {
              // Com todas as fatias na lista, o título repetiria o rótulo da
              // primeira; o total do conjunto é o que falta ali.
              title: function (itens) {
                if (!itens.length) { return ''; }
                var total = itens[0].dataset.data.reduce(function (a, b) { return a + b; }, 0);
                return total + (total === 1 ? ' evento' : ' eventos');
              },
              label: function (ctx) {
                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                var pct = total ? Math.round(ctx.parsed / total * 100) : 0;
                return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
              }
            }
          }
        }
      }
    }));
  }

  // ── Barras empilhadas ─────────────────────────────────────────────────

  function empilhada(canvas, rotulos, series) {
    if (!disponivel() || !canvas) return null;
    var o = baseOpcoes();

    return registrar(new Chart(canvas, {
      type: 'bar',
      data: {
        labels: rotulos,
        datasets: series.map(function (s) {
          return {
            label: s.rotulo,
            data: s.dados,
            // Sólido, sem alfa: a paleta foi validada contra a superfície
            // opaca, e 85% muda a cor que chega ao olho — o que invalida a
            // medição de separação entre as parcelas vizinhas.
            backgroundColor: cor(s.cor),
            borderWidth: 0
          };
        })
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        // A barra é o alvo, não o segmento: passar o mouse em qualquer altura
        // da coluna abre o mês inteiro. Antes só aparecia a parcela exata sob
        // o cursor, e ler o mês todo exigia percorrer as seis faixas uma a uma
        // — nas parcelas finas isso é quase impossível de acertar.
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: o.plugins.legend,
          tooltip: {
            callbacks: {
              // Parcela zerada fica de fora: uma linha "R$ 0,00" por parcela
              // ausente enche o tooltip de nada.
              label: function (ctx) {
                if (!ctx.parsed.y) { return null; }
                return ' ' + ctx.dataset.label + ': ' + core.brl(ctx.parsed.y);
              },
              footer: function (itens) {
                var t = itens.reduce(function (a, i) { return a + i.parsed.y; }, 0);
                return 'Total: ' + core.brl(t);
              }
            }
          }
        },
        scales: {
          x: {
            stacked: true,
            ticks: { color: o._texto, font: { size: 10 } },
            grid: { display: false },
            // Coluna larga, vão estreito: o padrão do Chart.js deixa quase
            // metade da faixa vazia, e o gráfico vira mais fundo que dado.
            categoryPercentage: 0.9,
            barPercentage: 0.92
          },
          y: {
            stacked: true, beginAtZero: true,
            ticks: {
              color: o._texto, font: { size: 10 },
              callback: function (v) { return core.num(v / 1000) + 'k'; }
            },
            grid: { color: o._grade }
          }
        }
      }
    }));
  }

  // ── Histograma ────────────────────────────────────────────────────────

  function histograma(canvas, dist, corToken) {
    if (!disponivel() || !canvas) return null;
    var o = baseOpcoes();
    var rotulos = [];
    var dados = [];
    for (var n = 5; n >= 1; n--) {
      rotulos.push(n + '★');
      dados.push(dist[n] || 0);
    }

    return registrar(new Chart(canvas, {
      type: 'bar',
      data: { labels: rotulos, datasets: [{ data: dados, backgroundColor: cor(corToken), borderWidth: 0 }] },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,

        // `dataset` em vez do padrão: o tooltip traz as cinco faixas de uma
        // vez, não só a barra sob o cursor. Uma distribuição só se lê inteira
        // — saber que 5★ tem 52 não diz nada sem ver quantos são 1★.
        interaction: { mode: 'dataset', intersect: false },

        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              // O título viria do primeiro item ("5★"), o que sugeriria que o
              // tooltip é só daquela faixa.
              title: function () { return 'Distribuição'; },
              label: function (ctx) {
                return ' ' + ctx.label + ': ' + core.num(ctx.parsed.x);
              },
              footer: function (itens) {
                var total = itens.reduce(function (a, i) { return a + i.parsed.x; }, 0);
                if (!total) { return 'Sem avaliações'; }
                var soma = itens.reduce(function (a, i) {
                  return a + (parseInt(i.label, 10) * i.parsed.x);
                }, 0);
                return 'Total: ' + core.num(total) + '  ·  média ' +
                       (soma / total).toFixed(1).replace('.', ',');
              }
            }
          }
        },
        scales: {
          x: { beginAtZero: true, ticks: { color: o._texto, precision: 0, font: { size: 10 } }, grid: { color: o._grade } },
          y: { ticks: { color: o._texto, font: { size: 11 } }, grid: { display: false } }
        }
      }
    }));
  }

  // Redesenha ao trocar o tema: as cores vêm das CSS vars, que mudam junto.
  document.addEventListener('gh:tema', function () {
    var pendentes = instancias.slice();
    instancias = [];
    pendentes.forEach(function (c) {
      try { c.destroy(); } catch (e) { /* ja destruido */ }
    });
    document.dispatchEvent(new CustomEvent('gh:redesenhar'));
  });

  return { disponivel: disponivel, rosca: rosca, empilhada: empilhada, histograma: histograma, cor: cor };
})();
