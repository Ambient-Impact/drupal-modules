// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Font component
// -----------------------------------------------------------------------------

AmbientImpact.onGlobals(['FontFaceObserver'], function() {
AmbientImpact.addComponent('font', function(aiFont, $) {
  'use strict';

  // The FontFaceObserver time out, in milliseconds. Defaults to 10 seconds.
  this.timeout = 10000;

  // Array of machine names of fonts that have loaded.
  this.loaded = [];

  /**
   * Font status object for loading or loaded fonts.
   *
   * Fonts are keyed by their machine name, with the value being an object with:
   *
   * - loaded: a boolean indicating whether this font has been loaded yet.
   *
   * - className: the class name to add to the <body> element when this font has
   *   loaded.
   *
   * @type {null|Object}
   */
  var fontStatus = null;

  /**
   * Get class to apply to the <body> element when a given font has loaded.
   *
   * @param {String} machineName
   *   The machine name to identify this font by.
   *
   * @return {String}
   *   The class to apply to the <body> element when this font is loaded.
   */
  function getBodyLoadedClass(machineName) {
    return 'font-loaded-' + machineName;
  };

  /**
   * Store a given font's status and other information in localStorage.
   *
   * @param {String} machineName
   *   The machine name to identify this font by.
   *
   * @param {Boolean} loaded
   *   True if this font has loaded, false otherwise.
   */
  function storeFontStatus(machineName, loaded) {
    // try {} catch {} in case of no support for localStorage or some other
    // error.
    try {
      if (fontStatus === null) {
        // Attempt to read any existing data that's in localStorage, so we don't
        // overwrite it.
        var existing = JSON.parse(
          localStorage.getItem('Drupal.AmbientImpact.font.fonts')
        );

        // If localStorage is able to find the item, use it, otherwise it'll be
        // null.
        if (existing !== null) {
          fontStatus = existing;
        } else {
          fontStatus = {};
        }
      }

      // If this font doesn't have a key, add it.
      if (!(machineName in fontStatus)) {
        fontStatus[machineName] = {};
      }

      fontStatus[machineName].loaded    = loaded;
      fontStatus[machineName].className = getBodyLoadedClass(machineName);

      localStorage.setItem(
        'Drupal.AmbientImpact.font.fonts',
        JSON.stringify(fontStatus)
      );
    } catch (error) {
      console.error(error);
    }
  };

  /**
   * Load one or more font families.
   *
   * @param {object} fontFamilies
   *   An object containing one or more key/value pairs. Keys are the font
   *   machine (used in the <body> class to indicate loaded state) and the value
   *   is the string font-family name used in the CSS @font-face definition for
   *   the font.
   *
   * @see https://www.filamentgroup.com/lab/font-events.html
   *   Font loading strategy inspired by the Filament Group's font events.
   */
  this.load = function(fontFamilies) {
    $.each(fontFamilies, function(machineName, fontFamilyName) {
      // Don't try to load a font more than once.
      if (aiFont.loaded.indexOf(machineName) > -1) {
        return true;
      }

      // Set as not loaded yet.
      storeFontStatus(machineName, false);

      new FontFaceObserver(fontFamilyName)
        .load(null, aiFont.timeout)
        .then(function() {
          // Add this font to the array of loaded fonts.
          aiFont.loaded.push(machineName);

          // Set as loaded.
          storeFontStatus(machineName, true);

          // Trigger an event to inform other code that this font has loaded.
          $(document).trigger('fontloaded', [machineName]);

          // Add the body class for this font so CSS styles can be applied.
          $('body').addClass(getBodyLoadedClass(machineName));
        });
    });
  };

  /**
   * Determine if a given font has been loaded yet.
   *
   * @param {String} fontMachineName
   *   The machine name of the font, as a key that can be passed to this.load().
   *
   * @return {Boolean}
   *   True if the font with the given machine name has loaded, false otherwise.
   */
  this.isLoaded = function(fontMachineName) {
    return this.loaded.indexOf(fontMachineName) > -1;
  };
});
});
