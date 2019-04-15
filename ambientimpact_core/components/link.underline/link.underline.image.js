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

  // Behaviour to wrap and unwrap image link text.
  this.addBehaviour(
    'AmbientImpactLinkUnderlineImage',
    'ambientimpact-link-underline-image',
    'a:not(.' + aiLinkUnderline.excludeLinkClass + ')' +
      ':has(' + aiLinkImage.elements.join(',') + ')',
    function(context, settings) {
      $(this).wrapImageLinkText();
    },
    function(context, settings, trigger) {
      $(this).unwrapImageLinkText();
    }
  );

  // Link underline exclude added/removed events. This catches links that have
  // their exclusion state changed after the behaviour has been applied.
  $(document).on(aiLinkUnderline.excludeAddedEvent, function(
    event, selector
  ) {
    // An exclude was added, so unwrap any links.
    $(selector).unwrapImageLinkText();
  }).on(aiLinkUnderline.excludeRemoveEvent, function(event, selector) {
    // Exclude was removed, so wrap links.
    $(selector + ':has(' + aiLinkImage.elements.join(',') + ')')
      .wrapImageLinkText();
  });
});
});
