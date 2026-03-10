(function () {
  'use strict';

  var MOBILE_LAYOUT_QUERY = '(max-width: 768px), (max-width: 1024px) and (orientation: portrait)';

  function parseConfig() {
    var node = document.getElementById('entry-page-config');
    if (!node) return { slug: '', titles: {} };

    try {
      return JSON.parse(node.textContent);
    } catch (error) {
      console.error('Failed to parse entry page config', error);
      return { slug: '', titles: {} };
    }
  }

  function initDesktopGalleryMasonry() {
    if (window.matchMedia(MOBILE_LAYOUT_QUERY).matches) return;

    var gallery = document.querySelector('.entry-card .gallery');
    if (!gallery) return;

    var rowHeight = parseInt(getComputedStyle(gallery).getPropertyValue('grid-auto-rows'), 10);
    var rowGap = parseInt(getComputedStyle(gallery).getPropertyValue('gap'), 10);

    if (!rowHeight && !rowGap) return;

    function setSpan(figure) {
      var img = figure.querySelector('img');
      if (!img) return;
      var height = img.getBoundingClientRect().height;
      var span = Math.ceil((height + rowGap) / (rowHeight + rowGap));
      figure.style.gridRowEnd = 'span ' + span;
    }

    gallery.querySelectorAll('figure').forEach(function (figure) {
      var img = figure.querySelector('img');
      if (!img) return;

      if (!img.complete) {
        img.addEventListener('load', function () { setSpan(figure); }, { once: true });
      } else {
        setSpan(figure);
      }
    });
  }

  function initLightbox(config) {
    var lightbox = document.querySelector('.lightbox');
    if (!lightbox) return;

    var caption = lightbox.querySelector('.lightbox-caption');
    var closeButton = lightbox.querySelector('.lightbox-close');
    var mediaContainer = lightbox.querySelector('.lightbox-media');

    if (!caption || !closeButton || !mediaContainer) return;

    function openWithImage(src, alt, imageCaption) {
      mediaContainer.innerHTML = '';
      var img = document.createElement('img');
      img.src = src;
      img.alt = alt || '';
      mediaContainer.appendChild(img);

      caption.textContent = imageCaption || '';
      lightbox.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    }

    function openWithSvg(originalSvg) {
      if (!originalSvg) return;

      var clone = originalSvg.cloneNode(true);
      var slug = String(config.slug || '').toLowerCase().trim().replace(/-/g, '_');

      clone.querySelectorAll('g.active').forEach(function (group) {
        group.classList.remove('active');
      });

      var activeGroup = clone.querySelector('g[data-name="' + slug + '"]');
      if (activeGroup) {
        activeGroup.classList.add('active');
      }

      mediaContainer.innerHTML = '';
      mediaContainer.appendChild(clone);
      caption.textContent = '';

      lightbox.classList.add('is-svg');
      lightbox.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.setAttribute('hidden', '');
      lightbox.classList.remove('is-svg');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('.entry-gallery img').forEach(function (img) {
      img.addEventListener('click', function () {
        var captionNode = img.closest('figure') && img.closest('figure').querySelector('figcaption');
        openWithImage(img.src, img.alt || '', captionNode ? captionNode.textContent : '');
      });
    });

    document.querySelectorAll('.entry-mosaic').forEach(function (mosaic) {
      mosaic.addEventListener('click', function () {
        var svg = mosaic.querySelector('svg');
        openWithSvg(svg);
      });
    });

    closeButton.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (event) {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !lightbox.hasAttribute('hidden')) {
        closeLightbox();
      }
    });
  }

  function initMapLabels(config) {
    var svg = document.querySelector('#map');
    if (!svg || !svg.parentElement) return;

    var container = svg.parentElement;
    var pageTitles = config.titles || {};

    document.querySelectorAll('#map g[data-name]').forEach(function (group) {
      var slug = group.dataset.name;
      var title = pageTitles[slug] || slug;

      var link = group.closest('a');
      if (!link) {
        link = document.createElementNS('http://www.w3.org/2000/svg', 'a');
        var url = (config.urls && config.urls[slug]) || '/entries/' + slug + '/';
        link.setAttribute('href', url);
        group.parentNode.insertBefore(link, group);
        link.appendChild(group);
      }

      var label = document.createElement('div');
      label.className = 'map-label-wrapper';
      label.textContent = title;
      label.style.position = 'absolute';
      label.style.padding = '2px 6px';
      label.style.background = 'var(--red)';
      label.style.color = 'var(--white)';
      label.style.borderRadius = '20px';
      label.style.whiteSpace = 'nowrap';
      label.style.zIndex = '100';
      label.style.pointerEvents = 'none';
      label.style.opacity = '0';
      label.style.transform = 'translateX(-6px)';
      label.style.transition = 'opacity 180ms ease, transform 180ms ease';

      container.appendChild(label);

      function updateLabelPosition() {
        var svgRect = svg.getBoundingClientRect();
        var viewBox = svg.viewBox.baseVal;
        var circle = group.querySelector('circle');
        if (!circle) return;

        var cx = parseFloat(circle.getAttribute('cx'));
        var cy = parseFloat(circle.getAttribute('cy'));

        var scale = Math.min(
          svgRect.width / viewBox.width,
          svgRect.height / viewBox.height
        );

        var offsetX = (svgRect.width - viewBox.width * scale) / 2;
        var offsetY = (svgRect.height - viewBox.height * scale) / 2;

        var x = (cx - viewBox.x) * scale + offsetX;
        var y = (cy - viewBox.y) * scale + offsetY;
        var gap = 20;

        label.style.left = (x + gap) + 'px';
        label.style.top = y + 'px';
      }

      group.addEventListener('mouseenter', function () {
        updateLabelPosition();
        label.style.opacity = '1';
        label.style.transform = 'translateX(0)';
      });

      group.addEventListener('mouseleave', function () {
        label.style.opacity = '0';
        label.style.transform = 'translateX(-6px)';
      });

      window.addEventListener('resize', function () {
        if (label.style.opacity === '1') {
          updateLabelPosition();
        }
      });
    });
  }

  function highlightActiveStone(config) {
    var stones = document.querySelector('.shapes_entry');
    var locations = document.querySelector('#map');
    if (!stones || !locations || !config.slug) return;

    var slug = String(config.slug);

    stones.querySelectorAll('g.active').forEach(function (group) {
      group.classList.remove('active');
    });
    locations.querySelectorAll('g.active').forEach(function (group) {
      group.classList.remove('active');
    });

    var stoneGroup = stones.querySelector('g[data-name="' + slug + '"]');
    var locationGroup = locations.querySelector('g[data-name="' + slug + '"]');

    if (stoneGroup) stoneGroup.classList.add('active');
    if (locationGroup) locationGroup.classList.add('active');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var entry = document.querySelector('.entry-card');
    if (!entry) return;

    var config = parseConfig();

    initDesktopGalleryMasonry();

    if (window.SubstituteSite && typeof window.SubstituteSite.fadeInSequence === 'function') {
      window.SubstituteSite.fadeInSequence(entry, {
        targetSelector: '[data-fade]',
        baseDelay: 120,
        includeGallery: true
      });
    }

    initLightbox(config);
    initMapLabels(config);
    highlightActiveStone(config);
  });
})();
