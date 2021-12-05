// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Scroll proxy component
// -----------------------------------------------------------------------------

// @todo Split into sub-components.

// @todo Add a configurable direction so that this can be applied to the
//   vertical axis instead if needed.

// @todo Potential optimization: the items's properties are still updated when
//   it has just gone out of the viewport upwards but the sentinel is still
//   visible and thus the Intersection Observer is still invoking the callback.
//   Can an additional Intersection Observer be added to determine when items
//   are actually still visible and only update the properties if so?

// @see https://alligator.io/js/smooth-scrolling/
//   It may be more performant to use the browser's scrolling programmatically
//   rather than transforms. It would also remove the need to account for the
//   item width, as that would be handled by the browser.

AmbientImpact.onGlobals([
  'Drupal.debounce',
  'IntersectionObserver',
], function() {
AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent('scrollProxy', function(aiScrollProxy, $) {

  'use strict';

  /**
   * Name of the data object for the detach callback.
   *
   * @type {String}
   */
  const dataObjectName = 'aiScrollProxy';

  /**
   * Our event namespace.
   *
   * @type {String}
   */
  const eventNamespace = 'aiScrollProxy';

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * Name of the data attribute identifying a scroll proxy item.
   *
   * @type {String}
   */
  const itemAttributeName = 'data-scroll-proxy-item';

  /**
   * The scroll proxy item width property name.
   *
   * @type {String}
   */
  const itemWidthPropertyName = '--scroll-proxy-item-width';

  /**
   * The minimum threshold for the Intersection Observer to act on.
   *
   * @type {Number}
   */
  const minThreshold = 0.0;

  /**
   * The maximum threshold for the Intersection Observer to act on.
   *
   * @type {Number}
   */
  const maxThreshold = 1.0;

  /**
   * The threshold granularity or steps to generate thresholds with.
   *
   * @type {Number}
   */
  const thresholdGranularity = 0.01;

  /**
   * The array of thresholds to pass to the Intersection Observer.
   *
   * @type {Number[]}
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/IntersectionObserver#parameters
   */
  let thresholds = [];

  /**
   * The minimum time between DOM updates in milliseconds for the observer.
   *
   * This is passed to Drupal.debounce() as the 'wait' parameter.
   *
   * @type {Number}
   */
  const observerDebounceTimeout = 10;

  /**
   * Sentinel element class.
   *
   * @type {String}
   */
  const sentinelClass = 'scroll-proxy-sentinel';

  /**
   * The sentinel element intersect property name.
   *
   * @type {String}
   */
  const sentinelIntersectPropertyName =
    '--scroll-proxy-sentinel-intersect-amount';

  /**
   * Name of the data attribute on an item indicating the sentienal container.
   *
   * The value of this is expected to be a selector to be passed to
   * jQuery().closest().
   *
   * @type {String}
   */
  const sentinelContainerAttributeName = 'data-scroll-proxy-sentinel-container';

  /**
   * The scroll proxy on/off property name.
   *
   * This is set to 'true' in the CSS if the element is to use the scroll proxy
   * and 'false' otherwise. This allows the functionality to be enabled per-
   * element and also change on an element based on screen sizes, dynamically
   * turning it on and off as needed.
   *
   * @type {String}
   */
  const useScrollProxyPropertyName = '--use-scroll-proxy';

  /**
   * Get thresholds for the Intersection Observer, building if needed.
   *
   * @return {Number[]}
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/IntersectionObserver#parameters
   */
  function getThresholds() {

    // If they've already been built, return the existing thresholds.
    if (thresholds.length > 0) {
      return thresholds;
    }

    for (
      let i = minThreshold;
      i <= maxThreshold + thresholdGranularity;
      i += thresholdGranularity
    ) {
      thresholds.push(Math.round(i * 100) / 100);
    }

    return thresholds;

  };

  /**
   * Construct a new scroll proxy item.
   *
   * @param {HTMLElement} item
   *   An element to attach to.
   *
   * @constructor
   */
  this.scrollProxyItem = function(item) {

    /**
     * Reference to the current instance for use in callbacks.
     *
     * @type {Object}
     */
    let instance = this;

    /**
     * The scroll proxy item we're attaching to.
     *
     * @type {jQuery}
     */
    let $item = $(item);

    /**
     * The sentinel container.
     *
     * @type {jQuery}
     */
    let $sentinelContainer = $();

    /**
     * The sentinel container selector.
     *
     * @type {String|undefined}
     */
    let sentinelContainerSelector = $item.attr(
      sentinelContainerAttributeName
    );

    if (typeof sentinelContainerSelector === 'string') {

      /**
       * Temporary sentinel container collection.
       *
       * @type {jQuery}
       */
      let $container = $item.closest(sentinelContainerSelector);

      // If a container was found, use it.
      if ($container.length > 0) {

        $sentinelContainer = $container;

      // Otherwise output a warning that the selector didn't match anything.
      } else {

        console.warn(
          'Couldn\'t find the specified sentinel container:',
          sentinelContainerSelector
        );

      }

    }

    // If a sentinel container wasn't set, use the item's parent element.
    if ($sentinelContainer.length === 0) {
      $sentinelContainer = $item.parent();
    }

    /**
     * The sentinel element for this item.
     *
     * @type {jQuery}
     */
    let $sentinel = $('<span></span>').addClass(sentinelClass);

    /**
     * Intersection Observer callback.
     *
     * @param {IntersectionObserverEntry[]} entries
     *   An array of IntersectionObserverEntry objects.
     *
     * @param {IntersectionObserver} observer
     *   The IntersectionObserver for which the callback is being invoked.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/IntersectionObserver#parameters
     *
     * @todo Can we remove the loop as there's only ever one entry?
     */
    function intersectionCallback(entries, observer) {

      for (let i = 0; i < entries.length; i++) {

        /**
         * The value of the intersection property to set on the item.
         *
         * @type {Number}
         */
        let propertyValue = 0;

        // If the intersection ratio is above the minimum threshold, calculate
        // the fractional value.
        //
        // @todo Can we remove the adjustments for the minimum threshold? It's
        //   not used and doesn't scale correctly.
        if (entries[i].intersectionRatio >= minThreshold) {
          propertyValue = Math.ceil(
            (entries[i].intersectionRatio * 100) - (minThreshold * 100)
          ) / 100;
        }

        fastdom.mutate(function() {
          $item[0].style.setProperty(
            sentinelIntersectPropertyName,
            propertyValue
          );
        });

      }

    };

    /**
     * The Intersection Observer for this element.
     *
     * @type {IntersectionObserver}
     */
    let observer = new IntersectionObserver(
      Drupal.debounce(intersectionCallback, observerDebounceTimeout),
      {threshold: getThresholds()}
    );

    /**
     * Whether the Intersection Observer is currently observing.
     *
     * @type {Boolean}
     */
    let isObserving = false;

    /**
     * The last measured viewport width in pixels.
     *
     * @type {Number}
     */
    let lastViewportWidth = 0;

    fastdom.measure(function() {
      lastViewportWidth = $(window).width();

    }).then(function() {
      return instance.updateWidthProperty();

    }).then(function() {

      return fastdom.mutate(function() {
        $sentinel.prependTo($sentinelContainer);
      });

    }).then(function() {

      observer.observe($sentinel[0]);

      isObserving = true;

      $(window).on([
        'lazyResize.' + eventNamespace,
        'drupalViewportOffsetChange.' + eventNamespace
      ].join(' '), instance.update);

    });

    /**
     * Determine if the item is currently set to use scroll proxy.
     *
     * @return {Promise}
     *   Promise returned by FastDom that resolves with either true or false.
     */
    this.useScrollProxy = function() {

      // Attempt to read and parse the CSS custom property.
      return fastdom.measure(function() {

        /** @type {String} Either a string value or an empty string if not defined. */
        let propertyValue = getComputedStyle($item[0]).getPropertyValue(
          useScrollProxyPropertyName
        ).trim();

        return propertyValue === 'true';

      });

    };

    /**
     * Update the item width custom property.
     *
     * @return {Promise}
     *   The Promise returned by FastDom resolved once the update is complete.
     */
    this.updateWidthProperty = function() {

      return fastdom.measure(function() {

        return $item.width();

      }).then(function(width) {

        return fastdom.mutate(function() {
          $item[0].style.setProperty(itemWidthPropertyName, width + 'px');
        });

      });

    }

    /**
     * Update various properties and start/stop the observer if needed.
     *
     * @return {Promise}
     *   The Promise returned by FastDom resolved when all updates are complete.
     */
    this.update = function() {

      return fastdom.measure(function() {

        let viewportWidth = $(window).width();

        if (lastViewportWidth === viewportWidth) {
          return false;
        }

        // Update the stored viewport width.
        lastViewportWidth = viewportWidth;

        return instance.useScrollProxy().then(function(shouldObserve) {

          if (shouldObserve === false && isObserving === true) {

            observer.unobserve($sentinel[0]);

            isObserving = false;

          }

          return shouldObserve;

        });

      }).then(function(shouldUpdate) {

        if (shouldUpdate === false) {
          return;
        }

        return instance.updateWidthProperty().then(function() {

          if (isObserving === true) {
            return;
          }

          observer.observe($sentinel[0]);

          isObserving = true;

        });

      });

    };

    this.detach = function() {

      $(window).off([
        'lazyResize.' + eventNamespace,
        'drupalViewportOffsetChange.' + eventNamespace
      ].join(' '), instance.update);

      observer.disconnect();

      return fastdom.mutate(function() {
        $sentinel.remove();

        $item[0].style.removeProperty(itemWidthPropertyName);
      });

    };

  };

  this.addBehaviour(
    'AmbientImpactScrollProxy',
    'ambientimpact-scroll-proxy',
    '[' + itemAttributeName + ']',
    function(context, settings) {

      this[dataObjectName] = new aiScrollProxy.scrollProxyItem(this);

    },
    function(context, settings, trigger) {

      this[dataObjectName].detach();

      delete this[dataObjectName];

    }

  );

});
});
});
