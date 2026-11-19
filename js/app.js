'use strict';
/**
 * Comportamiento mínimo del lado del navegador. La lógica, los cálculos y el HTML los genera PHP;
 * aquí solo: refresco en vivo, avisos, reloj, filas clicables y confirmaciones.
 */
(() => {
  const $ = (sel, raiz = document) => raiz.querySelector(sel);

  // ---- Avisos (toasts) ----
  function toast(mensaje, tipo = '') {
    const cont = $('#toasts');
    if (!cont) return;
    const d = document.createElement('div');
    d.className = 'toast ' + tipo;
    d.setAttribute('role', tipo === 'er' ? 'alert' : 'status');
    d.textContent = mensaje;
    cont.append(d);
    setTimeout(() => d.remove(), 5000);
  }
  document.querySelectorAll('#toasts .toast').forEach(t => setTimeout(() => t.remove(), 5000));

  // ---- Reloj de la cabecera ----
  const reloj = $('#clk');
  if (reloj) {
    const pintar = () => { reloj.textContent = new Date().toLocaleString('es-BO', { dateStyle: 'full', timeStyle: 'medium' }); };
    pintar();
    setInterval(pintar, 1000);
  }

  // ---- Delegación de eventos (sin JavaScript dentro del HTML) ----
  document.addEventListener('click', ev => {
    const demo = ev.target.closest('[data-demo-usuario]');
    if (demo) {
      $('#lu').value = demo.dataset.demoUsuario;
      $('#lp').value = demo.dataset.demoClave;
      $('#lp').focus();
      return;
    }
    if (ev.target.closest('[data-imprimir]')) { window.print(); return; }
    const fila = ev.target.closest('tr[data-href]');
    if (fila && !ev.target.closest('a, button, input, select')) location.href = fila.dataset.href;
  });
  document.addEventListener('change', ev => {
    const sel = ev.target.closest('[data-autoenviar]');
    if (sel && sel.form) sel.form.submit();
  });
  document.addEventListener('submit', ev => {
    const msg = ev.target.dataset && ev.target.dataset.confirm;
    if (msg && !confirm(msg)) ev.preventDefault();
  });

  // ---- Refresco en vivo (solo en pantallas con #config[data-vista]) ----
  const cfg = $('#config');
  if (!cfg) return;
  const vivo = $('#vivo');
  Graficos.dibujarTodas();
  addEventListener('resize', () => { clearTimeout(cfg._t); cfg._t = setTimeout(() => Graficos.dibujarTodas(), 150); });
  if (!cfg.dataset.vista || !vivo) return;

  let ultima = parseInt(cfg.dataset.ultimaAlerta, 10) || 0;
  let ultimoHtml = vivo.innerHTML, enCurso = false;

  function actualizarInsignia(n) {
    const enlace = document.querySelector('.nv[href="alertas.php"]');
    if (!enlace) return;
    let b = enlace.querySelector('.bd');
    if (!n && b) b.remove();
    else if (n && b) b.textContent = n;
    else if (n) { b = document.createElement('span'); b.className = 'bd'; b.textContent = n; enlace.append(b); }
  }

  async function refrescar() {
    if (enCurso || document.hidden) return;
    enCurso = true;
    try {
      const url = `vivo.php?v=${encodeURIComponent(cfg.dataset.vista)}&${cfg.dataset.query || ''}&desde=${ultima}`;
      const r = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
      if (r.status === 401) { location.href = 'login.php'; return; }
      if (!r.ok) return;
      const d = await r.json();
      if (d.html !== ultimoHtml) {          // evita tocar el DOM si no cambió (no pierde clics en botones)
        vivo.innerHTML = d.html;
        ultimoHtml = d.html;
        Graficos.dibujarTodas(vivo);
      }
      actualizarInsignia(d.alertas);
      d.nuevas.forEach(m => toast(m, 'er'));
      ultima = d.ultima;
    } catch (e) { /* sin red: se reintenta en el siguiente ciclo */ }
    finally { enCurso = false; }
  }
  setInterval(refrescar, parseInt(cfg.dataset.intervalo, 10) || 2000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refrescar(); });
})();
