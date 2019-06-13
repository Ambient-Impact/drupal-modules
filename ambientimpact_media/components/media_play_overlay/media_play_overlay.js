// -----------------------------------------------------------------------------
//   Ambient.Impact - Media - Media Play Overlay component
// -----------------------------------------------------------------------------

// This attaches a simple API on .media-play-overlay elements to play, stop,
// or toggle the playing state. When playing, the icon and prompt disappear.

AmbientImpact.addComponent('mediaPlayOverlay', function(aiMediaPlayOverlay, $) {
  'use strict';

  var baseClass     = 'media-play-overlay',
      playingClass  = baseClass + '--playing';

  this.addBehaviour(
    'AmbientImpactMediaPlayOverlay',
    'ambientimpact-media-play-overlay',
    '.' + baseClass,
    function(context, settings) {
      var $this = $(this);

      this.aiMediaPlayOverlay = {
        play: function() {
          $this.addClass(playingClass);
        },
        stop: function() {
          $this.removeClass(playingClass);
        },
        toggle: function() {
          $this.toggleClass(playingClass);
        },
        isPlaying: function() {
          return $this.hasClass(playingClass);
        }
      };
    },
    function(context, settings, trigger) {
      this.aiMediaPlayOverlay.stop();

      delete this.aiMediaPlayOverlay;
    }
  );
});
