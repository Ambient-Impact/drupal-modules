// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Environment component
// -----------------------------------------------------------------------------

// A collection of front-end utilities to determine the current browser
// environment.

AmbientImpact.addComponent('environment', function(aiEnvironment, $) {
  'use strict';

  if (AmbientImpact.objectPathExists('CSS.supports')) {
    /**
     * CSS.supports() wrapper when it's available.
     *
     * @param {String} property
     *   The CSS property name to test for.
     *
     * @param {String} value
     *   The CSS property value to test for, for the 'property' parameter.
     *
     * @return {Boolean}
     *   The return value of CSS.supports() based on the passed property and
     *   value.
     */
    this.cssSupports = function(property, value) {
      return CSS.supports(property, value);
    }

  } else {
    /**
     * CSS.supports() dummy method for when it isn't supported.
     *
     * This is provided to avoid errors in browsers that don't have
     * CSS.supports() available, so that this method can safely be called
     * without having to check.
     *
     * @param {String} property
     *   The CSS property name to test for.
     *
     * @param {String} value
     *   The CSS property value to test for, for the 'property' parameter.
     *
     * @return {Boolean}
     *   Always returns false.
     */
    this.cssSupports = function(property, value) {
      return false;
    }
  }
});
