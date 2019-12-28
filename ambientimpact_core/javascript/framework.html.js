/* -----------------------------------------------------------------------------
  Ambient.Impact - Core - Framework component HTML
----------------------------------------------------------------------------- */

// .onGlobals() does nothing if Promises aren't supported in the framework
// feature check, i.e. AmbientImpact.mustard().
AmbientImpact.onGlobals([
  'jQuery',
  'drupalSettings.AmbientImpact.framework.html.endpointPath',
  'drupalSettings.AmbientImpact.framework.html.haveHTML',
], function() {
  'use strict';

  var $ = jQuery,

  // Settings passed from the back-end.
  settings = drupalSettings.AmbientImpact.framework,

  // The received HTML, keyed by component machine name.
  receivedHTML = {},

  // Prefix for the individual component HTML localStorage keys.
  componentCachePrefix = 'AmbientImpact.framework.cache.html.component.';

  /**
   * Retrieve a given component's HTML from storage.
   *
   * @param {String} componentName
   *   The component machine name.
   *
   * @return {String}
   *   The value found in storage, if any, null if not found, and an empty
   *   string if localStorage is not supported.
   */
  function getCachedHTML(componentName) {
    if (!('localStorage' in window)) {
      return '';
    }

    return localStorage.getItem(componentCachePrefix + componentName);
  };

  /**
   * Save the provided component's HTML to localStorage.
   *
   * @param {String} componentName
   *   The component machine name.
   *
   * @param {String} html
   *   The component's HTML.
   *
   * @return {Boolean}
   *   True if localStorage is supported, false otherwise.
   */
  function saveHTMLToCache(componentName, html) {
    if (!('localStorage' in window)) {
      return false;
    }

    localStorage.setItem(componentCachePrefix + componentName, html);

    return true;
  };

  /**
   * Determine if an update to the HTML is needed from the server.
   *
   * @return {Boolean}
   *   True under the following circumstances, false otherwise:
   *   * localStorage is not supported.
   *
   *   * The Drupal cache was just rebuilt.
   *
   *   * One or more components that are listed as having HTML don't have any
   *     cached.
   *
   * @see AmbientImpact.constructor.prototype.isCacheRebuilt()
   */
  function updateNeeded() {
    if (!('localStorage' in window)) {
      return true;
    }

    if (AmbientImpact.isCacheRebuilt()) {
      return true;
    }

    for (var i = settings.html.haveHTML.length - 1; i >= 0; i--) {
      var cachedHTML = getCachedHTML(settings.html.haveHTML[i]);

      if (
        typeof cachedHTML !== 'string' ||
        cachedHTML.length === 0
      ) {
        return true;
      }
    }

    return false;
  };

  /**
   * Insert the given component's HTML into the DOM.
   *
   * This is currently just a simple appending to the <body>.
   *
   * @param {String} componentName
   *   The component machine name.
   *
   * @param {String} html
   *   The component's HTML.
   */
  function insertHTML(componentName, html) {
    $(html).appendTo('body');
  };

  // Pass the delay Promise to the framework for the components that have HTML.
  AmbientImpact.delayComponents(settings.html.haveHTML,
  new Promise(function(resolve, reject) {
    if (!updateNeeded()) {
      // Wait for document ready in case the framework is attached in the <head>
      // rather than at the end of the document.
      $(function() {
        for (var i = settings.html.haveHTML.length - 1; i >= 0; i--) {
          insertHTML(
            settings.html.haveHTML[i],
            getCachedHTML(settings.html.haveHTML[i])
          );
        }

        resolve();
      });

      return;
    }

    // Make a request to the server to retrieve the HTML of all available and
    // attached components.
    $.get(settings.html.endpointPath).done(function(data) {
      receivedHTML = data;

      // Wait for document ready in case the framework is attached in the <head>
      // rather than at the end of the document and the response comes in before
      // the DOM is available.
      $(function() {
        // Append the returned HTML to the <body>. In the future, this may be
        // made configurable.
        for (var componentName in data) {
          if (data.hasOwnProperty(componentName)) {
            saveHTMLToCache(componentName, data[componentName]);

            insertHTML(componentName, data[componentName]);
          }
        }

        // When the response from the server has come in, resolve the component
        // delay Promise.
        resolve();
      });
    });
  }));

  /**
   * Get the HTML for a component, if any.
   *
   * Note that this doesn't need to be aware of the Ajax request or use any sort
   * of Promise as components only get added/registered when the Ajax request
   * succeeds.
   *
   * @return {string}
   *   HTML as a string if found for this component. If not found, an empty
   *   string is returned.
   */
  AmbientImpact.component.prototype.getHTML = function() {
    var machineName = this.getName();

    if (machineName in receivedHTML) {
      return receivedHTML[machineName];

    } else {
      return '';
    }
  };
});
