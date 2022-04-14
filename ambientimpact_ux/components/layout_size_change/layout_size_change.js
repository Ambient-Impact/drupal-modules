// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Layout size change component
// -----------------------------------------------------------------------------

// Note that the resize event is only triggered on a resize after initial page
// load while the displacement event is triggered at least once during the
// initial load; attempting to prevent acting on the initial displace event is
// surprisingly difficult because it sometimes triggers before this component
// has attached its event handler.

AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent('layoutSizeChange', function(aiLayoutSizeChange, $) {

  'use strict';

  /**
   * Name of the event triggered when layout size change has been detected.
   *
   * @type {String}
   */
  const changeEventName = 'layoutSizeChange';

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
   * The viewport and displacement values of the previous update.
   *
   * @type {Object}
   */
  let lastUpdateValues = {
    viewportWidth:  0,
    viewportHeight: 0,
    displaceWidth:  0,
    displaceHeight: 0,
  };

  /**
   * Determine if a viewport axis has changed since the last check.
   *
   * @param {String} axis
   *   One of 'width' or 'height'.
   *
   * @return {Boolean}
   *   True if the specified axis has changed since the last check, false
   *   otherwise.
   */
  function hasViewportAxisChanged(axis) {

    /**
     * The viewport size along the provided axis in pixels.
     *
     * @type {Number}
     */
    const size = $(window)[axis]();

    /**
     * The corresponding key name in lastUpdateValues for the provided axis.
     *
     * @type {String}
     */
    let key;

    if (axis === 'width') {
      key = 'viewportWidth';

    } else if (axis === 'height') {
      key = 'viewportHeight';

    }

    if (lastUpdateValues[key] === size) {
      return false;
    }

    lastUpdateValues[key] = size;

    return true;

  }

  /**
   * Resize handler; checks if the viewport has changed size.
   *
   * @param {jQuery.Event} event
   *   The event object.
   */
  function checkViewport(event) {

    fastdom.measure(function() {

      /**
       * A copy of lastUpdateValues with the old measured values.
       *
       * @type {Object}
       */
      let oldValues = $.extend(true, {}, lastUpdateValues);

      /**
       * Whether the viewport width has changed.
       *
       * Note that this has to be done separately outside of the if statement
       * so both axes get checked and updated.
       *
       * @type {Boolean}
       */
      const hasWidthChanged = hasViewportAxisChanged('width');

      /**
       * Whether the viewport height has changed.
       *
       * Note that this has to be done separately outside of the if statement
       * so both axes get checked and updated.
       *
       * @type {Boolean}
       */
      const hasHeightChanged = hasViewportAxisChanged('height');

      // Bail if the viewport hasn't changed size since the last update.
      if (!hasWidthChanged && !hasHeightChanged) {
        return;
      }

      /**
       * A copy of lastUpdateValues with the newly measured values.
       *
       * @type {Object}
       */
      let newValues = $.extend(true, {}, lastUpdateValues);

      $(document).trigger(changeEventName, [newValues, oldValues]);

    });

  };

  /**
   * Determine if displacement size has changed along the specified axis.
   *
   * @param {String} axis
   *   One of 'width' or 'height'.
   *
   * @param {Object} offsets
   *   The current displacement offsets.
   *
   * @return {Boolean}
   *   True if the available viewport size along the specified axis minus the
   *   displacement on that same axis has changed, false otherwise.
   */
  function hasDisplaceAxisChanged(axis, offsets) {

    /**
     * Whether the viewport size has changed along the specified axis.
     *
     * @type {Boolean}
     */
    let viewportChanged = false;

    /**
     * The viewport size along the specified axis, in pixels.
     *
     * @type {Number}
     */
    let viewportSize;

    /**
     * The previous displace size along the specified axis, in pixels.
     *
     * @type {Number}
     */
    let oldDisplaceSize;

    switch (axis) {

      case 'width':

        // Update the stored viewport width if it's changed. Note that we don't
        // care about the return value here.
        hasViewportAxisChanged('width');

        viewportSize = lastUpdateValues.viewportWidth;

        oldDisplaceSize = lastUpdateValues.displaceWidth;

        lastUpdateValues.displaceWidth = viewportSize - offsets.left -
          offsets.right;

        return (oldDisplaceSize !== lastUpdateValues.displaceWidth);

      case 'height':

        // Update the stored viewport height if it's changed. Note that we don't
        // care about the return value here.
        hasViewportAxisChanged('height');

        viewportSize = lastUpdateValues.viewportHeight;

        oldDisplaceSize = lastUpdateValues.displaceHeight;

        lastUpdateValues.displaceHeight = viewportSize - offsets.top -
          offsets.bottom;

        return (oldDisplaceSize !== lastUpdateValues.displaceHeight);

    }

  };

  /**
   * Viewport displace change handler; checks if displacement has changed size.
   *
   * @param {jQuery.Event} event
   *   The event object.
   */
  function checkDisplace(event, offsets) {

    fastdom.measure(function() {

      /**
       * A copy of lastUpdateValues with the old measured values.
       *
       * @type {Object}
       */
      let oldValues = $.extend(true, {}, lastUpdateValues);

      /**
       * Whether the viewport displacement width has changed.
       *
       * Note that this has to be done separately outside of the if statement
       * so both axes get checked and updated.
       *
       * @type {Boolean}
       */
      const hasWidthChanged = hasDisplaceAxisChanged('width', offsets);

      /**
       * Whether the viewport displacement height has changed.
       *
       * Note that this has to be done separately outside of the if statement
       * so both axes get checked and updated.
       *
       * @type {Boolean}
       */
      const hasHeightChanged = hasDisplaceAxisChanged('height', offsets);

      // Bail if displacement and the viewport haven't changed size since the
      // last update.
      if (!hasWidthChanged && !hasHeightChanged) {
        return;
      }

      /**
       * A copy of lastUpdateValues with the newly measured values.
       *
       * @type {Object}
       */
      let newValues = $.extend(true, {}, lastUpdateValues);

      $(document).trigger(changeEventName, [newValues, oldValues]);

    });

  };


  this.addBehaviour(
    'AmbientImpactLayoutSizeChange',
    'ambientimpact-layout-size-change',
    'html',
    function(context, settings) {

      $(window).on('lazyResize.' + eventNamespace, checkViewport);

      $(window).on(
        'drupalViewportOffsetChange.' + eventNamespace, checkDisplace
      );

    },
    function(context, settings, trigger) {

      $(window).off('lazyResize.' + eventNamespace, checkViewport);

      $(window).off(
        'drupalViewportOffsetChange.' + eventNamespace, checkDisplace
      );

    }
  );

});
});
