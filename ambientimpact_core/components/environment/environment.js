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


  // --- Default tests ---


  // Shortcut for Modernizr's test. This should only be used as a last resort,
  // and not to assume too much about a device, since even laptops now often
  // have touch screens. The only thing this will tell you is that the browser
  // claims to support touch events, and the user may or may not be using
  // a pointing device or keyboard as well. See:
  // http://www.stucox.com/blog/you-cant-detect-a-touchscreen/
  this.addTest('touch', '', function() {
    return (
      AmbientImpact.objectPathExists('Modernizr.touchevents') &&
      Modernizr.touchevents
    );
  });


  // Crude test against the user agent string to determine if we're likely
  // to be on a mobile device. Phrased this way because devices can and do
  // lie about what they are. Additionally, making too many assumptions about
  // a device based purely on whether it is classified as 'mobile' is very
  // risky since the lines often blur in some cases. For instance, some larger
  // devices could have significant power to render fancy effects while others
  // may be very limited. Input cannot be assumed either, as some mobile
  // devices can have a stylus that behaves more like a mouse than a touch
  // point, allowing hover, and it's possible to attach keyboards and mice
  // to Android devices, and some iPads.
  // tl;dr don't rely on this, and don't assume much
  this.addTest('probablyMobile', 'ambientimpact-probably-mobile', function() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i
      .test(navigator.userAgent);
  });


  // This sets an arbitrary distinction between low and high powered devices.
  // At the moment this makes the very crude assumption that touch == low
  // powered, and should be taken with a huge grain of salt. In the future,
  // a better way to actually determine the performance characteristics of the
  // device needs to be found. Potential ideas:
  // http://www.quirksmode.org/blog/archives/2016/03/rafp_a_proposal.html
  // https://developer.mozilla.org/en-US/docs/Web/API/Window/performance
  this.addTest('expensiveEffects', 'ambientimpact-expensive-effects', function() {
    return !this.getResult('touch');
  });


  // This is used to determine if we should use various hover effects.
  // The reasoning for this is that hover effects on touch aren't as useful,
  // and are mostly fired for compatibility with sites not designed for touch.
  // TO DO: this does not account for scenerios where touch is available but
  // the user is using a mouse or stylus, which can actually 'hover'. Maybe
  // discontinue this?
  this.addTest('globalHover', 'ambientimpact-global-hover', function() {
    return !this.getResult('touch');
  });
});
