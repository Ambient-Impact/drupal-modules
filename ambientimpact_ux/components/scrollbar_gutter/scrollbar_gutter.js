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

AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent('scrollbarGutter', function(aiScrollbarGutter, $) {

  'use strict';

  /**
   * The maximum number of decimals to measure sub-pixel values to.
   *
   * @type {Number}
   */
  const decimals = 4;

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * Attribute name containing the scrollbar gutter value in pixels.
   *
   * @type {String}
   */
  const attributeName = 'data-scrollbar-gutter';

  /**
   * Selector for the element that we set and update the custom property on.
   *
   * @type {String}
   */
  const propertyTargetSelector = 'html';

  /**
   * CSS custom property name that we set and update.
   *
   * @type {String}
   */
  const propertyName = '--scrollbar-gutter';

  /**
   * The current scrollbar thickness.
   *
   * This is used to store the measured scrollbar thickness to avoid writing to
   * the DOM if the value hasn't changed since the last measure.
   *
   * @type {Number}
   */
  let scrollbarThickness;

  /**
   * The scrollbar measure container element, wrapped in a jQuery collection.
   *
   * This element has overflow: scroll; to force a scrollbar.
   *
   * @type {jQuery}
   */
  let $scrollbarMeasureContainer = $();

  /**
   * The scrollbar measure child element, wrapped in a jQuery collection.
   *
   * This is a block-level element appended to $scrollbarMeasureContainer and
   * whose outer width is compared to that of its parent to derive the scrollbar
   * gutter.
   *
   * @type {jQuery}
   */
  let $scrollbarMeasureChild = $();

  /**
   * Build the measure elements if they don't exist yet.
   *
   * @return {Promise}
   *   A Promise that resolves when the measure elements are created and
   *   attached or an immediately resolved Promise if they already exist.
   */
  function buildMeasure() {

    if ($scrollbarMeasureContainer.length > 0) {
      return Promise.resolve();
    }

    return fastdom.mutate(function() {

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

    });

  };

  /**
   * Destroy and detach the measure elements.
   *
   * @return {Promise}
   *   Promise that resolves when the measure elements have been detached.
   */
  function destroyMeasure() {

    return fastdom.mutate(function() {
      $scrollbarMeasureContainer.remove();
    });

  };

  /**
   * Get the detected scrollbar thickness, in pixels.
   *
   * @return {Promise}
   *   A Promise that resolves with the scrollbar thickness, in pixels.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/getBoundingClientRect
   *   We use this rather than HTMLElement.offsetWidth as the latter rounds to
   *   the nearest integer, while the value of
   *   Element.getBoundingClientRect().width is a float and thus allows for more
   *   precision.
   */
  function getScrollbarThickness() {

    return buildMeasure().then(function() {

      return fastdom.measure(function() {

        /** @type {Number} The full width of the container, including the scrollbar. */
        let containerWidth = parseFloat(
          $scrollbarMeasureContainer[0].getBoundingClientRect().width
            .toFixed(decimals)
        );

        /** @type {Number} The full width of the child element. */
        let childWidth = parseFloat(
          $scrollbarMeasureChild[0].getBoundingClientRect().width.toFixed(
            decimals
          )
        );

        return parseFloat((containerWidth - childWidth).toFixed(decimals));

      });

    });

  };

  /**
   * Get the detected scrollbar thickness, in pixels.
   *
   * @return {Promise}
   *   A Promise that resolves with the scrollbar thickness, in pixels.
   */
  this.getScrollbarThickness = function() {
    return getScrollbarThickness();
  };

  /**
   * Lazy resize handler to set the custom property.
   */
  function resizeHandler() {

    getScrollbarThickness().then(function(measured) {

      // Only update the property if the scrollbar thickness has actually
      // changed to avoid unnecessary DOM and style updates.
      //
      // @todo Should this have some tolerance for sub-pixel differences and
      //   only update when the value has changed past a certain threshold?
      if (measured !== scrollbarThickness) {

        scrollbarThickness = measured;

        setProperty($(propertyTargetSelector), measured);

      }

    });

  };

  /**
   * Set the custom property on a provided element with the given thickness.
   *
   * @param {jQuery|HTMLElement} element
   *   The element or jQuery collection to apply the custom property to.
   *
   * @param {Number} thickness
   *   The scrollbar thickness to set.
   *
   * @return {Promise}
   *   Promise that resolves when style mutation is complete.
   */
  function setProperty(element, thickness) {

    return fastdom.mutate(function() {

      $(element)
      .attr(attributeName, thickness)
      .prop('style').setProperty(propertyName, thickness + 'px');

    });

  };

  this.addBehaviour(
    'AmbientImpactScrollbarGutter',
    'ambientimpact-scrollbar-gutter',
    propertyTargetSelector,
    function(context, settings) {

      /**
       * The behaviour target element.
       *
       * @type {HTMLElement}
       */
      let behaviourTarget = this;

      getScrollbarThickness().then(function(measured) {

        return setProperty(behaviourTarget, measured);

      }).then(function() {

        $(window).on('lazyResize.aiScrollbarGutter', resizeHandler);

      });

    },
    function(context, settings, trigger) {

      /**
       * The behaviour target element.
       *
       * @type {HTMLElement}
       */
      let behaviourTarget = this;

      $(window).off('lazyResize.aiScrollbarGutter', resizeHandler);

      destroyMeasure().then(function() {
        fastdom.mutate(function() {

          $(behaviourTarget)
          .removeAttr(attributeName)
          .prop('style').removeProperty(propertyName);

        });
      });

    }
  );

});
});
