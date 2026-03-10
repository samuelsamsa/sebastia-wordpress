(function () {
  'use strict';

  function fadeInSequence(container, options) {
    var settings = options || {};
    var targetSelector = settings.targetSelector || '[data-fade]';
    var baseDelay = typeof settings.baseDelay === 'number' ? settings.baseDelay : 120;
    var includeGallery = settings.includeGallery !== false;

    var root = typeof container === 'string' ? document.querySelector(container) : container;
    if (!root) return;

    var sequence = Array.prototype.slice.call(root.querySelectorAll(targetSelector));

    if (includeGallery) {
      var galleryFigures = Array.prototype.slice.call(root.querySelectorAll('.gallery figure'));
      galleryFigures.forEach(function (fig) {
        fig.setAttribute('data-fade', '');
      });
      sequence = sequence.concat(galleryFigures);
    }

    sequence.forEach(function (item, index) {
      window.setTimeout(function () {
        item.classList.add('is-visible');
      }, index * baseDelay);
    });
  }

  function initMobileNav() {
    var hamburger = document.getElementById('hamburger');
    var panel = document.getElementById('mobile-nav-panel');
    if (!hamburger || !panel) return;

    function closePanel() {
      hamburger.setAttribute('aria-expanded', 'false');
      panel.classList.remove('is-open');
    }

    hamburger.addEventListener('click', function () {
      var open = hamburger.getAttribute('aria-expanded') === 'true';
      hamburger.setAttribute('aria-expanded', String(!open));
      panel.classList.toggle('is-open');
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && panel.classList.contains('is-open')) {
        closePanel();
      }
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('#mobile-nav-panel') && !event.target.closest('#hamburger')) {
        closePanel();
      }
    });
  }

  function optimizeImages() {
    var images = document.querySelectorAll('img');
    images.forEach(function (img) {
      // Keep explicitly marked critical images as eager/high priority.
      if (!img.hasAttribute('loading')) {
        img.setAttribute('loading', 'lazy');
      }
      if (!img.hasAttribute('decoding')) {
        img.setAttribute('decoding', 'async');
      }
      if (!img.hasAttribute('fetchpriority') && img.getAttribute('loading') === 'lazy') {
        img.setAttribute('fetchpriority', 'low');
      }
    });
  }

  window.SubstituteSite = {
    fadeInSequence: fadeInSequence
  };

  document.addEventListener('DOMContentLoaded', function () {
    optimizeImages();
    initMobileNav();
  });
})();
