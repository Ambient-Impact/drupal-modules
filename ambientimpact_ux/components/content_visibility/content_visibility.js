// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Content visibility
// -----------------------------------------------------------------------------

AmbientImpact.onGlobals([
  'IntersectionObserver', 'Number.isNaN', 'Number.parseFloat',
], function() {
AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent('contentVisibility', function(contentVisibility, $) {

  'use strict';

  /**
   * Name of the data object attached to the behaviour target element.
   *
   * @type {String}
   */
  const containerObjectName = 'aiContentVisibilityContainer';

  /**
   * Name of the data object attached to observed elements.
   *
   * @type {String}
   */
  const elementObjectName = 'aiContentVisibility';

  /**
   * Name of the event triggered on elements when observation has started.
   *
   * @type {String}
   */
  const observeStartEventName = 'contentVisibilityObserve'

  /**
   * Name of the event triggered on elements when their visibility changes.
   *
   * @type {String}
   */
  const visibilityEventName = 'contentVisibilityChange';

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

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
   */
  function intersectionCallback(entries, observer) {

    for (let j = 0; j < entries.length; j++) {

      let $target = $(entries[j].target);

      if (entries[j].isIntersecting === true) {

        if ($target.hasClass(contentVisibility.settings.observeOnceClass)) {

          observer.disconnect();

          fastdom.mutate(function() {
            $target.removeClass(contentVisibility.settings.observingClass);
          });

        }

        fastdom.mutate(function() {
          $target
          .addClass(contentVisibility.settings.visibleClass)
          .trigger(visibilityEventName, [true]);
        });

      } else {

        fastdom.mutate(function() {
          $target
          .removeClass(contentVisibility.settings.visibleClass)
          .trigger(visibilityEventName, [false])
        });

      }

    }

  };

  /**
   * Build threshold values for the provided elements.
   *
   * This attempts to read a threshold value from the elements' data-*
   * attribute, falling back to the default value if it doesn't exist.
   *
   * @param {jQuery} $elements
   *   jQuery collection of elements to build threshold values for.
   *
   * @throws {Error}
   *   If the data-* attribute does exist but it can't be parsed as a float.
   */
  function buildThresholds($elements) {

    for (let i = 0; i < $elements.length; i++) {

      /**
       * The threshold value for this element, if specified.
       *
       * Note that we're using jQuery().attr() rather than
       * jQuery().data(), as the latter returns an object in line with the
       * HTML dataset API due to the fact that we pass a hyphenated and
       * not camel-cased data attribute name.
       *
       * @type {String|undefined}
       */
      let threshold = $elements.eq(i).attr(
        'data-' + contentVisibility.settings.thresholdDataName
      );

      if (typeof threshold === 'string') {

        /**
         * The parsed threshold value via Number.parseFloat().
         *
         * @type {Number|NaN}
         */
        let parsedThreshold = Number.parseFloat(threshold);

        if (Number.isNaN(parsedThreshold)) {
          throw new Error('Threshold is not a number: "' + threshold + '"');
        } else {
          threshold = parsedThreshold;
        }

      }

      // Fall back to the default value if a value is not specified.
      if (typeof threshold === 'undefined') {
        threshold = contentVisibility.settings.defaultThreshold;
      }

      $elements[i][elementObjectName].threshold = threshold;

    }

  };

  this.addBehaviour(
    'AmbientImpactContentVisibility',
    'ambientimpact-content-visibility',
    '.layout-container',
    function(context, settings) {

      /**
       * Observerable elements found in the container we're attaching to.
       *
       * @type {jQuery}
       */
      let $observableElements = $([
        '.' + contentVisibility.settings.baseClass,
        '.' + contentVisibility.settings.observeOnceClass
      ].join(','), this);

      // Save the jQuery collection to the container for detaching.
      this[containerObjectName] = {
        $observableElements: $observableElements
      };

      for (let i = 0; i < $observableElements.length; i++) {
        $observableElements[i][elementObjectName] = {};
      }

      fastdom.measure(function() {

        buildThresholds($observableElements);

      }).then(function() {

        for (let i = 0; i < $observableElements.length; i++) {

          let data = $observableElements[i][elementObjectName];

          /**
           * The Intersection Observer for this element.
           *
           * @type {IntersectionObserver}
           */
          let observer = new IntersectionObserver(intersectionCallback, {
            threshold: data.threshold
          });

          observer.observe($observableElements[i]);

          // Trigger the observe start event here so that it occurs before the
          // initial Intersection Observer callback events. Note that the
          // observing class will not have been added to the element yet as
          // that's queued to be done via FastDom.
          $observableElements.eq(i).trigger(observeStartEventName, [observer]);

          data.observer = observer;

        }

        return fastdom.mutate(function() {
          $observableElements.addClass(
            contentVisibility.settings.observingClass
          );
        });

      });

    },
    function(context, settings, trigger) {

      if (!(containerObjectName in this)) {
        return;
      }

      /**
       * The container element we're attached to.
       *
       * @type {HTMLElement}
       */
      let container = this;

      /**
       * Observerable elements in the container we're detaching from.
       *
       * @type {jQuery}
       */
      let $observableElements = this[containerObjectName].$observableElements;

      // Disconnect any Intersect Observers still observing.
      for (let i = 0; i < $observableElements.length; i++) {
        $observableElements[i].aiContentVisibility.observer.disconnect();
      }

      // Remove the classes the Intersection Observers add.
      fastdom.mutate(function() {

        $observableElements.removeClass([
          contentVisibility.settings.observingClass,
          contentVisibility.settings.visibleClass,
        ]);

      // Then delete our properties from the elements and container.
      }).then(function() {

        for (let i = 0; i < $observableElements.length; i++) {
          delete $observableElements[i].aiContentVisibility;
        }

        delete container[containerObjectName];

      });

    }

  );

});
});
});
