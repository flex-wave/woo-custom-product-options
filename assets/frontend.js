(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fw-options[data-fw]').forEach(initWidget);
  });

  // Luister naar quantity-wijzigingen en update prijs
  document.addEventListener('fw-quantity-changed', () => {
    document.querySelectorAll('.fw-options[data-fw]').forEach(widget => {
      if (widget._fwRecalc) widget._fwRecalc();
    });
  });

  let _originalGalleryImg = null;

  const switchProductImage = imageUrl => {
    const gallery = document.querySelector('.woocommerce-product-gallery');
    if (!gallery) return;
    if (_originalGalleryImg === null) {
      const origImg = gallery.querySelector('.wp-post-image');
      _originalGalleryImg = origImg ? origImg.getAttribute('src') : '';
    }
    const targetSrc = imageUrl || _originalGalleryImg;
    if (!targetSrc) return;
    const $gallery = typeof jQuery !== 'undefined' && jQuery(gallery);
    if ($gallery && $gallery.data('flexslider')) {
      const slider = $gallery.data('flexslider');
      const slides = slider.slides;
      for (let i = 0; i < slides.length; i++) {
        const slideImg = slides.eq(i).find('img').first();
        const slideSrc = slideImg.attr('src') || '';
        if (basename(slideSrc) === basename(targetSrc)) {
          slider.flexAnimate(i, true);
          return;
        }
      }
    }
    const mainImg = gallery.querySelector('.wp-post-image');
    if (mainImg) {
      mainImg.setAttribute('src', targetSrc);
      mainImg.removeAttribute('srcset');
      const wrap = mainImg.closest('a');
      if (wrap) wrap.setAttribute('href', targetSrc);
    }
  };

  const basename = url => (url || '').split('?')[0].split('/').pop().replace(/-\d+x\d+(\.\w+)$/, '$1');

  function initWidget(widget) {
    let cfg;
    try {
      cfg = JSON.parse(widget.dataset.fw);
    } catch (e) {
      return;
    }
    const pid = cfg.product_id;

    widget.querySelectorAll('.fw-option').forEach(label => {
      label.addEventListener('click', () => {
        const radio = label.querySelector('input[type="radio"]');
        if (!radio) return;
        const groupId = radio.dataset.group;
        requestAnimationFrame(() => {
          widget.querySelectorAll(`input[data-group="${groupId}"]`).forEach(r => {
            r.closest('.fw-option').classList.toggle('fw-option--active', r.checked);
          });
          switchProductImage(radio.dataset.image || '');
          const preview = document.getElementById(`fw-cp-fw_g${pid}_${groupId}`);
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

    widget.querySelectorAll('.fw-text-input').forEach(input => {
      input.addEventListener('input', () => {
        const groupEl = input.closest('.fw-group');
        if (groupEl) {
          const err = groupEl.querySelector('.fw-group-error');
          if (err) err.style.display = 'none';
        }
        recalc();
      });
    });

    widget.querySelectorAll('.fw-length-type').forEach(select => {
      const toggleCustomRow = () => {
        const wrap = select.closest('.fw-lengths-wrapper');
        if (!wrap) return;
        const customRow = wrap.querySelector('.fw-custom-length-row');
        if (!customRow) return;
        if (select.value === 'maatwerk') {
          customRow.style.display = '';
          const inp = customRow.querySelector('input');
          if (inp) inp.focus();
        } else {
          customRow.style.display = 'none';
          const err = customRow.querySelector('.fw-custom-length-error');
          if (err) err.style.display = 'none';
        }
      };
      select.addEventListener('change', () => {
        toggleCustomRow();
        recalc();
      });
      toggleCustomRow();
    });

    widget.querySelectorAll('.fw-custom-length').forEach(input => {
      input.addEventListener('input', () => {
        const wrap = input.closest('.fw-lengths-wrapper');
        const err = wrap ? wrap.querySelector('.fw-custom-length-error') : null;
        if (!err) return;
        const min = parseFloat(input.min);
        const max = parseFloat(input.max);
        const val = parseFloat(input.value.replace(',', '.'));
        if (isNaN(val) || val < min || val > max) {
          err.textContent = `Voer een geldige lengte in (${min} – ${max} cm)`;
          err.style.display = 'block';
        } else {
          err.style.display = 'none';
        }
        recalc();
      });
      input.addEventListener('change', recalc);
    });

    widget.querySelectorAll('input, select, textarea').forEach(el => {
      if (!el.classList.contains('fw-listener-attached')) {
        el.addEventListener('change', recalc);
        el.classList.add('fw-listener-attached');
      }
    });

    const form = widget.closest('form.cart');
    if (form) {
      form.addEventListener('submit', e => {
        if (!validateRequired()) {
          e.preventDefault();
          e.stopImmediatePropagation();
          widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, true);
    }

    function collectSelections() {
      const result = [];
      cfg.groups.forEach(group => {
        if (group.type === 'dimensions') {
          const v_l = group.variations[0] || {};
          const v_b = group.variations[1] || {};
          const inp_l = widget.querySelector(`.fw-dim-input[name^="fw_diml_"][data-group="${group.id}"]`);
          const inp_b = widget.querySelector(`.fw-dim-input[name^="fw_dimb_"][data-group="${group.id}"]`);
          const val_l = inp_l && inp_l.value !== '' ? parseFloat(inp_l.value) : null;
          const val_b = inp_b && inp_b.value !== '' ? parseFloat(inp_b.value) : null;
          const min_l = v_l.min !== undefined ? parseFloat(v_l.min) : 0;
          const min_b = v_b.min !== undefined ? parseFloat(v_b.min) : 0;
          const step_l = v_l.step > 0 ? parseFloat(v_l.step) : 1;
          const step_b = v_b.step > 0 ? parseFloat(v_b.step) : 1;
          const price_l = v_l.price !== undefined ? parseFloat(v_l.price) : 0;
          const price_b = v_b.price !== undefined ? parseFloat(v_b.price) : 0;
          const steps_l = val_l !== null && val_l > min_l ? Math.floor((val_l - min_l) / step_l) : 0;
          const steps_b = val_b !== null && val_b > min_b ? Math.floor((val_b - min_b) / step_b) : 0;
          if (val_l !== null) {
            result.push({
              group_id: group.id,
              group_name: group.name + ' - Lengte',
              type: 'dimension_length',
              label: val_l + (v_l.label ? ' ' + v_l.label : ''),
              price: price_l * steps_l
            });
          }
          if (val_b !== null) {
            result.push({
              group_id: group.id,
              group_name: group.name + ' - Breedte',
              type: 'dimension_width',
              label: val_b + (v_b.label ? ' ' + v_b.label : ''),
              price: price_b * steps_b
            });
          }
          return;
        }
        if (group.type === 'text') {
          const inp = widget.querySelector(`.fw-text-input[data-group="${group.id}"]`);
          if (!inp || inp.value.trim() === '') return;
          const v0 = group.variations[0] || {};
          result.push({
            group_id: group.id,
            group_name: group.name,
            type: 'text',
            label: inp.value.trim(),
            price: parseFloat(v0.price) || 0
          });
          return;
        }
        const checked = widget.querySelector(`input[data-group="${group.id}"]:checked`);
        if (!checked) return;
        result.push({
          group_id: group.id,
          group_name: group.name,
          type: group.type,
          label: checked.dataset.label || '',
          price: parseFloat(checked.dataset.price) || 0
        });
      });
      return result;
    }

    function recalc() {
      let base = parseFloat(cfg.base_price) || 0;
      const selField = document.getElementById('fw-sel-' + pid);
      const exField = document.getElementById('fw-extra-' + pid);

      let price = base;
      let optionTotal = 0;

      // Controleer of er een lengte-groep is
      const lengthWrapper = widget.querySelector('.fw-lengths-wrapper');
      if (lengthWrapper) {
        const select = lengthWrapper.querySelector('.fw-length-type');
        const customInput = lengthWrapper.querySelector('.fw-custom-length');
        const settings = lengthWrapper.dataset.settings ? JSON.parse(lengthWrapper.dataset.settings) : null;
        if (select && settings) {
          let lengthPrice = null;
          if (select.value && select.value.startsWith('vast_')) {
            // Vaste lengte
            const val = parseFloat(select.value.replace('vast_', ''));
            for (let j = 0; j < settings.lengths.length; j++) {
              if (parseFloat(settings.lengths[j].value) === val) {
                lengthPrice = parseFloat(settings.lengths[j].price);
                break;
              }
            }
          } else if (select.value === 'maatwerk' && customInput && customInput.value !== '') {
            // Maatwerk lengte
            const customVal = parseFloat(customInput.value.replace(',', '.'));
            for (let k = 0; k < settings.lengths.length; k++) {
              if (customVal * 10 <= parseFloat(settings.lengths[k].value)) {
                lengthPrice = parseFloat(settings.lengths[k].price);
                break;
              }
            }
          }
          if (lengthPrice !== null && !isNaN(lengthPrice)) {
            optionTotal += lengthPrice;
          }
        }
      }

      // Voeg eventueel andere opties toe (zoals radio's)
      optionTotal += Array.from(widget.querySelectorAll('.fw-option input[type="radio"]:checked')).reduce((sum, radio) => {
        const add = parseFloat(radio.dataset.price) || 0;
        return sum + add;
      }, 0);

      price = base + optionTotal;

      // Debug: log de prijs
      console.log('recalc: base', base, 'optionTotal', optionTotal, 'price', price);

      // Update custom prijs element
      const priceEl = document.getElementById('fw-price-' + pid);
      if (priceEl) priceEl.textContent = `€ ${price.toFixed(2).replace('.', ',')}`;

      // Update WooCommerce hoofdprijs element (alleen hoofdprijs, niet oude prijs)
      const formatted = price.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const mainPrice = document.querySelector('.single-product .price .amount:not(del .amount)');
      if (mainPrice) {
        mainPrice.textContent = `€ ${formatted}`;
      } else {
        document.querySelectorAll('.woocommerce-Price-amount, .price .amount, .price').forEach(el => {
          if (!el.closest('del')) {
            el.textContent = `€ ${formatted}`;
          }
        });
      }

      // Update hidden fields
      if (selField) selField.value = JSON.stringify(collectSelections());
      if (exField) exField.value = (price - base).toFixed(2);

      // Trigger custom event for external listeners
      widget.dispatchEvent(new CustomEvent('fw-recalc', {
        detail: { pid, base, price },
        bubbles: true,
        cancelable: true
      }));
    }

    // recalc als property zodat we hem buitenaf kunnen aanroepen
    widget._fwRecalc = recalc;
    recalc();
  }
})();
