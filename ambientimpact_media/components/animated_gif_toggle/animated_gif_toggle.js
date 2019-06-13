// -----------------------------------------------------------------------------
//   Ambient.Impact - Media - Animated GIF toggle component
// -----------------------------------------------------------------------------

// Allows fields containing animated GIFs to toggle between a static image and
// the animated GIF on user interaction.

// @todo Use IntersectionObserver to automatically stop GIF animation when the
// field goes off screen?

AmbientImpact.on([
  'link.external',
  'mediaPlayOverlay',
], function(aiLinkExternal, aiMediaPlayOverlay) {
AmbientImpact.addComponent('animatedGIFToggle', function(
  aiAnimatedGIFToggle, $
) {
  'use strict';

  var staticSourceDataName = 'ambientimpact-animated-gif-toggle-static-source',
      animatedSourceDataName =
        'ambientimpact-animated-gif-toggle-animated-source',
      componentSettings =
        AmbientImpact.getComponentSettings('animatedGifToggle');

  var eventHandler = function(event) {
    var $link = $(this);

    // Don't do anything if this triggers from another element (like a child).
    if (!$link.is('a')) {
      return;
    }

    // Get elements.
    var $image            = $link[0].aiAnimatedGIFToggle.$image,
        $mediaPlayOverlay = $link[0].aiAnimatedGIFToggle.$mediaPlayOverlay;

    // Toggle the overlay.
    $mediaPlayOverlay[0].aiMediaPlayOverlay.toggle();

    // Swap based on the overlay state.
    $image.attr('src', $link.data(
      $mediaPlayOverlay[0].aiMediaPlayOverlay.isPlaying() ?
      animatedSourceDataName :
      staticSourceDataName
    ));

    // Don't follow the link.
    event.preventDefault();
  }

  this.addBehaviour(
    'AmbientImpactAnimatedGIFToggle',
    'ambientimpact-animated-gif-toggle',
    '[' + componentSettings.fieldAttributes.enabled + ']',
    function(context, settings) {
      var $this   = $(this),
          $link   = $this.find('a').first(),
          $image  = $link.find('img');

      $link[0].aiAnimatedGIFToggle = {
        $image:             $image,
        $mediaPlayOverlay:  $link.find('.media-play-overlay')
      };

      $link
        .data(
          staticSourceDataName,
          $image.attr('src')
        )
        .data(
          animatedSourceDataName,
          $this.attr(componentSettings.fieldAttributes.url)
        )
        // Force pointer focus hiding.
        // @todo What if this already has an existing value?
        .data('pointer-focus-hide', true)
        .on('click.aiAnimatedGIFToggle', eventHandler);
    },
    function(context, settings, trigger) {
      var $this   = $(this),
          $link   = $this.find('a').first(),
          $image  = $link.find('img');

      $image.attr('src', $link.data(staticSourceDataName));

      $link
        .off('click.aiAnimatedGIFToggle', eventHandler)
        .removeData([
          'pointer-focus-hide',
          animatedSourceDataName,
          staticSourceDataName,
        ]);

      delete $link[0].aiAnimatedGIFToggle;
    }
  );
});
});
