// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Toolbar placeholder store component
// -----------------------------------------------------------------------------

// This stores the viewport offsets for the current theme in localStorage for
// toolbar.placeholder.apply.js to apply on a subsequent page load.

AmbientImpact.onGlobals([
  'Drupal.debounce',
  'localStorage.setItem',
], function() {
AmbientImpact.addComponent('toolbar.placeholder.store', function(
  aiToolbarPlaceholderStore, $
) {
  'use strict';

  /**
   * The current theme's machine name.
   *
   * @type {String}
   *
   * @see \Drupal\ambientimpact_ux\EventSubscriber\Preprocess\PreprocessHTMLEventSubscriber::preprocessHTML()
   *   This PHP method adds the 'data-drupal-theme' attribute.
   */
  var currentTheme = $(document.documentElement).attr('data-drupal-theme');

  $(document).on(
    'drupalViewportOffsetChange.aiToolbarPlaceholderStore',
    Drupal.debounce(function(event, offsets) {
      try {
        localStorage.setItem(
          'Drupal.AmbientImpact.toolbar.offsets.' + currentTheme,
          JSON.stringify(offsets)
        );
      } catch (error) {
        console.error(error);
      }
    }, 100));
});
});
