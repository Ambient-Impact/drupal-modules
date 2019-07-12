// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Displace component
// -----------------------------------------------------------------------------

AmbientImpact.on('environment', function(aiEnvironment) {
AmbientImpact.addComponent('displace', function(aiDisplace, $) {
  'use strict';

  // If the Drupal.displace.offsets object doesn't become available, this will
  // always return zero values.
  this.getOffsets = function() {
    return {top: 0, right: 0, bottom: 0, left: 0};
  };

  // When Drupal.displace.offsets becomes available, calling getOffsets() will
  // return the actual offsets.
  AmbientImpact.onGlobals(['Drupal.displace.offsets'], function() {
    aiDisplace.getOffsets = function() {
      return Drupal.displace.offsets;
    };
  });

  // Don't attach the event handler if the browser doesn't support CSS custom
  // properties.
  if (
    !AmbientImpact.objectPathExists('document.body.style.setProperty') ||
    !aiEnvironment.cssSupports('--test', 'orange')
  ) {
    return;
  }

  // Set the CSS custom properties on the <html> element when displacement
  // changes.
  $(document).on('drupalViewportOffsetChange.aiDisplace', function(
    event, offsets
  ) {
    for (var offsetName in offsets) {
      if (offsets.hasOwnProperty(offsetName)) {
        document.body.style.setProperty(
          '--displace-' + offsetName,
          offsets[offsetName] + 'px'
        );
      }
    }
  });
});
});
