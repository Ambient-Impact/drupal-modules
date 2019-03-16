/* -----------------------------------------------------------------------------
  Ambient.Impact - Core - Font component
----------------------------------------------------------------------------- */

AmbientImpact.onGlobals(['FontFaceObserver'], function() {
AmbientImpact.addComponent('font', function(aiFont, $) {
  'use strict';

  // The FontFaceObserver time out, in milliseconds. Defaults to 10 seconds.
  this.timeout = 10000;

  // Array of machine names of fonts that have loaded.
  this.loaded = [];

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
      var className = 'font-loaded-' + machineName;

      // Don't try to load a font more than once.
      if (aiFont.loaded.indexOf(machineName) > -1) {
        return true;
      }

      new FontFaceObserver(fontFamilyName)
        .load(null, aiFont.timeout)
        .then(function() {
          // Trigger an event to inform other code that this font has loaded.
          $(document).trigger('fontloaded', [machineName]);

          // Add this font to the array of loaded fonts.
          aiFont.loaded.push(machineName);

          // Add the body class for this font so CSS styles can be applied.
          $('body').addClass(className);
        });
    });
  };
});
});
