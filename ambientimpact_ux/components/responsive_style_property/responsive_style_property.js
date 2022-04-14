// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Responsive style property component
// -----------------------------------------------------------------------------

AmbientImpact.on([
  'fastdom', 'layoutSizeChange',
], function(aiFastDom, aiLayoutSizeChange) {
AmbientImpact.addComponent('responsiveStyleProperty', function(
  aiResponsiveStyleProperty, $
) {

  'use strict';

  /**
   * Event namespace name.
   *
   * @type {String}
   */
  const eventNamespace = this.getName();

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * The name of the event triggered when the property changes.
   *
   * @type {String}
   */
  const changeEventName = 'responsivePropertyChange';

  /**
   * Default options.
   *
   * - 'direction': The direction to watch and trigger an event if it changes.
   *   Can be one of 'width' (the default), 'height',
   *
   * @type {Object}
   */
  let defaultOptions = {
    direction: 'width'
  };

  /**
   * Responsive style property object.
   *
   * @param {String} propertyName
   *   The style property to watch. This can be a CSS custom property or any CSS
   *   property.
   *
   * @param {jQuery|HTMLElement} element
   *   An HTML element or a jQuery collection containing one element. If this is
   *   a jQuery collection that contains more than one element, only the first
   *   will be watched.
   *
   * @param {Object} options
   *   Options for this instance.
   *
   * @constructor
   */
  function responsiveStyleProperty(propertyName, element, options) {

    /**
     * Reference to the current instance for use in closures.
     *
     * @type {responsiveStyleProperty}
     */
    let instance = this;

    /**
     * Settings; default options with the 'options' parameter merged on top.
     *
     * @type {Object}
     */
    let settings = $.extend(true, {}, defaultOptions, options);

    /**
     * The current raw property value.
     *
     * @type {String}
     */
    let value = '';

    /**
     * The element jQuery collection.
     *
     * @type {jQuery}
     */
    let $element = $(element).first();

    if ($element.length === 0) {

      console.error('An element must be provided.');

      return;

    }

    element = $element[0];

    /**
     * Get the watched property name.
     *
     * @return {String}
     */
    this.getPropertyName = function() {
      return propertyName;
    };

    /**
     * Get the trimmed current value of the property.
     *
     * Note that white-space between the colon (:) and the semi-colon (;) is
     * preserved and returned by browsers for CSS custom properties, and is
     * likely an intentional part of the specification. This method trims any
     * white-space.
     *
     * @return {String}
     *
     * @see this.getRawValue()
     *   Gets the raw value without any trimming.
     */
    this.getValue = function() {
      return this.getRawValue().trim();
    };

    /**
     * Get the raw, untrimmed current value of the watched property.
     *
     * Note that white-space between the colon (:) and the semi-colon (;) is
     * preserved and returned by browsers for CSS custom properties, and is
     * likely an intentional part of the specification. This method returns the
     * raw value, including any white-space.
     *
     * @return {String}
     *
     * @see this.getValue()
     *   Gets the trimmed value.
     */
    this.getRawValue = function() {
      return value;
    };

    /**
     * Update the watched property value and trigger an event if changed.
     *
     * @return {Promise}
     *   A Promise that resolves when update tasks are complete.
     */
    this.update = function() {

      return fastdom.measure(function() {

        let oldValue = value;

        value = getComputedStyle(element).getPropertyValue(propertyName);

        if (oldValue === value) {
          return;
        }

        $element.trigger(changeEventName, [instance]);

      });

    };

    // Run once on initialization.
    this.update();

    /**
     * Layout size change event handler.
     *
     * @param {jQuery.Event} event
     *   The event object.
     *
     * @param {Object} newValues
     *   The new layout size values as of this event.
     *
     * @param {Object} oldValues
     *   The old layout sizes when previously measured.
     */
    function layoutSizeChangeHandler(event, newValues, oldValues) {

      switch (settings.direction) {

        case 'width':

          if (
            newValues.viewportWidth === oldValues.viewportWidth &&
            newValues.displaceWidth === oldValues.displaceWidth
          ) {
            return;
          }

          break;

        case 'height':

          if (
            newValues.viewportHeight === oldValues.viewportHeight &&
            newValues.displaceHeight === oldValues.displaceHeight
          ) {
            return;
          }

          break;

        default:

          if (
            newValues.viewportWidth === oldValues.viewportWidth &&
            newValues.displaceWidth === oldValues.displaceWidth &&
            newValues.viewportHeight === oldValues.viewportHeight &&
            newValues.displaceHeight === oldValues.displaceHeight
          ) {
            return;
          }

      }

      instance.update();

    }

    $(document).on(
      'layoutSizeChange.' + eventNamespace, layoutSizeChangeHandler
    );

    /**
     * Destroy this instance.
     */
    this.destroy = function() {
      $(document).off(
        'layoutSizeChange.' + eventNamespace, layoutSizeChangeHandler
      );
    };

  };

  /**
   * Create a responsive style property instance.
   *
   * @param {String} propertyName
   *
   * @return {responsiveStyleProperty}
   */
  this.create = function(propertyName, element, options) {
    return new responsiveStyleProperty(propertyName, element, options);
  }

});
});
