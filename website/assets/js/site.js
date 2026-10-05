(function () {
  'use strict';

  function enhanceAccordions(root) {
    var groups = (root || document).querySelectorAll('.faq-accordion');
    groups.forEach(function (group) {
      if (group.getAttribute('data-enhanced') === '1') return;
      group.setAttribute('data-enhanced', '1');
      group.addEventListener('toggle', function (ev) {
        var target = ev.target;
        if (!(target instanceof HTMLDetailsElement) || !target.open) return;
        group.querySelectorAll('details.faq-item[open]').forEach(function (other) {
          if (other !== target) other.open = false;
        });
      }, true);
    });
  }

  function bindFaqButtons() {
    document.querySelectorAll('.faq-accordion .faq-item > summary').forEach(function (summary) {
      summary.setAttribute('role', 'button');
      summary.setAttribute('aria-expanded', summary.parentElement.open ? 'true' : 'false');
      summary.parentElement.addEventListener('toggle', function () {
        summary.setAttribute('aria-expanded', summary.parentElement.open ? 'true' : 'false');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    enhanceAccordions(document);
    bindFaqButtons();
  });
})();
