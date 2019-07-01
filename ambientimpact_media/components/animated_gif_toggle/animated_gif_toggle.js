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

  /**
   * The data-* attribute name that stores the static image source URL.
   *
   * @type {String}
   */
  var staticSourceDataName = 'ambientimpact-animated-gif-toggle-static-source';

  /**
   * The data-* attribute name that stores the animated image source URL.
   *
   * @type {String}
   */
  var animatedSourceDataName =
    'ambientimpact-animated-gif-toggle-animated-source';

  // The back-end sends the settings under 'animatedGifToggle' because the
  // lowerCamelCase conversion is pretty simple and doesn't understand
  // abbreviations or acronyms.
  this.settings = AmbientImpact.getComponentSettings('animatedGifToggle');

  /**
   * Event handler to toggle the animated/static GIF state.
   *
   * @param {jQuery.Event} event
   *   The jQuery.Event object for this event.
   */
  var eventHandler = function(event) {
    var $link = $(this);

    // Don't do anything if this triggers from another element (like a child).
    if (!$link.is('a')) {
      return;
    }

    $link[0].aiAnimatedGIFToggle.toggle();

    // Don't follow the link.
    event.preventDefault();
  }

  /**
   * Play a GIF.
   *
   * Note that 'this' refers to the aiAnimatedGIFToggle object attached to the
   * link by the behaviour.
   */
  var play = function() {
    // Set the overlay to playing.
    this.$overlay[0].aiMediaPlayOverlay.play();

    // Set the src to the animated source URL.
    this.$image.attr('src', this.$link.data(animatedSourceDataName));
  };

  /**
   * Stop playing a GIF.
   *
   * Note that 'this' refers to the aiAnimatedGIFToggle object attached to the
   * link by the behaviour.
   */
  var stop = function() {
    // Set the overlay to stopped.
    this.$overlay[0].aiMediaPlayOverlay.stop();

    // Set the src to the static source URL.
    this.$image.attr('src', this.$link.data(staticSourceDataName));
  };

  /**
   * Toggle playing a GIF.
   *
   * Note that 'this' refers to the aiAnimatedGIFToggle object attached to the
   * link by the behaviour.
   */
  var toggle = function() {
    if (this.$overlay[0].aiMediaPlayOverlay.isPlaying() === true) {
      this.stop();
    } else {
      this.play();
    }
  };

  this.addBehaviour(
    'AmbientImpactAnimatedGIFToggle',
    'ambientimpact-animated-gif-toggle',
    '[' + this.settings.fieldAttributes.enabled + ']',
    function(context, settings) {
      var $this   = $(this),
          $link   = $this.find('a').first(),
          $image  = $link.find('img');

      $link[0].aiAnimatedGIFToggle = {
        $link:     $link,
        $image:    $image,
        $overlay:  $link.find('.media-play-overlay'),
        play:      play,
        stop:      stop,
        toggle:    toggle
      };

      $link
        .data(
          staticSourceDataName,
          $image.attr('src')
        )
        .data(
          animatedSourceDataName,
          $this.attr(aiAnimatedGIFToggle.settings.fieldAttributes.url)
        )
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
          animatedSourceDataName,
          staticSourceDataName,
        ]);

      delete $link[0].aiAnimatedGIFToggle;
    }
  );
});
});
