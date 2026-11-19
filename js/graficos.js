'use strict';
/**
 * Gráficas de líneas dibujadas en <canvas>, sin librerías externas.
 * Los datos los entrega PHP en el propio HTML: <canvas data-series='[{"color":"#fff","datos":[1,2]}]' data-umbral="650">
 */
const Graficos = (() => {
  const MARGEN_IZQ = 42, MARGEN_DER = 6, MARGEN_SUP = 10, MARGEN_INF = 20;
  const fmt = n => Math.round(n).toLocaleString('es-BO');

  function lineas(canvas, series, umbral) {
    const dpr = window.devicePixelRatio || 1;
    const W = canvas.clientWidth, H = canvas.clientHeight;
    if (!W || !H) return;
    canvas.width = W * dpr;
    canvas.height = H * dpr;
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    const valores = series.flatMap(s => s.datos).concat(umbral == null ? [] : [umbral]);
    const max = Math.max(...valores, 10) * 1.1;
    const Y = v => H - MARGEN_INF - (H - MARGEN_INF - MARGEN_SUP) * v / max;

    ctx.font = '11px sans-serif';
    ctx.fillStyle = '#8fa3bf';
    ctx.strokeStyle = '#27406a';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
      const y = Y(max * i / 4);
      ctx.beginPath(); ctx.moveTo(MARGEN_IZQ, y); ctx.lineTo(W - MARGEN_DER, y); ctx.stroke();
      ctx.fillText(fmt(max * i / 4), 2, y + 4);
    }

    if (umbral != null) {
      ctx.strokeStyle = '#ff5a5f';
      ctx.fillStyle = '#ff5a5f';
      ctx.setLineDash([6, 4]);
      ctx.beginPath(); ctx.moveTo(MARGEN_IZQ, Y(umbral)); ctx.lineTo(W - MARGEN_DER, Y(umbral)); ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillText('umbral', W - 46, Y(umbral) - 4);
    }

    series.forEach(serie => {
      ctx.strokeStyle = serie.color;
      ctx.lineWidth = 2;
      ctx.beginPath();
      serie.datos.forEach((v, i) => {
        const x = MARGEN_IZQ + (W - MARGEN_IZQ - MARGEN_DER) * i / Math.max(serie.datos.length - 1, 1);
        if (i) ctx.lineTo(x, Y(v)); else ctx.moveTo(x, Y(v));
      });
      ctx.stroke();
    });
  }

  /** Dibuja todas las gráficas (canvas[data-series]) que haya dentro de `raiz`. */
  function dibujarTodas(raiz = document) {
    raiz.querySelectorAll('canvas[data-series]').forEach(c => {
      try {
        const umbral = c.dataset.umbral ? parseFloat(c.dataset.umbral) : null;
        lineas(c, JSON.parse(c.dataset.series), umbral);
      } catch (e) { /* datos dañados: se omite la gráfica */ }
    });
  }

  return { dibujarTodas };
})();
