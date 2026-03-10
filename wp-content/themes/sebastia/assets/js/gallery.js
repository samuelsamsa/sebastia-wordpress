(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var items = Array.prototype.slice.call(document.querySelectorAll('.gallery-entry-item'));
    if (!items.length) return;

    var tops = items.map(function (item) { return item.offsetTop; });
    var lastRowTop = Math.max.apply(Math, tops);

    var eligibleItems = items.filter(function (item) {
      return item.offsetTop !== lastRowTop;
    });

    eligibleItems
      .map(function (item) { return { item: item, sort: Math.random() }; })
      .sort(function (a, b) { return a.sort - b.sort; })
      .slice(0, 13)
      .forEach(function (entry) {
        entry.item.classList.add('is-large');
      });

    function revealItem(item, index) {
      window.setTimeout(function () {
        item.classList.add('is-visible');
      }, index * 60);
    }

    items.forEach(function (item, index) {
      var img = item.querySelector('img');
      if (!img) {
        revealItem(item, index);
        return;
      }

      if (img.complete) {
        revealItem(item, index);
      } else {
        img.addEventListener('load', function () { revealItem(item, index); }, { once: true });
        img.addEventListener('error', function () { revealItem(item, index); }, { once: true });
      }
    });
  });
})();
