/* Waypoint theme — minimal JS enhancements */
(function () {
  'use strict';

  // Animate progress bars on page load.
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wpnt-progress-bar-fill').forEach(function (el) {
      var pct = parseFloat(el.dataset.pct) || 0;
      el.style.width = '0%';
      requestAnimationFrame(function () {
        el.style.transition = 'width 0.6s ease';
        el.style.width = pct + '%';
      });
    });
  });
})();
