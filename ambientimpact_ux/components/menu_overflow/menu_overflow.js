// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu overflow component
// -----------------------------------------------------------------------------

// Priority+ navigation pattern; menu items that don't fit on a single line are
// displayed in an overflow menu.
//
// Note that the theme is expected to handle the actual drop-down layout and
// styling - the main function of this component is to manage which menu items
// are to be shown in the top-level menu and which are to be in the overflow
// menu.
//
// @see https://css-tricks.com/container-adapting-tabs-with-more-button/
//   Based on this article by Osvaldas Valutis.
//
// @todo Find out what the accessibility implications are of showing/hiding
// items entirely.

AmbientImpact.on([
  'fastdom', 'menuOverflowOverflowMenu', 'menuOverflowShared',
  'menuOverflowToggle',
], function(
  aiFastDom, aiMenuOverflowOverflowMenu, aiMenuOverflowShared,
  aiMenuOverflowToggle
) {
AmbientImpact.addComponent('menuOverflow', function(aiMenuOverflow, $) {

  'use strict';

  /**
   * Our event namespace.
   *
   * @type {String}
   */
  const eventNamespace = 'aiMenuOverflow';

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
   * The minimum number of menu items visible to use partial/some overflow.
   *
   * If there are fewer than this number, all menu items will be placed in the
   * overflow menu.
   *
   * @type {Number}
   */
  this.minimumVisibleItems = 2;

  /**
   * The viewport width of the last update in pixels.
   *
   * This is stored to ensure that we only run an update if the viewport width
   * has changed.
   *
   * @type {Number}
   */
  let lastUpdateViewportWidth = 0;

  /**
   * Attach to a provided menu element.
   *
   * @param {jQuery|HTMLElement} $menu
   *   An HTML element or a jQuery collection containing one element. Note that
   *   if the jQuery collection contains more than one element, only the first
   *   will be attached to.
   */
  this.attach = function($menu) {

    // Make sure $menu is a jQuery collection by having jQuery wrap it. If it's
    // already a jQuery collection, jQuery will return it as-is.
    $menu = $($menu).first();

    /**
     * The top level menu to attach to.
     *
     * @type {HTMLElement}
     */
    let menu = $menu[0];

    /**
     * The top-level menu items of the menu to attach to.
     *
     * @type {jQuery}
     */
    let $menuItems = $menu.children('.' + classes.menuItemClass);

    /**
     * The overflow container which contains the toggle and overflow menu.
     *
     * @type {jQuery}
     */
    let $overflowContainer = $('<li></li>');

    /**
     * Overflow menu object.
     *
     * @type {Object}
     */
    let overflowMenu = aiMenuOverflowOverflowMenu.createOverflowMenu(
      $menuItems
    );

    // Attach an object to the menu HTML element with relevant jQuery
    // collections and various settings.
    menu.aiMenuOverflow = {
      $overflowContainer: $overflowContainer,
      mode:               'initial',
      overflowMenu:       overflowMenu,
      toggle:             aiMenuOverflowToggle.createToggle()
    };

    $overflowContainer
      // Add classes to identify this as an expanded menu item, in addition to
      // our base class indicating this item contains the overflow menu.
      .addClass([
        classes.menuItemClass,
        classes.menuItemClass + '--expanded',
        classes.baseClass,
      ])
      .append(overflowMenu.getMenu());

    menu.aiMenuOverflow.toggle.getToggle().insertBefore(overflowMenu.getMenu());

    fastdom.mutate(function() {

      $overflowContainer.appendTo($menu);

      $menu
        .addClass(classes.menuEnhancedClass)
        .trigger('menuOverflowAttached');

    });

    /**
     * Update the visible and overflow items, based on current space.
     *
     * @param {Boolean} forceUpdate
     *   Whether to force an update even when the viewport width has not
     *   changed.
     */
    menu.aiMenuOverflow.update = function(forceUpdate) {

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

      /**
       * Menu items in the visible menu bar that are to be hidden.
       *
       * @type {jQuery}
       */
      let $hiddenMenuItems = $();

      fastdom.measure(function() {

         /**
         *  The current viewport width in pixels.
         *
         * @type {Number}
         */
        const viewportWidth = $(window).width();

        // Bail if not forcing an update and the viewport width hasn't changed.
        if (forceUpdate !== true && lastUpdateViewportWidth === viewportWidth) {
          return false;
        }

        // Update the last update viewport width.
        lastUpdateViewportWidth = viewportWidth;

        return true;

      }).then(function(shouldUpdate) {

        if (shouldUpdate === false) {
          return shouldUpdate;
        }

        return fastdom.mutate(function() {

          // Show all items so that we can measure their widths and selectively
          // hide individual items.
          $menuItems.removeClass(classes.menuItemHiddenClass);

          // Set the toggle to 'some' mode if it's still in 'initial' mode so
          // that we can get correct measurements on attach.
          if (menu.aiMenuOverflow.mode === 'initial') {
            return menu.aiMenuOverflow.toggle.update('some');
          }

        });

      }).then(function(shouldUpdate) {

        if (shouldUpdate === false) {
          return;
        }

        fastdom.measure(function() {

          menuWidth = $menu.width();

          stopWidth = menu.aiMenuOverflow.toggle.getToggle().outerWidth();

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
            // don't exceed the menu width, add the current item width and
            // keep iterating.
            if (menuWidth >= stopWidth + menuItemWidth) {
              stopWidth += menuItemWidth;

            // If we've exceeded the menu width, add this and all items after
            // it in the collection to the hidden collection, and break out of
            // the loop. This is probably the most performant and fool-proof
            // way to ensure we hide all items sequentially, versus the
            // original method in the CSS Tricks article - hiding one by one -
            // where you could end with items being hidden from the middle
            // while following items may not be.
            } else {
              $hiddenMenuItems = $menuItems.slice(i);

              break;
            }

          }

        });

        fastdom.mutate(function() {

          // If no menu items are to be hidden, hide the overflow container.
          if ($hiddenMenuItems.length === 0) {

            $overflowContainer.addClass(classes.hiddenClass);

            // Don't forget to remove this in case we go right from all items
            // in overflow to none.
            $menu.removeClass(classes.menuAllOverflowClass);

          // If we do have menu items to hide, do so while showing the
          // overflow container and overflow menu items whose counterparts
          // were just hidden.
          } else {

            // If less than the minimum items are visible, place all items in
            // $hiddenMenuItems, add the class indicating we've entered 'menu'
            // mode, and update the toggle content to reflect this.
            if (
              $menuItems.length - $hiddenMenuItems.length <
                aiMenuOverflow.minimumVisibleItems
            ) {
              $hiddenMenuItems = $menuItems;

              $menu.addClass(classes.menuAllOverflowClass);

              menu.aiMenuOverflow.toggle.update('all')

            } else {
              $menu.removeClass(classes.menuAllOverflowClass);

              menu.aiMenuOverflow.toggle.update('some')
            }

            $hiddenMenuItems.addClass(classes.menuItemHiddenClass);

            $overflowContainer.removeClass(classes.hiddenClass);

            // Update overflow menu item visibility.
            overflowMenu.updateItemVisibility().then(function() {

              // If any of the items displayed in the overflow menu are in the
              // active trail, add the active trail to the overflow container.
              if (
                overflowMenu.getVisibleItems()
                  .hasClass(classes.menuItemActiveTrailClass)
              ) {
                $overflowContainer.addClass(classes.menuItemActiveTrailClass);

              // If not, remove the active trail class from the overflow
              // container.
              } else {
                $overflowContainer
                  .removeClass(classes.menuItemActiveTrailClass);
              }

            });

          }

          // Trigger an event on updating the overflow menu.
          //
          // @todo Only trigger this if the number of items visible has changed?
          $menu.trigger('menuOverflowUpdated');

        });

      });

    };

    // Run once on attach.
    menu.aiMenuOverflow.update();

    // Add event handlers to trigger on our debounced resize event and when
    // the viewport offsets change, such as when the Drupal toolbar trays open
    // or close in vertical mode.
    $(window).on([
      'lazyResize.' + eventNamespace,
      'drupalViewportOffsetChange.' + eventNamespace
    ].join(' '), menu.aiMenuOverflow.update);

  };

  /**
   * Detach from a provided menu element.
   *
   * @param {jQuery|HTMLElement} $menu
   *   An HTML element or a jQuery collection containing one element. Note that
   *   if the jQuery collection contains more than one element, only the first
   *   will be detached from.
   */
  this.detach = function($menu) {

    // Make sure $menu is a jQuery collection by having jQuery wrap it. If it's
    // already a jQuery collection, jQuery will return it as-is.
    $menu = $($menu).first();

    /**
     * The top level menu to detach from.
     *
     * @type {HTMLElement}
     */
    let menu = $menu[0];

    /**
     * The top-level menu items of the menu to attach to.
     *
     * @type {jQuery}
     */
    let $menuItems = $menu.children('.' + classes.menuItemClass);

    // Don't do anything if we can't find our object attached to the menu
    // element.
    if (!('aiMenuOverflow' in menu)) {
      return;
    }

    $(window).off([
      'lazyResize.' + eventNamespace,
      'drupalViewportOffsetChange.' + eventNamespace
    ].join(' '), menu.aiMenuOverflow.update);

    menu.aiMenuOverflow.$overflowContainer.remove();

    menu.aiMenuOverflow.overflowMenu.destroy();

    delete menu.aiMenuOverflow;

    $menu
      .removeClass(classes.menuEnhancedClass)
      .children('.' + classes.menuItemClass)
        .removeClass(classes.menuItemHiddenClass);

    $menu.trigger('menuOverflowDetached');

  };

});
});
