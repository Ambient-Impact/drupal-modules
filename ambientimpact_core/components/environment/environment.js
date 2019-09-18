// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Environment component
// -----------------------------------------------------------------------------

// A collection of front-end utilities to determine the current browser
// environment.

AmbientImpact.addComponent('environment', function(aiEnvironment, $) {
  'use strict';

  // CSS.supports() wrapper. Removes need to check for presence of
  // CSS.supports().
  if (AmbientImpact.objectPathExists('CSS.supports')) {
    this.cssSupports = function(property, value) {
      return CSS.supports(property, value);
    }
  } else {
    this.cssSupports = function(property, value) {
      // If not supported, always return false. We could use a polyfill
      // for this, but those browsers are dwindling in number.
      return false;
    }
  }
});
