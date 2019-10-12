// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Toolbar placeholder apply component
// -----------------------------------------------------------------------------

// This attempts to retrieve and apply the offsets stored for the current theme
// as an inline <style> element in the <head> to reduce layout shifting when the
// toolbar is initialized by the Drupal core JavaScript.

(function() {
  'use strict';

  // Bail if the browser doesn't cut the mustard.
  if (!document.head || !('classList' in document.documentElement)) {
    return;
  }

  /**
   * The current theme's machine name.
   *
   * @type {String}
   *
   * @see \Drupal\ambientimpact_ux\EventSubscriber\Preprocess\PreprocessHTMLEventSubscriber::preprocessHTML()
   *   This PHP method adds the 'data-drupal-theme' attribute.
   */
  var currentTheme = document.documentElement.getAttribute('data-drupal-theme');

  if (!currentTheme) {
    return;
  }

  /**
   * Stored viewport offsets for the current theme, if found.
   *
   * @type {String|Object}
   */
  var offsets;

  try {
    offsets = localStorage.getItem(
      'Drupal.AmbientImpact.toolbar.offsets.' + currentTheme
    );
  } catch (error) {
    console.error(error);

    return;
  }

  if (typeof offsets === null) {
    return;
  }

  // Try to parse the JSON string into an object.
  try {
    offsets = JSON.parse(offsets);
  } catch (error) {
    console.error(error);

    return;
  }

  // Just in case.
  if (
    !offsets ||
    !offsets.hasOwnProperty('top') ||
    !offsets.hasOwnProperty('left') ||
    !offsets.hasOwnProperty('right')
  ) {
    return;
  }

  /**
   * The <style> element containing the viewport offset compensations.
   *
   * @type {HTMLElement}
   */
  var styleElement = document.createElement('style');

  styleElement.type = 'text/css';
  styleElement.id   = 'ambientimpact-toolbar-placeholder-styles';

  // Generate the <style> element contents, containing the specific offset
  // values that were found as CSS custom properties.
  styleElement.appendChild(document.createTextNode(
    ':root {' +
      '--toolbar-placeholder-top: '   + offsets.top   + 'px;' +
      '--toolbar-placeholder-left: '  + offsets.left  + 'px;' +
      '--toolbar-placeholder-right: ' + offsets.right + 'px;' +
    '}'
  ));

  document.head.appendChild(styleElement);

  // If jQuery has loaded, use it to bind to the first triggered viewport offset
  // change event to remove the <style> element and add a class to the <body> to
  // indicate the placeholder is no longer needed. The toolbar is assumed to be
  // successfully initialized when this is triggered.
  if ('jQuery' in window) {
    jQuery(document)
      .one('drupalViewportOffsetChange.aiToolbarPlaceholderApply', function(
        event
      ) {
        jQuery(styleElement).remove();
        jQuery('body').addClass('toolbar-placeholder-disabled');
      });

  // If jQuery isn't found, fall back to listening to the window's load event to
  // remove the <style> element and add a <body> class indicating the
  // placeholder is no longer needed, provided that the toolbar has the
  // 'toolbar-oriented' class.
  } else {
    window.addEventListener('load', function(event) {
      var toolbar = document.getElementById('toolbar-administration');

      if (
        !toolbar ||
        (!'classList' in toolbar) ||
        !toolbar.classList.contains('toolbar-oriented')
      ) {
        return;
      }

      document.head.removeChild(styleElement);
      document.body.classList.add('toolbar-placeholder-disabled');
    });
  }
})();
