document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.fw-length-type').forEach(function(select) {
    function toggleCustomRow() {
      var wrap = select.closest('.fw-lengths-wrapper');
      if (!wrap) return;
      var customRow = wrap.querySelector('.fw-custom-length-row');
      if (!customRow) return;
      if (select.value === 'maatwerk') {
        customRow.style.display = '';
        var inp = customRow.querySelector('input');
        if (inp) inp.focus();
      } else {
        customRow.style.display = 'none';
        var err = customRow.querySelector('.fw-custom-length-error');
        if (err) err.style.display = 'none';
      }
    }
    select.addEventListener('change', toggleCustomRow);
    // Init state (ook bij reload/autofill)
    toggleCustomRow();
  });
});

