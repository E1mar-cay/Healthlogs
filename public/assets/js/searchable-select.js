(function () {
  function enhance(select) {
    if (select.dataset.searchableReady === '1') return;
    select.dataset.searchableReady = '1';

    var input = document.createElement('input');
    input.type = 'search';
    input.placeholder = select.dataset.searchPlaceholder || 'Search by name or location...';
    input.autocomplete = 'off';
    input.className = 'mb-2 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400';
    select.size = 6;
    select.parentNode.insertBefore(input, select);

    input.addEventListener('input', function () {
      var term = input.value.trim().toLowerCase();
      Array.from(select.options).forEach(function (option) {
        if (!option.value) {
          option.hidden = false;
          return;
        }
        option.hidden = term !== '' && !option.textContent.toLowerCase().includes(term);
      });
    });
  }

  function init() {
    document.querySelectorAll('select[data-searchable-select]').forEach(enhance);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
