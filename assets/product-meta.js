/* ================================================================
   FlexWave – Frontend CSS
   https://flex-wave.nl
   Copyright (c) 2026 Jorian Beukens
   E-mail: jorian@flex-wave.nl
   Alle rechten voorbehouden
   ================================================================ */
  
(function () {
  'use strict';

  const list      = document.getElementById('fw-pm-list');
  const orderFld  = document.getElementById('fw_groups_order');
  if (!list) return;

  list.addEventListener('change', function (e) {
    const cb = e.target;

    if (cb.classList.contains('fw-pm-check')) {
      const row    = cb.closest('.fw-pm-row');
      const active = cb.checked;
      row.classList.toggle('fw-pm--active', active);

      const expBtn = row.querySelector('.fw-pm-expand');
      if (expBtn) expBtn.disabled = !active;

      if (!active) {
        const vars = row.querySelector('.fw-pm-vars');
        if (vars) { vars.style.display = 'none'; }
        const expBtnEl = row.querySelector('.fw-pm-expand');
        if (expBtnEl) { expBtnEl.textContent = '▶ variaties'; expBtnEl.classList.remove('fw-expanded'); }
      }

      saveOrder();
    }

    if (cb.classList.contains('fw-pm-var-check')) {
      const row    = cb.closest('.fw-pm-row');
      const gid    = row.dataset.gid;
      const allCb  = row.querySelector('.fw-pm-select-all');
      const checks = Array.from( row.querySelectorAll('.fw-pm-var-check') );
      const allOn  = checks.every(c => c.checked);
      if (allCb) allCb.checked = allOn;
      updateBadge(row, checks);
    }

    if (cb.classList.contains('fw-pm-select-all')) {
      const row    = cb.closest('.fw-pm-row');
      const checks = row.querySelectorAll('.fw-pm-var-check');
      checks.forEach(c => c.checked = cb.checked);
      updateBadge(row, Array.from(checks));
    }
  });

  list.addEventListener('click', function (e) {
    const btn = e.target.closest('.fw-pm-expand');
    if (!btn || btn.disabled) return;

    const row  = btn.closest('.fw-pm-row');
    const vars = row.querySelector('.fw-pm-vars');
    if (!vars) return;

    const open = vars.style.display !== 'none';
    vars.style.display = open ? 'none' : 'block';
    btn.textContent = open ? '▶ variaties' : '▼ variaties';
    btn.classList.toggle('fw-expanded', !open);
  });

  let dragged = null;

  list.addEventListener('dragstart', function (e) {
    dragged = e.target.closest('.fw-pm-row');
    if (!dragged) return;
    dragged.classList.add('fw-row--dragging');
    e.dataTransfer.effectAllowed = 'move';
  });

  list.addEventListener('dragend', function () {
    if (dragged) dragged.classList.remove('fw-row--dragging');
    dragged = null;
    saveOrder();
  });

  list.addEventListener('dragover', function (e) {
    e.preventDefault();
    const target = e.target.closest('.fw-pm-row');
    if (!target || target === dragged) return;
    const rect = target.getBoundingClientRect();
    if (e.clientY < rect.top + rect.height / 2) {
      list.insertBefore(dragged, target);
    } else {
      list.insertBefore(dragged, target.nextSibling);
    }
  });

  list.querySelectorAll('.fw-pm-row').forEach(makeDraggable);

  new MutationObserver(muts => {
    muts.forEach(m => m.addedNodes.forEach(n => {
      if (n.classList && n.classList.contains('fw-pm-row')) makeDraggable(n);
    }));
  }).observe(list, { childList: true });

  function makeDraggable(row) {
    row.setAttribute('draggable', 'true');
    const handle = row.querySelector('.fw-pm-drag');
    if (handle) {
      handle.addEventListener('mousedown', () => row.setAttribute('draggable', 'true'));
    }
  }

  function saveOrder() {
    if (!orderFld) return;
    const ids = Array.from(list.querySelectorAll('.fw-pm-row[data-gid]'))
      .filter(r => r.querySelector('.fw-pm-check')?.checked)
      .map(r => parseInt(r.dataset.gid, 10));
    orderFld.value = JSON.stringify(ids);
  }

  function updateBadge(row, checks) {
    const badge  = row.querySelector('.fw-pm-badge');
    if (!badge) return;
    const total   = checks.length;
    const checked = checks.filter(c => c.checked).length;
    const allOn   = checked === total;
    badge.textContent = allOn ? total + ' variaties' : checked + ' / ' + total + ' variaties';
    badge.classList.toggle('fw-pm-badge--filtered', !allOn);
  }

  saveOrder();
})();
