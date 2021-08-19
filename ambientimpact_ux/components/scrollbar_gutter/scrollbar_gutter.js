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
   * The current scrollbar thickness.
   *
   * This is used to store the measured scrollbar thickness to avoid writing to
   * the DOM if the value hasn't changed since the last measure.
   *
   * @type {Number}
   */
  var scrollbarThickness;

  /**
   * The maximum number of decimals to measure sub-pixel values to.
   *
   * @type {Number}
   */
  var decimals = 4;

  /**
   * The scrollbar measure container element, wrapped in a jQuery collection.
   *
   * This element has overflow: scroll; to force a scrollbar.
   *
   * @type {jQuery}
   */
  var $scrollbarMeasureContainer = $();

  /**
   * The scrollbar measure child element, wrapped in a jQuery collection.
   *
   * This is a block-level element appended to $scrollbarMeasureContainer and
   * whose outer width is compared to that of its parent to derive the scrollbar
   * gutter.
   *
   * @type {jQuery}
   */
  var $scrollbarMeasureChild = $();

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

    if ($scrollbarMeasureContainer.length === 0) {

      $scrollbarMeasureContainer = $('<div></div>')
        .attr('id', 'overlay-scroll-scrollbar-measure')
        // Probably not necessary but just to be sure this doesn't appear in the
        // accessibility tree.
        .attr('aria-hidden', true)
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
          overflow:   'scroll'
        })
        .appendTo('body');

      $scrollbarMeasureChild = $('<div></div>')
        .attr('id', 'overlay-scroll-scrollbar-measure-child')
        .appendTo($scrollbarMeasureContainer);

    }

    /** @type {Number} The full width of the container, including the scrollbar. */
    var containerWidth = parseFloat(
      $scrollbarMeasureContainer[0].getBoundingClientRect().width
        .toFixed(decimals)
    );

    /** @type {Number} The full width of the child element. */
    var childWidth = parseFloat(
      $scrollbarMeasureChild[0].getBoundingClientRect().width.toFixed(decimals)
    );

    return parseFloat((containerWidth - childWidth).toFixed(decimals));

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

    /**
     * The scrollbar thickness measured just now.
     *
     * @type {Number}
     */
    var measured = getScrollbarThickness();

    // Only update the property if the scrollbar thickness has actually changed
    // to avoid unnecessary DOM and style updates.
    //
    // @todo Should this have some tolerance for sub-pixel differences and only
    //   update when the value has changed past a certain threshold?
    if (measured !== scrollbarThickness) {
      setProperty($('html'), measured);
    }

    scrollbarThickness = measured;

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
