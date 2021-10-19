// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu overflow measure component
// -----------------------------------------------------------------------------

AmbientImpact.on([
  'fastdom', 'menuOverflowShared',
], function(aiFastDom, aiMenuOverflowShared) {
AmbientImpact.addComponent(
  'menuOverflowMeasure',
function(aiMenuOverflowMeasure, $) {

  'use strict';

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * Menu overflow element classes.
   *
   * @type {Object}
   */
  const classes = aiMenuOverflowShared.getClasses();

  /**
   * Measure object.
   *
   * @param {jQuery} $menu
   *   A jQuery collection containing exactly one menu element.
   *
   * @constructor
   */
  function measure($menu) {

    /**
     * Get menu items that exceed the available space of the menu.
     *
     * @return {Promise}
     *   A Promise (returned by FastDom) which resolves with a jQuery collection
     *   containing the menu items exceed the available space of the menu.
     */
    this.getOverflowingMenuItems = function() {

      /**
       * The overflow container which contains the toggle and overflow menu.
       *
       * @type {jQuery}
       */
      let $overflowContainer = $menu.children('.' + classes.baseClass).first();

      /**
       * The top-level menu items of the menu to attach to.
       *
       * This excludes the overflow container item.
       *
       * @type {jQuery}
       */
      let $menuItems = $menu.children('.' + classes.menuItemClass)
        .not($overflowContainer);

      /**
       * Menu items that are overflowing the current space of the menu.
       *
       * @type {jQuery}
       */
      let $overflowingMenuItems = $();

      /**
       * The width in pixels that the menu is currently taking up horizontally.
       *
       * @type {Number}
       */
      let menuWidth;

      /**
       * The maximum width in pixels the menu can display before overflowing.
       *
       * @type {Number}
       */
      let stopWidth;

      return fastdom.measure(function() {

        menuWidth = $menu.width();

        stopWidth = $overflowContainer.outerWidth();

        for (let i = 0; i < $menuItems.length; i++) {

          /**
           * The current menu item.
           *
           * @type {jQuery}
           */
          let $menuItem = $menuItems.eq(i);

          /**
           * The current menu item's width.
           *
           * @type {Number}
           */
          let menuItemWidth = $menuItem.outerWidth();

          // If the measured width up until now plus the current item width
          // don't exceed the menu width, add the current item width and keep
          // going.
          if (menuWidth >= stopWidth + menuItemWidth) {
            stopWidth += menuItemWidth;

          // If we've exceeded the menu width, add this and all items after it
          // in the collection to the hidden collection, and break out of the
          // loop. This is probably the most performant and fool-proof way to
          // ensure we hide all items sequentially, versus the original method
          // in the CSS Tricks article - hiding one by one - where you could end
          // with items being hidden from the middle while subsequent items may
          // not be.
          } else {
            $overflowingMenuItems = $menuItems.slice(i);

            break;
          }

        }

        return $overflowingMenuItems;

      });

    };

  };

  /**
   * Create an overflow menu measure object.
   *
   * @param {HTMLElement|jQuery} menu
   *   A menu HTML element or a jQuery collection. If this is a jQuery
   *   collection, only the first element will be used.
   *
   * @return {Object}
   *   An initialized measure object.
   */
  this.createMeasure = function(menu) {
    return new measure($(menu).first());
  };

});
});
