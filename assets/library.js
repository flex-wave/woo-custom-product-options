/* ================================================================
   FlexWave – Frontend CSS
   https://flex-wave.nl
   Copyright (c) 2026 FlexWave
   E-mail: jorian@flex-wave.nl
   Alle rechten voorbehouden
   ================================================================ */

(function () {
  'use strict';

  const typeSelect  = document.getElementById('fw_group_type');
  const varWrap     = document.getElementById('fw-variations-wrap');
  const textWrap    = document.getElementById('fw-text-wrap');
  const varBody     = document.getElementById('fw-variations-body');
  const addVarBtn   = document.getElementById('fw-add-variation');
  const rowTpl      = document.getElementById('fw-var-row-tpl');

  let varCounter = varBody ? varBody.querySelectorAll('.fw-var-row').length : 0;

  if (typeSelect) {
    typeSelect.addEventListener('change', applyType);
    applyType();
  }

  function applyType() {
    const t = typeSelect.value;
    varWrap.classList.toggle('fw-hidden', t === 'text');
    textWrap.classList.toggle('fw-hidden', t !== 'text');

    document.querySelectorAll('.fw-col-color').forEach(el => el.classList.toggle('fw-hidden', t !== 'color'));
    document.querySelectorAll('.fw-col-image').forEach(el => el.classList.toggle('fw-hidden', t === 'color'));
  }

  if (addVarBtn && rowTpl) {
    addVarBtn.addEventListener('click', function () {
      const html = rowTpl.innerHTML.replace(/__VI__/g, 'n' + varCounter++);
      varBody.insertAdjacentHTML('beforeend', html);
      applyType();
    });
  }

  if (varBody) {
    varBody.addEventListener('click', function (e) {

      if (e.target.classList.contains('fw-remove-var')) {
        e.target.closest('.fw-var-row').remove();
      }

      if (e.target.classList.contains('fw-upload-img')) {
        const row   = e.target.closest('.fw-var-row');
        const idEl  = row.querySelector('.fw-image-id');
        const urlEl = row.querySelector('.fw-image-url');
        const wrap  = row.querySelector('.fw-img-wrap');

        const frame = wp.media({
          title   : 'Kies afbeelding',
          button  : { text: 'Gebruik afbeelding' },
          multiple: false,
        });

        frame.on('select', function () {
          const att = frame.state().get('selection').first().toJSON();
          idEl.value  = att.id;
          urlEl.value = att.url;

          let img = wrap.querySelector('.fw-thumb');
          if (!img) {
            img = document.createElement('img');
            img.className = 'fw-thumb';
            wrap.insertBefore(img, wrap.querySelector('.fw-upload-img'));
          }
          img.src = att.sizes?.thumbnail?.url || att.url;

          let rem = wrap.querySelector('.fw-remove-img');
          if (!rem) {
            rem = document.createElement('button');
            rem.type      = 'button';
            rem.className = 'button-link-delete fw-remove-img';
            rem.style     = 'display:block;margin-top:3px;font-size:11px';
            rem.textContent = 'verwijder';
            wrap.appendChild(rem);
          }
        });

        frame.open();
      }

      if (e.target.classList.contains('fw-remove-img')) {
        const row  = e.target.closest('.fw-var-row');
        row.querySelector('.fw-image-id').value  = '';
        row.querySelector('.fw-image-url').value = '';
        const img = row.querySelector('.fw-thumb');
        if (img) img.remove();
        e.target.remove();
      }
    });
  }

})();
