<?php
/*
 * Relatório "Campanha Liga Acme-EUA" do time de CS dentro do dashboard
 * Comercial 4. CSS, markup e JS vêm do relatório HTML original
 * (dashboard_liga_21_09.html), com todo seletor sob .liga-root para não
 * colidir com o bi-theme.css — o CSS é cópia literal do original, não
 * reescrever à mão. Dados: comercial4/ligaDados (mesmo formato do objeto `D`
 * do relatório). Sem nenhuma venda na campanha, `vendas` vem null e os
 * blocos de venda ficam ocultos.
 */
?>
<div class="liga-root">
<style>
/* Encaixe no shell do dashboard (não existe no relatório original): o
   .liga-root ocupa a área de conteúdo inteira com o fundo creme e vira
   container de referência para o breakpoint do relatório. */
.liga-root{min-height:100%;container-type:inline-size;container-name:liga}
/* Mensagem de carregando/erro da view (também não existe no relatório original). */
.liga-root .liga-msg{font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--ink-soft)}
/* ── CSS do relatório "Liga Acme-EUA" (dashboard_liga_21_09.html), idêntico
   ao original a menos do escopo .liga-root ── */
.liga-root{--bg:#F5F1EA;--bg-card:#FFFDF9;--ink:#1A2422;--ink-soft:#4A5755;--ink-faint:#8A938F;--rule:#E4DDD0;
--teal:#3E6B6F;--gold:#B8893B;--green:#5C7548;
--q1:#B8893B;--q2:#3E6B6F;--q3:#5C7548;--q4:#8A6A9E;--q5:#A6485C;--q6:#3A4548;--q7:#8C7A4B;--q8:#6E8B8E;--q9:#7D6A8F;}
.liga-root,.liga-root *{box-sizing:border-box;margin:0;padding:0}
.liga-root{background:var(--bg);color:var(--ink);font-family:'Inter Tight',sans-serif;font-size:16px;line-height:1.5;-webkit-font-smoothing:antialiased}
.liga-root .liga-wrap{padding:42px 32px 70px;max-width:1080px;margin:0 auto}
.liga-root .liga-tabs{display:flex;flex-wrap:wrap;gap:20px;margin-bottom:28px;font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.04em}
.liga-root .liga-tabs a{color:var(--ink-soft);text-decoration:none;padding-bottom:3px;border-bottom:1px solid transparent;transition:color .15s,border-color .15s}
.liga-root .liga-tabs a:hover{color:var(--ink)}
.liga-root .liga-tabs a[aria-current]{color:var(--ink);border-bottom-color:var(--teal)}
.liga-root .head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:1px solid var(--rule);padding-bottom:22px;margin-bottom:36px;gap:24px;flex-wrap:wrap}
.liga-root .head .marca{font-family:'JetBrains Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:.18em;color:var(--ink-soft);margin-bottom:8px}
.liga-root .head h1{font-family:'Fraunces',serif;font-size:40px;font-weight:500;line-height:1.05;letter-spacing:-.02em}
.liga-root .head h1 em{font-style:italic;color:var(--teal);font-weight:500}
.liga-root .head .meta{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink-soft);text-align:right;letter-spacing:.04em;line-height:1.8}
.liga-root .head .meta strong{color:var(--ink)}
.liga-root section{margin-bottom:42px}
.liga-root .sh{font-family:'JetBrains Mono',monospace;font-size:10.5px;text-transform:uppercase;letter-spacing:.16em;color:var(--ink-soft);margin-bottom:16px}
.liga-root .metas{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.liga-root .mc{background:var(--bg-card);border:1px solid var(--rule);border-radius:8px;padding:28px;position:relative;overflow:hidden}
.liga-root .mc::before{content:'';position:absolute;left:0;top:0;right:0;height:5px}
.liga-root .mc.a::before{background:var(--gold)}
.liga-root .mc.b::before{background:var(--green)}
.liga-root .mc.c::before{background:var(--green)}
.liga-root .mc .l{font-family:'JetBrains Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:.12em;color:var(--ink-soft);margin-bottom:16px;line-height:1.4}
.liga-root .mc .v{font-family:'Fraunces',serif;font-size:44px;font-weight:500;line-height:1;letter-spacing:-.03em}
.liga-root .mc.a .v{color:var(--gold)}
.liga-root .mc.b .v,.liga-root .mc.c .v{color:var(--green)}
.liga-root .mc .sub{font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--ink-soft);margin-top:10px}
.liga-root .mc .track{height:10px;background:#EFE9DC;border-radius:3px;overflow:hidden;margin:18px 0 10px}
.liga-root .mc .track .f{height:100%;border-radius:3px;transition:width 1s}
.liga-root .mc.a .f{background:var(--gold)}
.liga-root .mc.b .f,.liga-root .mc.c .f{background:var(--green)}
.liga-root .mc .metatxt{display:flex;justify-content:space-between;font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--ink-soft)}
.liga-root .mc .metatxt b{color:var(--ink)}
.liga-root .acc{background:var(--ink);color:var(--bg);border-radius:8px;padding:32px 36px}
.liga-root .acc .top{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;flex-wrap:wrap;margin-bottom:24px}
.liga-root .acc .lab{font-family:'JetBrains Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:.15em;color:rgba(245,241,234,.6);margin-bottom:10px}
.liga-root .acc .big{font-family:'Fraunces',serif;font-size:56px;font-weight:500;line-height:.95;letter-spacing:-.03em}
.liga-root .acc .big .sub{font-family:'JetBrains Mono',monospace;font-size:13px;color:rgba(245,241,234,.7);margin-top:10px}
.liga-root .acc .right{text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px;color:rgba(245,241,234,.7);line-height:2}
.liga-root .acc .right b{color:var(--bg);font-size:17px}
.liga-root .acc .track{height:34px;background:rgba(245,241,234,.12);border-radius:5px;display:flex;overflow:hidden}
.liga-root .acc .seg{height:100%;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:10px;color:#fff;font-weight:700;white-space:nowrap;overflow:hidden}
.liga-root .acc .escala{display:flex;justify-content:space-between;margin-top:9px;font-family:'JetBrains Mono',monospace;font-size:9.5px;color:rgba(245,241,234,.45)}
.liga-root .sem{background:var(--bg-card);border:2px solid var(--green);border-radius:8px;padding:26px 30px}
.liga-root .sem .sh2{display:flex;justify-content:space-between;align-items:baseline;gap:20px;flex-wrap:wrap;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--rule)}
.liga-root .sem .sh2 h3{font-family:'Fraunces',serif;font-size:20px;font-weight:500}
.liga-root .sem .sh2 .tot{font-family:'Fraunces',serif;font-size:30px;font-weight:500;color:var(--green);line-height:1}
.liga-root .sem .sh2 .tot small{display:block;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--ink-soft);text-align:right;font-weight:400;margin-top:3px}
.liga-root table.v{width:100%;border-collapse:collapse;font-size:13px}
.liga-root table.v th{font-family:'JetBrains Mono',monospace;font-size:9px;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-soft);text-align:left;padding:8px 10px;border-bottom:1px solid var(--ink);font-weight:500}
.liga-root table.v th:last-child{text-align:right}
.liga-root table.v td{padding:8px 10px;border-bottom:1px dashed var(--rule)}
.liga-root table.v td.cli{font-weight:500}
.liga-root table.v tr.novo td{background:#EDF1E5}
.liga-root table.v td.csm span{font-family:'JetBrains Mono',monospace;font-size:10.5px;padding:2px 8px;border-radius:3px;font-weight:500}
.liga-root .csm-Patrícia{background:#D6E2E3;color:var(--teal)}
.liga-root .csm-Simone{background:#F0E4CC;color:var(--gold)}
.liga-root .csm-Tânia{background:#DCE3D2;color:var(--green)}
.liga-root .csm-Viviane{background:#EBC9D2;color:#A6485C}
.liga-root table.v td.mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink-soft)}
.liga-root table.v td.val{font-family:'JetBrains Mono',monospace;font-weight:600;text-align:right;white-space:nowrap}
.liga-root table.v tr:last-child td{border-bottom:none}
.liga-root .sem .destaque{margin-top:16px;padding:14px 18px;background:#EDF1E5;border:1px solid #CFDCC0;border-radius:6px;font-size:13.5px;color:var(--ink-soft);line-height:1.55}
.liga-root .sem .destaque b{color:var(--green)}
.liga-root .qz{background:var(--bg-card);border:1px solid var(--rule);border-radius:8px;padding:26px 30px}
.liga-root .qr{display:grid;grid-template-columns:150px 1fr 44px 148px;align-items:center;gap:18px;padding:11px 0;border-bottom:1px dashed var(--rule)}
.liga-root .qr:last-child{border-bottom:none}
.liga-root .qr .n{font-size:15px;font-weight:600}
.liga-root .qr .n small{display:block;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--ink-faint);font-weight:400;margin-top:2px}
.liga-root .qr .tk{height:24px;background:#EFE9DC;border-radius:4px;overflow:hidden}
.liga-root .qr .tk .f{height:100%;border-radius:3px;transition:width 1s}
.liga-root .qr .nv{text-align:center;font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:700;color:var(--teal)}
.liga-root .qr .nv small{display:block;font-size:7.5px;color:var(--ink-faint);font-weight:400;letter-spacing:.05em}
.liga-root .qr .vv{text-align:right}
.liga-root .qr .vv .v{font-family:'Fraunces',serif;font-size:22px;font-weight:500;letter-spacing:-.01em}
.liga-root .qr .vv .s{display:block;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--ink-soft);margin-top:2px}
.liga-root .duo{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.liga-root .pn{background:var(--bg-card);border:1px solid var(--rule);border-radius:8px;padding:26px 28px}
.liga-root .pn h3{font-family:'Fraunces',serif;font-size:18px;font-weight:500;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid var(--rule)}
.liga-root .pn h3 span{font-family:'JetBrains Mono',monospace;font-size:9.5px;font-weight:400;color:var(--ink-faint);text-transform:uppercase;letter-spacing:.08em}
.liga-root .br{display:grid;grid-template-columns:150px 1fr 108px;align-items:center;gap:12px;padding:11px 0;border-bottom:1px dashed var(--rule)}
.liga-root .br:last-child{border-bottom:none}
.liga-root .br .n{font-size:13px;font-weight:500;line-height:1.25}
.liga-root .br .n small{display:block;font-family:'JetBrains Mono',monospace;font-size:9.5px;color:var(--ink-faint);font-weight:400;margin-top:1px}
.liga-root .br .fora{display:inline-block;font-family:'JetBrains Mono',monospace;font-size:8px;text-transform:uppercase;letter-spacing:.06em;background:#EFE9DC;color:var(--ink-faint);padding:1px 6px;border-radius:3px;margin-left:5px;vertical-align:middle}
.liga-root .br .tk{height:14px;background:#EFE9DC;border-radius:2px;overflow:hidden}
.liga-root .br .tk .f{height:100%;transition:width 1s}
.liga-root .br.c0 .f{background:var(--teal)}
.liga-root .br.c1 .f{background:var(--gold)}
.liga-root .br.c2 .f{background:var(--green)}
.liga-root .br.c3 .f{background:#A6485C}
.liga-root .br.c4 .f{background:#8A6A9E}
.liga-root .br.c5 .f{background:#7A8A8C}
.liga-root .br .vv{font-family:'JetBrains Mono',monospace;font-size:13px;text-align:right;font-weight:600}
.liga-root .br .vv small{display:block;font-size:9.5px;color:var(--ink-soft);font-weight:400;margin-top:1px}
.liga-root .pn .splitnota{margin-top:16px;padding-top:14px;border-top:1px dashed var(--rule);font-size:12px;color:var(--ink-soft);line-height:1.55}
.liga-root .pn .splitnota b{color:var(--ink)}
.liga-root .mstrip{background:var(--bg-card);border:1px solid var(--rule);border-radius:8px;padding:28px 32px;display:grid;grid-template-columns:auto 1fr;gap:32px;align-items:center}
.liga-root .mbars{display:flex;align-items:flex-end;gap:8px;height:120px;position:relative}
.liga-root .mbars .b{width:40px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%}
.liga-root .mbars .b .p{font-family:'JetBrains Mono',monospace;font-size:9.5px;font-weight:700;margin-bottom:5px;color:var(--ink-soft)}
.liga-root .mbars .b.on .p{color:var(--green)}
.liga-root .mbars .b .bar{width:100%;border-radius:4px 4px 0 0;background:var(--teal);transition:height 1s;opacity:.4}
.liga-root .mbars .b.on .bar{background:var(--green);opacity:1}
.liga-root .mbars .b .s{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--ink-soft);margin-top:7px}
.liga-root .mtxt .big{font-family:'Fraunces',serif;font-size:34px;font-weight:500;letter-spacing:-.02em;line-height:1;margin-bottom:10px}
.liga-root .mtxt .big .ar{color:var(--ink-faint);font-size:22px;margin:0 6px}
.liga-root .mtxt .big .f{color:var(--green)}
.liga-root .mtxt p{font-size:14px;color:var(--ink-soft);line-height:1.6}
.liga-root .mtxt p b{color:var(--ink)}
.liga-root .mtxt .seg{margin-top:10px;padding-top:10px;border-top:1px dashed var(--rule);font-size:12.5px}
.liga-root .funil{background:var(--bg-card);border:1px solid var(--rule);border-radius:8px;padding:26px 30px}
.liga-root .fl{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.liga-root .fc{border:1px solid var(--rule);border-radius:6px;padding:18px;text-align:center;background:#FBFAF6}
.liga-root .fc.on{background:#EDF1E5;border-color:var(--green)}
.liga-root .fc .l{font-family:'JetBrains Mono',monospace;font-size:9px;text-transform:uppercase;letter-spacing:.09em;color:var(--ink-soft);margin-bottom:8px;line-height:1.4}
.liga-root .fc .v{font-family:'Fraunces',serif;font-size:32px;font-weight:500;line-height:1}
.liga-root .fc.on .v{color:var(--green)}
.liga-root .funil .nota{font-size:13.5px;color:var(--ink-soft);line-height:1.6;padding-top:16px;border-top:1px dashed var(--rule)}
.liga-root .funil .nota b{color:var(--ink)}
.liga-root footer{margin-top:46px;padding-top:20px;border-top:1px solid var(--rule);font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--ink-faint);letter-spacing:.1em;text-transform:uppercase;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px}
@container liga (max-width:900px){.liga-root .liga-wrap{padding:26px 16px 50px}
.liga-root .head h1{font-size:29px}
.liga-root .head .meta{text-align:left}
.liga-root .metas,.liga-root .duo{grid-template-columns:1fr}
.liga-root .fl{grid-template-columns:1fr 1fr}
.liga-root .acc .big{font-size:40px}
.liga-root .qr{grid-template-columns:86px 1fr 32px 100px;gap:10px}
.liga-root .br{grid-template-columns:118px 1fr 90px;gap:8px}
.liga-root .mstrip{grid-template-columns:1fr;gap:22px}
.liga-root .mbars .b{width:30px}}
</style>
<div class="liga-wrap">
<nav class="liga-tabs" aria-label="Abas do dashboard">
<a href="<?= $this->site ?>comercial4<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Comercial 4</a>
<a href="<?= $this->site ?>comercial4/liga<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>" aria-current="page">Liga Acme-EUA</a>
<a href="<?= $this->site ?>comercial4/dicionario<?= (($_GET['to'] ?? '') === '1') ? '?to=1' : '' ?>">Dicionário</a>
</nav>
<header class="head">
<div><div class="marca">Acme Group · Customer Success</div><h1>Campanha <em>Liga Acme-EUA</em></h1></div>
<div class="meta"><div>Período: <strong id="h-periodo">—</strong></div><div id="h-semana" hidden>Última semana: <strong id="h-semana-v">—</strong></div>
<div>Meta de vendas: <strong id="h-meta">—</strong></div></div>
</header>
<section><div class="sh">Metas da campanha · resultado atingido</div><div class="metas" id="metas"><p class="liga-msg">Carregando…</p></div></section>
<section id="s-sem" hidden><div class="sh" id="sh-sem">Vendas da última semana</div><div class="sem" id="sem"></div></section>
<section id="s-acc" hidden><div class="sh">Vendas acumuladas</div><div class="acc" id="acc"></div></section>
<section id="s-qz" hidden><div class="sh">Vendas por quinzena</div><div class="qz" id="qz"></div></section>
<section id="s-duo" hidden><div class="sh">Vendas por CSM e por produto</div><div class="duo" id="duo"></div></section>
<section><div class="sh">Clientes mapeados · evolução contra a meta</div><div class="mstrip" id="mstrip"></div></section>
<section><div class="sh">Conversão de oportunidades · como o número é formado</div><div class="funil" id="funil"></div></section>
<footer><span id="rod-l">Liga Acme-EUA</span><span id="rod"></span></footer>
</div>
</div>
<script>
(function(){
const API='<?= $this->site ?>comercial4/ligaDados';
const $=s=>document.querySelector('.liga-root '+s);
const brl=n=>'R$ '+n.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
const brl0=n=>'R$ '+n.toLocaleString('pt-BR',{maximumFractionDigits:0});
const QC=['var(--q1)','var(--q2)','var(--q3)','var(--q4)','var(--q5)','var(--q6)','var(--q7)','var(--q8)','var(--q9)'];
const qc=i=>QC[i%QC.length];
// Percentual com divisor zero → 0: venda sem valor conta, então total e máximos podem ser R$ 0.
const pc=(a,b)=>b>0?a/b*100:0;

function render(D){
const Q=D.quinzenas,M=D.map,C=D.conv,V=D.vendas;

$('#h-periodo').textContent=D.periodo.inicio.slice(0,5)+' a '+D.periodo.ate;
$('#h-meta').textContent=brl(D.meta);
$('#rod-l').textContent='Liga Acme-EUA · Resultados até '+D.periodo.ate;

const metaA=V?`
<div class="mc a"><div class="l">Meta 1 · Vendas em valor</div><div class="v">${V.pct}%</div><div class="sub">${brl(V.comb)}</div>
<div class="track"><div class="f" style="width:${Math.min(100,V.pct)}%"></div></div>
<div class="metatxt"><span>${V.qtd} vendas · faltam ${brl0(Math.max(0,D.meta-V.comb))}</span><span>meta <b>${brl0(D.meta)}</b></span></div></div>`:`
<div class="mc a"><div class="l">Meta 1 · Vendas em valor</div><div class="v">—</div><div class="sub">aguardando data e valor das vendas no Bitrix</div>
<div class="track"><div class="f" style="width:0%"></div></div>
<div class="metatxt"><span>sem dados de venda</span><span>meta <b>${brl0(D.meta)}</b></span></div></div>`;
const difC=C.pct-C.meta;
$('#metas').innerHTML=metaA+`
<div class="mc b"><div class="l">Meta 2 · Clientes mapeados</div><div class="v">${M.pct}%</div><div class="sub">${M.eq} de ${M.meta} clientes</div>
<div class="track"><div class="f" style="width:${Math.min(100,M.pct)}%"></div></div>
<div class="metatxt"><span>${M.excedente>=0?`meta superada em ${M.excedente} clientes`:`faltam ${-M.excedente} clientes`}</span><span>meta <b>${M.meta}</b></span></div></div>
<div class="mc c"><div class="l">Meta 3 · Conversão de oportunidades</div><div class="v">${C.pct}%</div>
<div class="sub">${C.fechadas} fechadas ÷ ${C.total} negociações</div>
<div class="track"><div class="f" style="width:${Math.min(100,C.pct/C.meta*100)}%"></div></div>
<div class="metatxt"><span>${difC>=0?`meta superada em ${difC.toFixed(1)} pp`:`faltam ${(-difC).toFixed(1)} pp`}</span><span>meta <b>≥ ${C.meta}%</b></span></div></div>`;

if(V){
const S=V.semana,NP=V.novo_produto;
['#s-sem','#s-acc','#s-qz','#s-duo','#h-semana'].forEach(s=>$(s).hidden=false);
$('#h-semana-v').textContent=S.periodo;
$('#sh-sem').textContent='Vendas da última semana · '+S.periodo;

$('#sem').innerHTML=`
<div class="sh2"><h3>${S.qtd} vendas fechadas · ${S.periodo}</h3>
<div class="tot">${brl0(S.total)}<small>dólar ${S.dolar.toFixed(4)}</small></div></div>
<table class="v"><thead><tr><th>Cliente</th><th>Produto</th><th>CSM</th><th>Data</th><th>Valor</th></tr></thead>
<tbody>${[...S.vendas].sort((a,b)=>b.brl-a.brl).map(v=>`<tr class="${NP&&v.produto===NP.nome?'novo':''}">
<td class="cli">${v.cliente}</td><td>${v.produto}</td>
<td class="csm"><span class="csm-${v.csm}">${v.csm}</span></td><td class="mono">${v.data}</td>
<td class="val">${brl(v.brl)}</td></tr>`).join('')}</tbody></table>
${NP?`<div class="destaque"><b>Primeira venda de ${NP.nome} na campanha:</b> ${NP.cliente} (${NP.csm}), ${brl(NP.valor)}.${NP.ordinal?` É o ${NP.ordinal} dos ${NP.total_produtos} produtos do mapeamento a registrar venda.`:''}</div>`:''}`;

$('#acc').innerHTML=`
<div class="top"><div><div class="lab">Total vendido · ${D.periodo.inicio.slice(0,5)} a ${D.periodo.ate.slice(0,5)}</div>
<div class="big">${brl0(V.comb)}<div class="sub">${V.qtd} vendas · ticket médio ${brl(V.ticket)}</div></div></div>
<div class="right"><div>Meta da campanha<br><b>${brl0(D.meta)}</b></div><div>Atingimento<br><b>${V.pct}%</b></div></div></div>
<div class="track">${Q.map((q,i)=>{const w=q.total/D.meta*100;return `<div class="seg" style="width:${w}%;background:${qc(i)}">${w>5?q.label:''}</div>`;}).join('')}</div>
<div class="escala"><span>0</span><span>${brl0(D.meta*.25)}</span><span>${brl0(D.meta*.5)}</span><span>${brl0(D.meta*.75)}</span><span>${brl0(D.meta)}</span></div>`;

const maxQ=Math.max(...Q.map(q=>q.total));
$('#qz').innerHTML=Q.map((q,i)=>`<div class="qr"><div class="n">${q.label}<small>${q.datas}</small></div>
<div class="tk"><div class="f" style="width:${pc(q.total,maxQ)}%;background:${qc(i)}"></div></div>
<div class="nv">${q.qtd}<small>VENDAS</small></div>
<div class="vv"><span class="v">${brl0(q.total)}</span><span class="s">${q.andamento?'em andamento':(q.total/D.meta*100).toFixed(1)+'% da meta'}</span></div></div>`).join('');

const maxC=Math.max(...V.csm.map(c=>c.v)),maxQtd=Math.max(...V.prod_vol.map(p=>p.q));
$('#duo').innerHTML=`
<div class="pn"><h3>Por CSM</h3>${V.csm.map((c,i)=>`<div class="br c${i}"><div class="n">${c.n}<small>${c.q} vendas</small></div>
<div class="tk"><div class="f" style="width:${pc(c.v,maxC)}%"></div></div>
<div class="vv">${brl0(c.v)}<small>${pc(c.v,V.comb).toFixed(0)}% do total</small></div></div>`).join('')}</div>
<div class="pn"><h3>Por produto <span>ordenado por nº de vendas</span></h3>
${V.prod_vol.map((p,i)=>`<div class="br c${i}"><div class="n">${p.n}${p.fora?' <span class="fora">fora do mapeamento</span>':''}<small>ticket médio ${brl0(p.v/p.q)}</small></div>
<div class="tk"><div class="f" style="width:${p.q/maxQtd*100}%${p.fora?';opacity:.45':''}"></div></div>
<div class="vv">${p.q} venda${p.q===1?'':'s'}<small>${brl0(p.v)} · ${pc(p.v,V.comb).toFixed(0)}%</small></div></div>`).join('')}
<div class="splitnota">Os produtos do mapeamento respondem por <b>${brl0(V.split.map_v)}</b> em ${V.split.map_q} vendas (${pc(V.split.map_v,V.comb).toFixed(1)}% do total). O Visto de turismo é comercializado à parte e não integra o mapeamento da carteira.</div></div>`;
}

const maxM=Math.max(...Q.map(q=>q.map_pct||0));
$('#mstrip').innerHTML=`
<div class="mbars">${Q.map(q=>q.map_eq==null?`<div class="b"><div class="p">—</div>
<div class="bar" style="height:0%"></div><div class="s">${q.label}</div></div>`:`<div class="b ${q.map_eq>=M.meta?'on':''}"><div class="p">${q.map_eq}</div>
<div class="bar" style="height:${q.map_pct/maxM*100}%"></div><div class="s">${q.label}</div></div>`).join('')}</div>
<div class="mtxt"><div class="big">${M.inicio}<span class="ar">→</span><span class="f">${M.eq}</span>
<span style="font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--ink-soft);margin-left:8px">clientes</span></div>
<p>A meta de mapeamento era de <b>${M.meta} clientes</b>. A campanha chegou a <b>${M.eq}</b> — <b>${M.pct}% da meta</b>, ${M.excedente>=0?`com ${M.excedente} clientes acima do previsto`:`faltando ${-M.excedente} clientes para a meta`}.</p>
<p class="seg">Meta alcançada em <b>${M.data_meta}</b> (${M.eq_meta} clientes) ${M.eq>=M.meta?'e mantida desde então':'mas hoje abaixo dela'} — as barras em verde marcam as quinzenas acima da meta. A carteira programada era de ${M.carteira} clientes; sobre ela, a cobertura é de ${M.pct_cart}%.</p></div>`;

$('#funil').innerHTML=`
<div class="fl">
<div class="fc on"><div class="l">Negociações fechadas</div><div class="v">${C.fechadas}</div></div>
<div class="fc"><div class="l">Em negociação</div><div class="v">${C.em_negoc}</div></div>
<div class="fc"><div class="l">Declinadas</div><div class="v">${C.declinadas}</div></div>
<div class="fc"><div class="l">Aguardando orçamento</div><div class="v">${C.orcamento}</div></div>
</div>
<div class="nota"><b>Como o indicador é calculado:</b> de todas as oportunidades que chegaram a virar negociação (${C.total} no total), <b>${C.fechadas} foram fechadas</b> — conversão de <b>${C.pct}%</b>, ${difC>=0?'acima':'abaixo'} da meta de ${C.meta}%. O indicador usa a base do mapeamento e considera apenas clientes que efetivamente entraram em negociação, não a carteira inteira.${C.provisorio?' <b>Provisório:</b> conta o status atual de cada produto da carteira, inclusive negociações anteriores a 18/05, enquanto a regra do relatório é confirmada com o time de CS.':''}</div>`;

$('#rod').textContent=(V?brl(V.comb)+' · '+V.qtd+' vendas · ':'')+'mapeados '+M.eq+'/'+M.meta+' · conversão '+C.pct+'%';
}

fetch(API)
  .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
  .then(D=>{if(D.error)throw new Error(D.error);render(D);})
  .catch(e=>{$('#metas').innerHTML=`<p class="liga-msg">Não foi possível carregar os dados da Liga (${e.message}).</p>`;});
})();
</script>
