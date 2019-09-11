// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Environment component
// -----------------------------------------------------------------------------

// A simple framework for feature and environment testing.

AmbientImpact.addComponent('environment', function(aiEnvironment, $) {
  'use strict';

  // Private object containing registered tests, keyed by machine name:
  // machineName: {
  //    callback:   <callback>,
  //    htmlClassName:  <string>,
  //    result:     <bool>
  // }
  var tests = {};


  // Runs the passed callback, and applies the htmlClassName to the <html>
  // element based on the callback return, in Modernizr format:
  // - html.<class> if callback returns something that evaluates to true
  // - html.no-<class> otherwise
  // (If htmlClassName is an empty string or evaluates to false, no classes
  // are added.)
  this.addTest = function(machineName, htmlClassName, callback) {
    var result = callback.call(this); // Run the callback

    tests[machineName] = {
      callback:   callback,
      htmlClassName:  htmlClassName,
      result:     result
    }

    if (htmlClassName) {
      if (result) {
        $('html').addClass(htmlClassName);
      } else {
        $('html').addClass('no-' + htmlClassName);
      }
    }
  };


  // Get the result of a specified test
  this.getResult = function(machineName) {
    if (tests.hasOwnProperty(machineName)) {
      return tests[machineName].result;
    }
    return null;
  };


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
