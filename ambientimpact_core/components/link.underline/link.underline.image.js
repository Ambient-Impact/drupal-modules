/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Link underline image component
----------------------------------------------------------------------------- */

// This looks for links that contain an image, and wraps any text nodes found in
// those links so that we can apply the underline to only the text instead of
// the link as a whole, which would result in misaligned underlines and
// underlines under the image, etc.

AmbientImpact.on(['link.underline', 'link.image'], function(
  aiLinkUnderline, aiLinkImage
) {
AmbientImpact.addComponent('link.underline.image', function(
  aiUnderlineImage, $
) {
  'use strict';

  // Drupal behaviors.
  this.addBehaviors({
    AmbientImpactLinkUnderlineImage: {
      attach: function (context, settings) {
        // Wrap any links not excluded.
        $(
          'a:not(.' + aiLinkUnderline.excludeLinkClass + ')' +
          ':has(' + aiLinkImage.elements.join(',') + ')',
        context).wrapImageLinkText();
      },
      detach: function (context, settings, trigger) {
        if (trigger !== 'unload') {
          return;
        }

        // Unwrap.
        $('a.' + aiLinkImage.imageLinkClass)
          .unwrapImageLinkText();
      }
    }
  });

  // Link underline exclude added/removed events.
  // TO DO: this will **not** fire if an exclude was added before the
  // 'link.image' component has loaded. What should we do about this?
  $(document).on(aiLinkUnderline.excludeAddedEvent, function(
    event, selector
  ) {
    // An exclude was added, so unwrap any links.
    $(selector)
      .unwrapImageLinkText();
  }).on(aiLinkUnderline.excludeRemoveEvent, function(event, selector) {
    // Exclude was removed, wrap links.
    $(selector + ':has(' + aiLinkImage.elements.join(',') + ')')
      .wrapImageLinkText();
  });
});
});
