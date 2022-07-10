// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Property to pixel converter
// -----------------------------------------------------------------------------

AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent(
  'propertyToPixelConverter',
function(propertyToPixelConverter, $) {

  'use strict';

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * Property converter object.
   *
   * @param {jQuery} $container
   *   The container element to read property values from, wrapped in a jQuery
   *   collection.
   *
   * @param {Array} propertyNames
   *   String CSS property names to read the values of.
   *
   * @constructor
   */
  function converter($container, propertyNames) {

    /**
     * Cache of values converted to pixels, keyed by CSS property names.
     *
     * @type {Object}
     */
    let values = {};

    /**
     * The container's computed style object.
     *
     * @type {CSSStyleDeclaration}
     */
    let computedStyle;

    /**
     * Get the computed style object for the container.
     *
     * @return {Promise}
     *   A Promise that resolves with the CSSStyleDeclaration object of the
     *   container element.
     */
    function getContainerComputedStyle() {

      // If the computed style object hasn't been fetched yet, get it.
      if (typeof computedStyle === 'undefined') {

        return fastdom.measure(function() {

          computedStyle = getComputedStyle($container[0]);

          return computedStyle;

        });

      }

      // If it's already fetched, just return an already resolved Promise with
      // it.
      return Promise.resolve(computedStyle);

    };

    /**
     * Build a measure element.
     *
     * @return {jQuery}
     *   A jQuery collection containing a measure element. This element is not
     *   yet attached to the DOM and has no height set as that is expected to
     *   be set after calling this to a property value to be measured.
     */
    function buildMeasureElement() {

      return $('<div></div>').attr('aria-hidden', true).css({
        position: 'absolute',
        // Placed just out of view. Note that a negative top value shouldn't
        // cause any scrolling upwards on any platforms/browsers.
        top:      '-110vh',
        width:    '1px'
      });

    }

    /**
     * Get a single property value in pixels.
     *
     * Note that this always reads the value from the DOM/CSSOM and is not
     * cached.
     *
     * @param {String} propertyName
     *   The CSS property name to measure in pixels.
     *
     * @return {Promise}
     *   A Promise object that is resolved with an object containing the
     *   property name as its only key, with the value being the measured pixel
     *   value.
     */
    function getValue(propertyName) {

      return getContainerComputedStyle()
      .then(function(computedStyle) { return fastdom.measure(function() {

        return computedStyle.getPropertyValue(propertyName);

      }) }).then(function(rawValue) { return fastdom.mutate(function() {

        return buildMeasureElement()
        .css('height', rawValue)
        .appendTo($container);

      }) }).then(function(measureElement) { return fastdom.measure(function() {

        return {
          element: measureElement,
          value:   measureElement.height()
        };

      }); }).then(function(data) { return fastdom.mutate(function() {

        data.element.remove();

        let returnObject = {};

        returnObject[propertyName] = data.value;

        return returnObject;

      }); });

    };

    /**
     * Get all the property values in pixels defined for this instance.
     *
     * @param {Boolean} forceUpdate
     *   Whether to force an update. By default, this method will return cached
     *   values from when first initialized or the last forced update. Passing
     *   true as this parameter forces an update, but should be used only when
     *   needed for performance reasons.
     *
     * @return {Promise}
     *   A Promise that resolves with an object containing keys for the
     *   properties defined for this instance, with values being the measured
     *   pixel values.
     */
    this.getValues = function(forceUpdate) {

      /**
       * An array of Promise objects.
       *
       * @type {Array}
       */
      let promises = [];

      for (let i = 0; i < propertyNames.length; i++) {

        let propertyName = propertyNames[i];

        // If forcing an update or the value isn't yet cached, get the value
        // from computed styles.
        if (forceUpdate || !(propertyName in values)) {

          promises.push(getValue(propertyName));

          continue;

        }

        /**
         * Object to resolve this property's Promise with.
         *
         * Contains a single key with the CSS property name which contains the
         * pixel value the property converts to.
         *
         * @type {Object}
         */
        let returnObject = {};

        returnObject[propertyName] = values[propertyName];

        promises.push(Promise.resolve(returnObject));

      }

      return Promise.all(promises).then(function(valuesArray) {

        // Merge all the objects the Promises resolved to into a single object
        // and save it to the values cache.
        values = $.extend.apply(null, valuesArray);

        return values;

      });

    };

  };

  /**
   * Create a property converter instance.
   *
   * @param {jQuery|HTMLElement} $container
   *   A container element or a jQuery collection a containing a container. If
   *   more than one container is provided, only the first one will be used.
   *
   * @param {Array} propertyNames
   *   String CSS property names to read the values of.
   *
   * @return {converter}
   */
  this.create = function($container, propertyNames) {
    return new converter($($container).first(), propertyNames);
  };

});
});
