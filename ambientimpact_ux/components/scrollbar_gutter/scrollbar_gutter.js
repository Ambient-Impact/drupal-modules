// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Scrollbar gutter component
// -----------------------------------------------------------------------------

// This component creates an element to measure the thickness of the scrollbar
// gutter, and sets it as a '--scrollbar-gutter' property on the <html> element.
// This property is updated lazily on viewport resize, so that the value is kept
// up to date in case it changes.
//
// @see https://davidwalsh.name/detect-scrollbar-width
//   Based on this post by David Walsh.
//
// @see https://jsfiddle.net/a1m6an3u/
//   JSFiddle demonstrating David Walsh's solution.
//
// @see https://stackoverflow.com/questions/28360978/css-how-to-get-browser-scrollbar-width-for-hover-overflow-auto-nice-margi/28361560#28361560
//
// @see https://developer.mozilla.org/en-US/docs/Web/CSS/scrollbar-gutter
//   CSS property that is currently not supported in any browsers that may
//   eventually make this component unnecessary.

AmbientImpact.addComponent('scrollbarGutter', function(aiScrollbarGutter, $) {

  'use strict';

  /**
   * The scrollbar measure element, wrapped in a jQuery collection.
   *
   * @type {jQuery}
   */
  var $scrollbarMeasure = $();

  /**
   * Get the detected scrollbar thickness, in pixels.
   *
   * @return {Number}
   *   The scrollbar thickness, in pixels.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/getBoundingClientRect
   *   We use this rather than HTMLElement.offsetWidth as the latter rounds to
   *   the nearest integer, while the value of
   *   Element.getBoundingClientRect().width is a float and thus allows for more
   *   precision.
   */
  function getScrollbarThickness() {

    if ($scrollbarMeasure.length === 0) {
      $scrollbarMeasure = $('<div></div>')
        .attr('id', 'overlay-scroll-scrollbar-measure')
        .css({
          position:   'absolute',
          // Placed just out of view. Note that a negative top value shouldn't
          // cause any scrolling upwards on any platforms/browsers.
          top:        '-110vh',
          // We're using viewport units for the width to hopefully get an
          // accurate sub-pixel width of the scrollbar and minimize any reflow
          // when the scrollbar width is used for layout.
          width:      '100vw',
          height:     '100px',
          overflow:   'scroll',
          // Probably not necessary but just in case this is shown in the
          // accessibility tree.
          ariaHidden: true,
        })
        .appendTo('body');
    }

    /** @type {Number} */
    var offsetWidth = $scrollbarMeasure.prop('offsetWidth');

    /** @type {Number} */
    var clientWidth = $scrollbarMeasure.prop('clientWidth');

    // If the offsetWidth and clientWidth are identical, return zero. We can't
    // compare HTMLElement.offsetWidth with
    // Element.getBoundingClientRect().width as they would likely not match in a
    // lot of cases due to the former returning only rounded integers and the
    // latter floats.
    if (offsetWidth === clientWidth) {
      return 0;
    }

    return ($scrollbarMeasure[0].getBoundingClientRect().width - clientWidth);

  };

  /**
   * Get the detected scrollbar thickness, in pixels.
   *
   * @return {Number}
   *   The scrollbar thickness, in pixels.
   */
  this.getScrollbarThickness = function() {
    return getScrollbarThickness();
  };

  /**
   * Lazy resize handler to set the custom property.
   */
  function resizeHandler() {
    setProperty($('html'), getScrollbarThickness());
  };

  /**
   * Set the custom property on a provided element with the given thickness.
   *
   * @param {jQuery|HTMLElement} element
   *   The element or jQuery collection to apply the custom property to.
   *
   * @param {Number} thickness
   *   The scrollbar thickness to set.
   */
  function setProperty(element, thickness) {
    $(element).prop('style').setProperty(
      '--scrollbar-gutter',
      thickness + 'px'
    );
  };

  this.addBehaviour(
    'AmbientImpactScrollbarGutter',
    'ambientimpact-scrollbar-gutter',
    'html',
    function(context, settings) {

      setProperty(this, getScrollbarThickness());

      $(window).on('lazyResize.aiScrollbarGutter', resizeHandler);

    },
    function(context, settings, trigger) {

      $(window).off('lazyResize.aiScrollbarGutter', resizeHandler);

      this.style.removeProperty('--scrollbar-gutter');

    }
  );

});
