// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Lazy resize event component
// -----------------------------------------------------------------------------

AmbientImpact.onGlobals('Drupal.debounce', function() {
AmbientImpact.addComponent('event.lazyResize', function(aiLazyResize, $) {
  /**
   * The interval at which the 'lazyResize' event is triggered, in milliseconds.
   *
   * @type {Number}
   */
  this.interval = 250;

  $(window).on('resize', Drupal.debounce(function(event) {
    $(window).trigger('lazyResize');
  }, this.interval));
});
});
