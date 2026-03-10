(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var aboutGrid = document.querySelector('.about-grid');
    if (!aboutGrid) return;

    var blocks = aboutGrid.querySelectorAll('.blocks > *, .wp-block-column > *');
    var figures = Array.prototype.slice.call(aboutGrid.querySelectorAll('figure.about-media'));

    if ('IntersectionObserver' in window && blocks.length) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });

      blocks.forEach(function (block, index) {
        block.style.transitionDelay = (index * 40) + 'ms';
        observer.observe(block);
      });
    } else {
      blocks.forEach(function (block) {
        block.classList.add('is-visible');
      });
    }

    if (!figures.length) return;

    var isTouchDevice =
      ('ontouchstart' in window) ||
      (navigator.maxTouchPoints && navigator.maxTouchPoints > 0);
    if (!isTouchDevice) return;

    var touchStartX = 0;
    var touchStartY = 0;
    var touchMoved = false;
    var suppressClickUntil = 0;

    function closeAllCaptions(exceptFigure) {
      figures.forEach(function (figure) {
        if (figure !== exceptFigure) {
          figure.classList.remove('is-caption-visible');
        }
      });
    }

    aboutGrid.addEventListener('touchstart', function (event) {
      var touch = event.changedTouches && event.changedTouches[0];
      if (!touch) return;
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      touchMoved = false;
    }, { passive: true });

    aboutGrid.addEventListener('touchmove', function (event) {
      var touch = event.changedTouches && event.changedTouches[0];
      if (!touch) return;
      if (
        Math.abs(touch.clientX - touchStartX) > 10 ||
        Math.abs(touch.clientY - touchStartY) > 10
      ) {
        touchMoved = true;
      }
    }, { passive: true });

    aboutGrid.addEventListener('touchend', function (event) {
      if (touchMoved) return;
      var figure = event.target.closest('figure.about-media');
      if (!figure || !aboutGrid.contains(figure)) {
        closeAllCaptions();
        return;
      }

      var isOpen = figure.classList.contains('is-caption-visible');
      closeAllCaptions(figure);
      figure.classList.toggle('is-caption-visible', !isOpen);
      suppressClickUntil = Date.now() + 500;
      event.preventDefault();
    }, { passive: false });

    aboutGrid.addEventListener('click', function (event) {
      if (Date.now() < suppressClickUntil) return;
      var figure = event.target.closest('figure.about-media');
      if (!figure || !aboutGrid.contains(figure)) {
        closeAllCaptions();
      }
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('.about-grid')) {
        closeAllCaptions();
      }
    });
  });
})();
