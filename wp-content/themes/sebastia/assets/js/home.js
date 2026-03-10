(function () {
  'use strict';

  var MOBILE_LAYOUT_QUERY = '(max-width: 768px), (max-width: 1024px) and (orientation: portrait)';
  var TABLET_PORTRAIT_QUERY = '(min-width: 769px) and (max-width: 1024px) and (orientation: portrait)';

  document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.querySelector('.mosaic-wrapper');
    if (!wrapper) return;

    var mask = wrapper.querySelector('#mask');
    var stones = wrapper.querySelector('#shapes');
    var drone = wrapper.querySelector('img');
    var lines = wrapper.querySelector('#lines');
    var header = document.querySelector('.header');

    var panelWrapper = document.getElementById('stone-panel-wrapper');
    var panel = document.getElementById('stone-panel');
    var searchOverlay = document.getElementById('search-overlay');
    var toggleSearch = document.getElementById('toggle-search');
    var iconPath = document.getElementById('icon-path');
    var iconSvg = document.getElementById('search-icon');
    var searchInput = document.getElementById('search-input');
    var indexContainer = document.querySelector('.index-container');
    var entries = Array.prototype.slice.call(document.querySelectorAll('.entry-index-item'));
    var headers = Array.prototype.slice.call(document.querySelectorAll('.header-filter'));
    var rotatedEl = wrapper.querySelector('.mosaic-rotated');
    var isMobile = window.matchMedia(MOBILE_LAYOUT_QUERY).matches;
    var isTabletPortrait = window.matchMedia(TABLET_PORTRAIT_QUERY).matches;

    if (!mask || !stones || !drone || !lines || !header || !panelWrapper || !panel || !toggleSearch || !iconPath || !iconSvg) {
      return;
    }

    var moved = false;
    var activeSlug = null;
    var searchOpen = false;
    var headerResizeObserver = null;
    var magnifierPath = 'M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm5 12l5 5';
    var closePath = 'M4 4l16 16M20 4L4 20';

    var mosaicGroups = Array.prototype.slice.call(stones.querySelectorAll('g'));
    mosaicGroups.sort(function () { return Math.random() - 0.5; });
    mosaicGroups.forEach(function (group, index) {
      window.setTimeout(function () {
        group.classList.add('is-visible');
      }, index * 15);
    });

    mask.classList.remove('is-visible');
    lines.classList.remove('is-visible');
    drone.classList.remove('is-visible');
    header.classList.remove('is-visible');

    requestAnimationFrame(function () {
      stones.classList.add('is-visible');

      window.setTimeout(function () {
        mask.classList.add('is-visible');

        window.setTimeout(function () {
          Array.prototype.slice.call(header.children).forEach(function (item, index) {
            window.setTimeout(function () {
              item.classList.add('is-visible');
            }, index * 120);
          });

          window.setTimeout(function () {
            lines.classList.add('is-visible');
            window.setTimeout(function () {
              drone.classList.add('is-visible');
            }, 1200);
          }, 100);
        }, 100);
      }, 1200);
    });

    function updateIndexWidth() {
      if (!indexContainer) return;
      if (!isMobile && panelWrapper.classList.contains('active')) {
        indexContainer.style.width = '70vw';
      } else {
        indexContainer.style.width = '100%';
      }
    }

    function updateCollapsedPanelHeight() {
      if (!isMobile) return;
      var headerEl = panelWrapper.querySelector('.entry-card-home > header');
      if (!headerEl) return;

      var computed = window.getComputedStyle(headerEl);
      var marginTop = parseFloat(computed.marginTop) || 0;
      var marginBottom = parseFloat(computed.marginBottom) || 0;
      var headerHeight = Math.ceil(headerEl.getBoundingClientRect().height + marginTop + marginBottom);

      if (headerHeight > 0) {
        document.documentElement.style.setProperty('--stone-sheet-collapsed-height', headerHeight - 2 + 'px');
      }
    }

    function observePanelHeaderHeight() {
      if (!isMobile) return;

      if (headerResizeObserver) {
        headerResizeObserver.disconnect();
        headerResizeObserver = null;
      }

      var headerEl = panelWrapper.querySelector('.entry-card-home > header');
      if (!headerEl) return;

      updateCollapsedPanelHeight();

      if ('ResizeObserver' in window) {
        headerResizeObserver = new ResizeObserver(function () {
          updateCollapsedPanelHeight();
        });
        headerResizeObserver.observe(headerEl);
      }
    }

    function setPanelState(state) {
      var open = Boolean(state.open);
      var expanded = Boolean(state.expanded);
      panelWrapper.classList.toggle('active', open);
      panelWrapper.classList.toggle('expanded', open && expanded);
      document.body.classList.toggle('stone-sheet-open', open);
      document.body.classList.toggle('stone-sheet-expanded', open && expanded);
    }

    function closePanel() {
      setPanelState({ open: false, expanded: false });
      if (headerResizeObserver) {
        headerResizeObserver.disconnect();
        headerResizeObserver = null;
      }
      window.setTimeout(function () {
        panel.innerHTML = '';
      }, 300);
      activeSlug = null;

      stones.querySelectorAll('g.active').forEach(function (group) {
        group.classList.remove('active');
        group.removeAttribute('data-selected');
      });

      document.querySelectorAll('.entry-index-item.active').forEach(function (entry) {
        entry.classList.remove('active');
      });

      updateIndexWidth();
    }

    var observer = new MutationObserver(updateIndexWidth);
    observer.observe(panelWrapper, {
      attributes: true,
      attributeFilter: ['class']
    });
    updateIndexWidth();

    panelWrapper.addEventListener('click', function (event) {
      if (event.target.closest('.entry-close')) {
        closePanel();
        return;
      }

      if (!isMobile || !panelWrapper.classList.contains('active') || panelWrapper.classList.contains('expanded')) {
        return;
      }

      if (event.target.closest('.entry-card-home > header')) {
        setPanelState({ open: true, expanded: true });
      }
    });

    if (isMobile) {
      var touchStartY = 0;

      panel.addEventListener('touchstart', function (event) {
        if (event.touches.length !== 1) return;
        touchStartY = event.touches[0].clientY;
      }, { passive: true });

      panel.addEventListener('touchmove', function (event) {
        if (!panelWrapper.classList.contains('expanded') || event.touches.length !== 1) return;

        var currentY = event.touches[0].clientY;
        var pullingDown = currentY > touchStartY;
        var pullingUp = currentY < touchStartY;
        var atTop = panel.scrollTop <= 0;
        var atBottom = panel.scrollTop + panel.clientHeight >= panel.scrollHeight - 1;

        if ((atTop && pullingDown) || (atBottom && pullingUp)) {
          event.preventDefault();
        }
      }, { passive: false });
    }

    async function selectEntry(slug) {
      if (!slug) return;

      if (activeSlug === slug) {
        closePanel();
        return;
      }

      activeSlug = slug;

      stones.querySelectorAll('g.active').forEach(function (group) {
        group.classList.remove('active');
        group.removeAttribute('data-selected');
      });

      var selectedGroup = stones.querySelector('g[data-name="' + slug + '"]');
      if (selectedGroup) {
        selectedGroup.classList.add('active');
        selectedGroup.dataset.selected = 'true';
      }

      document.querySelectorAll('.entry-index-item.active').forEach(function (entry) {
        entry.classList.remove('active');
      });

      var indexItem = document.querySelector('.entry-index-item[data-slug="' + slug + '"]');
      if (indexItem) {
        indexItem.classList.add('active');
      }

      try {
        var response = await fetch('?stone=' + encodeURIComponent(slug), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) throw new Error('Entry not found');

        panel.innerHTML = await response.text();
        observePanelHeaderHeight();
        window.requestAnimationFrame(updateCollapsedPanelHeight);
        setPanelState({ open: true, expanded: !isMobile });
        updateIndexWidth();
      } catch (error) {
        console.error(error);
      }
    }

    if (!isMobile) {
      [mask, stones, drone, lines].forEach(function (element) {
        element.style.height = '100%';
        element.style.width = 'auto';
        element.style.transformOrigin = '0 0';
      });
    } else {
      var mobileAspectMode = isTabletPortrait ? 'xMidYMid meet' : 'xMidYMid slice';
      [mask, stones, lines].forEach(function (svg) {
        svg.setAttribute('preserveAspectRatio', mobileAspectMode);
      });
    }

    requestAnimationFrame(function () {
      var scale = 1;
      var panX = 0;
      var panY = 0;
      var initialDistance = null;
      var initialScale = 1;
      var pointers = [];
      var isPanning = false;
      var startX;
      var startY;
      var maxScale = 8;
      var zoomSpeed = 0.02;

      if (!isMobile) {
        var wrapperRect = wrapper.getBoundingClientRect();
        var imageRect = drone.getBoundingClientRect();
        var overflowX = imageRect.width - wrapperRect.width;
        var overflowY = imageRect.height - wrapperRect.height;
        var desktopLeftBias = wrapperRect.width * 0.01; // slight left shift on initial load

        if (overflowX > 0) {
          panX = -overflowX / 2;
          panX -= Math.min(desktopLeftBias, overflowX / 2);
        }
        if (overflowY > 0) panY = -overflowY / 2;
      }

      function updateTransform() {
        if (isMobile) {
          var halfViewportWidth = window.innerWidth / 2;
          var halfViewportHeight = window.innerHeight / 2;
          var maxPanX = halfViewportWidth * (scale - 1);
          var maxPanY = halfViewportHeight * (scale - 1);

          panX = Math.max(-maxPanX, Math.min(maxPanX, panX));
          panY = Math.max(-maxPanY, Math.min(maxPanY, panY));

          if (rotatedEl) {
            rotatedEl.style.transformOrigin = 'center center';
            rotatedEl.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + scale + ') rotate(90deg)';
          }
          return;
        }

        var rect = wrapper.getBoundingClientRect();
        var image = drone.getBoundingClientRect();
        var imageWidth = image.width * scale;
        var imageHeight = image.height * scale;

        panX = Math.min(Math.max(panX, Math.min(0, rect.width - imageWidth)), 0);
        panY = Math.min(Math.max(panY, Math.min(0, rect.height - imageHeight)), 0);

        var transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + scale + ')';
        mask.style.transform = transform;
        stones.style.transform = transform;
        drone.style.transform = transform;
        lines.style.transform = transform;
      }

      wrapper.addEventListener('wheel', function (event) {
        if (isMobile) return;
        event.preventDefault();

        var rect = wrapper.getBoundingClientRect();
        var mouseX = event.clientX - rect.left;
        var mouseY = event.clientY - rect.top;
        var prevScale = scale;

        scale += (event.deltaY < 0 ? 1 : -1) * zoomSpeed * scale;
        scale = Math.max(1, Math.min(maxScale, scale));

        panX -= (mouseX - panX) * (scale / prevScale - 1);
        panY -= (mouseY - panY) * (scale / prevScale - 1);

        updateTransform();
      }, { passive: false });

      wrapper.addEventListener('pointerdown', function (event) {
        moved = false;

        if (event.pointerType === 'touch') {
          pointers = pointers.filter(function (pointer) {
            return pointer.pointerId !== event.pointerId;
          });
          pointers.push(event);
        }

        if (pointers.length <= 1) {
          isPanning = true;
          startX = event.clientX - panX;
          startY = event.clientY - panY;
        } else {
          isPanning = false;
        }
      });

      window.addEventListener('pointermove', function (event) {
        if (event.pointerType === 'touch') {
          pointers = pointers.map(function (pointer) {
            return pointer.pointerId === event.pointerId ? event : pointer;
          });
        }

        if (pointers.length === 2) {
          isPanning = false;
          moved = true;

          var dx = pointers[0].clientX - pointers[1].clientX;
          var dy = pointers[0].clientY - pointers[1].clientY;
          var distance = Math.hypot(dx, dy);
          var midX = (pointers[0].clientX + pointers[1].clientX) / 2;
          var midY = (pointers[0].clientY + pointers[1].clientY) / 2;

          if (!initialDistance) {
            initialDistance = distance;
            initialScale = scale;
          } else {
            var previousScale = scale;
            scale = Math.max(1, Math.min(maxScale, initialScale * (distance / initialDistance)));

            var centerX = window.innerWidth / 2;
            var centerY = window.innerHeight / 2;
            panX -= (midX - centerX - panX) * (scale / previousScale - 1);
            panY -= (midY - centerY - panY) * (scale / previousScale - 1);

            updateTransform();
          }
          return;
        }

        if (!isPanning) return;
        moved = true;
        panX = event.clientX - startX;
        panY = event.clientY - startY;
        updateTransform();
      });

      window.addEventListener('pointerup', function (event) {
        if (event.pointerType === 'touch') {
          pointers = pointers.filter(function (pointer) {
            return pointer.pointerId !== event.pointerId;
          });
          if (pointers.length < 2) {
            initialDistance = null;
          }
        }
        if (!pointers.length) {
          isPanning = false;
        }
      });

      window.addEventListener('pointercancel', function () {
        isPanning = false;
        pointers = [];
        initialDistance = null;
      });

      updateTransform();
    });

    stones.querySelectorAll('g').forEach(function (group) {
      var polygons = Array.prototype.slice.call(group.querySelectorAll('path'));
      var isTouchPointer = window.matchMedia('(pointer: coarse)').matches;

      if (!isTouchPointer) {
        polygons.forEach(function (path) {
          path.addEventListener('mouseenter', function () {
            if (activeSlug !== group.dataset.name) {
              group.classList.add('active');
            }
          });

          path.addEventListener('mouseleave', function () {
            if (activeSlug !== group.dataset.name) {
              group.classList.remove('active');
            }
          });
        });
      }

      group.addEventListener('click', function () {
        if (moved) return;
        selectEntry(group.dataset.name);
      });
    });

    entries.forEach(function (entry) {
      entry.addEventListener('click', function () {
        selectEntry(entry.dataset.slug);
      });
    });

    toggleSearch.addEventListener('click', function () {
      searchOpen = !searchOpen;
      if (searchOverlay) {
        searchOverlay.classList.toggle('is-open');
      }

      iconPath.setAttribute('d', searchOpen ? closePath : magnifierPath);
      iconSvg.style.transform = searchOpen ? 'rotate(90deg) scale(1.2)' : 'rotate(0deg) scale(1)';
    });

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        var query = searchInput.value.toLowerCase();

        entries.forEach(function (entry) {
          var text = Array.prototype.slice.call(entry.children)
            .map(function (cell) { return cell.textContent.toLowerCase(); })
            .join(' ');

          entry.style.display = text.indexOf(query) !== -1 ? '' : 'none';
        });
      });
    }

    window.addEventListener('resize', function () {
      var mobileNow = window.matchMedia(MOBILE_LAYOUT_QUERY).matches;
      if (mobileNow) {
        updateCollapsedPanelHeight();
      }
    }, { passive: true });

    function updateHeaderCaret(header) {
      var caret = header.querySelector('.caret');
      if (!caret) return;
      var hasChecked = header.querySelector('.filter-dropdown input[type="checkbox"]:checked');
      caret.textContent = hasChecked ? '[•]' : '[ ]';
    }

    headers.forEach(function (headerElement, headerIndex) {
      var dropdown = headerElement.querySelector('.filter-dropdown');
      if (!dropdown) return;

      var values = Array.from(new Set(entries
        .map(function (entry) {
          var cell = entry.querySelector('div:nth-child(' + (headerIndex + 1) + ')');
          return cell ? cell.textContent : '';
        })
        .filter(function (value) { return value !== ''; })))
        .sort();

      dropdown.innerHTML = values.map(function (value) {
        return '<label class="filter-option"><input type="checkbox" value="' + value + '"><span class="label-text">' + value + '</span></label>';
      }).join('');

      headerElement.addEventListener('click', function () {
        var isOpen = dropdown.classList.contains('is-open');
        document.querySelectorAll('.filter-dropdown').forEach(function (menu) {
          menu.classList.remove('is-open');
        });
        if (!isOpen) {
          dropdown.classList.add('is-open');
        }
      });

      dropdown.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
          updateHeaderCaret(headerElement);

          var activeFilters = headers.reduce(function (acc, currentHeader, idx) {
            var checkedValues = Array.prototype.slice.call(
              currentHeader.querySelectorAll('input[type="checkbox"]:checked')
            ).map(function (input) {
              return input.value;
            });

            if (checkedValues.length) {
              acc[idx + 1] = checkedValues;
            }
            return acc;
          }, {});

          entries.forEach(function (entry) {
            var show = true;

            Object.keys(activeFilters).forEach(function (columnIndex) {
              if (!show) return;
              var value = entry.querySelector('div:nth-child(' + columnIndex + ')');
              if (!value || activeFilters[columnIndex].indexOf(value.textContent) === -1) {
                show = false;
              }
            });

            entry.style.display = show ? '' : 'none';
          });
        });
      });
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('.header-filter')) {
        document.querySelectorAll('.filter-dropdown').forEach(function (dropdown) {
          dropdown.classList.remove('is-open');
        });
      }
    });
  });
})();
