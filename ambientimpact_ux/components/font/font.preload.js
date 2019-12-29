// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Font preloading component
// -----------------------------------------------------------------------------

// This attempts to read any existing font loading statuses from localStorage,
// and if any are found, creates a MutationObserver which waits for the <body>
// element to become available and adds each loaded font's class to the element
// as soon as it is. Unlike most components, this is attached in the <head> to
// be render blocking, and cannot rely on the JavaScript framework being
// available.

(function() {
  'use strict';

  // Bail if the browser doesn't cut the mustard.
  if (
    !('MutationObserver' in window) ||
    !('classList' in document.documentElement)
  ) {
    return;
  }

  var fontStatus;

  try {
    fontStatus = localStorage.getItem('Drupal.AmbientImpact.font.fonts');
  } catch (error) {
    console.error(error);

    return;
  }

  if (typeof fontStatus === null) {
    return;
  }

  // Try to parse the JSON string into an object.
  try {
    fontStatus = JSON.parse(fontStatus);
  } catch (error) {
    console.error(error);

    return;
  }

  /**
   * Set classes on the <body> element for any fonts previously loaded.
   *
   * @param {HTMLElement} body
   *   The <body> element.
   */
  function setClasses(body) {
    for (var machineName in fontStatus) {
      // Skip inherited properties and fonts that haven't been loaded.
      if (
        !fontStatus.hasOwnProperty(machineName) ||
        fontStatus[machineName].loaded === false
      ) {
        continue;
      }

      body.classList.add(fontStatus[machineName].className);
    }
  };

  /**
   * The MutationObserver.
   *
   * @type MutationObserver
   */
  var observer = new MutationObserver(function(mutations) {
    for (var i = mutations.length - 1; i >= 0; i--) {
      // Skip if no added nodes are listed in this item.
      if (!mutations[i].addedNodes) {
        continue;
      }

      var addedNodes = mutations[i].addedNodes;

      for (var j = addedNodes.length - 1; j >= 0; j--) {
        // Skip anything that isn't the <body> element.
        if (addedNodes[j].tagName !== 'BODY') {
          continue;
        }

        // When we find the <body> has been added, set classes for any
        // previously loaded fonts.
        setClasses(addedNodes[j]);

        // Stop observing.
        observer.disconnect();

        // Exit this function loops completely.
        return;
      }
    }
  });

  // Start observing the document.
  observer.observe(document.documentElement, {
    childList:  true,
    subtree:    true
  });
})();
