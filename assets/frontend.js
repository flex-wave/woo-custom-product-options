/* ================================================================
   FlexWave – Frontend JS
   https://flex-wave.nl
   Copyright (c) 2026 FlexWave
   E-mail: jorian@flex-wave.nl
   Alle rechten voorbehouden
   ================================================================ */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.fw-options[data-fw]').forEach(initWidget);
  });

  var _originalGalleryImg = null;

  function switchProductImage(imageUrl) {
    var gallery = document.querySelector('.woocommerce-product-gallery');
    if (!gallery) return;

    if (_originalGalleryImg === null) {
      var origImg = gallery.querySelector('.wp-post-image');
      _originalGalleryImg = origImg ? origImg.getAttribute('src') : '';
    }

    var targetSrc = imageUrl || _originalGalleryImg;
    if (!targetSrc) return;

    var $gallery = typeof jQuery !== 'undefined' && jQuery(gallery);
    if ($gallery && $gallery.data('flexslider')) {
      var slider = $gallery.data('flexslider');
      var slides = slider.slides;

      for (var i = 0; i < slides.length; i++) {
        var slideImg = slides.eq(i).find('img').first();
        var slideSrc = slideImg.attr('src') || '';

        if (basename(slideSrc) === basename(targetSrc)) {
          slider.flexAnimate(i, true);
          return;
        }
      }
    }

    var mainImg = gallery.querySelector('.wp-post-image');
    if (mainImg) {
      mainImg.setAttribute('src', targetSrc);
      mainImg.removeAttribute('srcset');

      var wrap = mainImg.closest('a');
      if (wrap) wrap.setAttribute('href', targetSrc);
    }
  }

  function basename(url) {
    var file = (url || '').split('?')[0].split('/').pop();
    return file.replace(/-\d+x\d+(\.\w+)$/, '$1');
  }

  function initWidget(widget) {
    let cfg;
    try {
      cfg = JSON.parse(widget.dataset.fw);
    } catch (e) {
      return;
    }

    const pid = cfg.product_id;
    const base = parseFloat(cfg.base_price) || 0;
    const sym = cfg.symbol || '€';

    const totalEl = document.getElementById('fw-total-' + pid);
    const selField = document.getElementById('fw-sel-' + pid);
    const exField = document.getElementById('fw-extra-' + pid);

    widget.querySelectorAll('.fw-option').forEach(function (label) {
      label.addEventListener('click', function () {
        const radio = label.querySelector('input[type="radio"]');
        if (!radio) return;

        const groupId = radio.dataset.group;

        requestAnimationFrame(function () {
          widget.querySelectorAll('input[data-group="' + groupId + '"]').forEach(function (r) {
            r.closest('.fw-option').classList.toggle('fw-option--active', r.checked);
          });

          const imageUrl = radio.dataset.image || '';
          switchProductImage(imageUrl);

          const previewId = 'fw-cp-fw_g' + pid + '_' + groupId;
          const preview = document.getElementById(previewId);

          if (preview) {
            const dot = preview.querySelector('.fw-cp-dot');
            const lbl = preview.querySelector('.fw-cp-label');
            const swatch = label.querySelector('.fw-swatch');

            if (swatch && dot) dot.style.background = swatch.style.background;
            if (lbl) lbl.textContent = radio.dataset.label || '';

            preview.style.display = 'flex';
          }

          const groupEl = label.closest('.fw-group');
          if (groupEl) {
            const err = groupEl.querySelector('.fw-group-error');
            if (err) err.style.display = 'none';
          }

          recalc();
        });
      });
    });

    widget.querySelectorAll('.fw-text-input').forEach(function (input) {
      input.addEventListener('input', function () {
        const groupEl = input.closest('.fw-group');
        if (groupEl) {
          const err = groupEl.querySelector('.fw-group-error');
          if (err) err.style.display = 'none';
        }
        recalc();
      });
    });

    const form = widget.closest('form.cart');
    if (form) {
      form.addEventListener('submit', function (e) {
        if (!validateRequired()) {
          e.preventDefault();
          e.stopImmediatePropagation();
          widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, true);
    }

    function recalc() {
      const sels = collectSelections();
      const extra = sels.reduce(function (s, sel) {
        return s + sel.price;
      }, 0);

      const total = base + extra;

      if (totalEl) {
        totalEl.textContent = sym + '\u00a0' + fmt(total);
        totalEl.classList.remove('fw-total--bump');
        void totalEl.offsetWidth;
        totalEl.classList.add('fw-total--bump');
      }

      if (selField) selField.value = JSON.stringify(sels);
      if (exField) exField.value = extra.toFixed(2);
    }

    function collectSelections() {
      const result = [];

      cfg.groups.forEach(function (group) {
        if (group.type === 'text') {
          const inp = widget.querySelector('.fw-text-input[data-group="' + group.id + '"]');
          if (!inp || inp.value.trim() === '') return;

          const v0 = group.variations[0] || {};

          result.push({
            group_id: group.id,
            group_name: group.name,
            type: 'text',
            label: inp.value.trim(),
            price: parseFloat(v0.price) || 0,
          });
        } else {
          const checked = widget.querySelector('input[data-group="' + group.id + '"]:checked');
          if (!checked) return;

          result.push({
            group_id: group.id,
            group_name: group.name,
            type: group.type,
            label: checked.dataset.label || '',
            price: parseFloat(checked.dataset.price) || 0,
          });
        }
      });

      return result;
    }

    function validateRequired() {
      let ok = true;

      cfg.groups.forEach(function (group) {
        if (!group.required) return;

        const groupEl = widget.querySelector('[data-group-id="' + group.id + '"]');
        if (!groupEl) return;

        let filled;

        if (group.type === 'text') {
          const inp = groupEl.querySelector('.fw-text-input');
          filled = inp && inp.value.trim() !== '';
        } else {
          filled = !!groupEl.querySelector('input[type="radio"]:checked');
        }

        if (!filled) {
          const err = groupEl.querySelector('.fw-group-error');
          if (err) err.style.display = 'block';
          ok = false;
        }
      });

      return ok;
    }

    function fmt(n) {
      return n.toLocaleString('nl-NL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    recalc();
  }

})();