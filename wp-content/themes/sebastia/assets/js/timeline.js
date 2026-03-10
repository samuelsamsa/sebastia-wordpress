(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var grid = document.querySelector('.timeline-grid');
    if (!grid) return;

    var yearButton = document.getElementById('sortYearButton');
    var alphaButton = document.getElementById('sortAlphaButton');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.timeline-row'));
    var years = Array.prototype.slice.call(document.querySelectorAll('.year'));

    function sortRows(compareFn) {
      var sortableRows = Array.prototype.slice.call(grid.querySelectorAll('.timeline-row'));
      sortableRows.sort(compareFn);
      sortableRows.forEach(function (row) {
        grid.appendChild(row);
      });
    }

    if (yearButton) {
      yearButton.addEventListener('click', function () {
        var order = yearButton.dataset.order;
        sortRows(function (a, b) {
          var aYear = parseInt(a.querySelector('.entry-time').dataset.start, 10);
          var bYear = parseInt(b.querySelector('.entry-time').dataset.start, 10);
          return order === 'asc' ? aYear - bYear : bYear - aYear;
        });
        yearButton.dataset.order = order === 'asc' ? 'desc' : 'asc';
        yearButton.textContent = order === 'asc' ? 'Year ↓' : 'Year ↑';
      });
    }

    if (alphaButton) {
      alphaButton.addEventListener('click', function () {
        var order = alphaButton.dataset.order;
        sortRows(function (a, b) {
          var aTitle = a.querySelector('.entry-title span').textContent.toLowerCase();
          var bTitle = b.querySelector('.entry-title span').textContent.toLowerCase();
          if (aTitle < bTitle) return order === 'asc' ? -1 : 1;
          if (aTitle > bTitle) return order === 'asc' ? 1 : -1;
          return 0;
        });
        alphaButton.dataset.order = order === 'asc' ? 'desc' : 'asc';
        alphaButton.textContent = order === 'asc' ? 'A-Z ↓' : 'A-Z ↑';
      });
    }

    rows.forEach(function (row) {
      row.addEventListener('mouseenter', function () {
        var rowYears = (row.dataset.years || '').split(',');
        years.forEach(function (year) {
          if (rowYears.indexOf(year.dataset.year) !== -1) {
            year.classList.add('highlight');
          }
        });
      });

      row.addEventListener('mouseleave', function () {
        var rowYears = (row.dataset.years || '').split(',');
        years.forEach(function (year) {
          if (rowYears.indexOf(year.dataset.year) !== -1) {
            year.classList.remove('highlight');
          }
        });
      });
    });

    rows.forEach(function (row, index) {
      window.setTimeout(function () {
        row.classList.add('is-visible');
      }, index * 50);
    });
  });
})();
