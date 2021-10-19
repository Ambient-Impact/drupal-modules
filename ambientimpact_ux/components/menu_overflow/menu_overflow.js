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
  'fastdom', 'menuOverflowMeasure', 'menuOverflowOverflowMenu',
  'menuOverflowShared', 'menuOverflowToggle',
], function(
  aiFastDom, aiMenuOverflowMeasure, aiMenuOverflowOverflowMenu,
  aiMenuOverflowShared, aiMenuOverflowToggle
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
   * Menu overflow object.
   *
   * @param {jQuery} $menu
   *   A jQuery collection containing exactly one menu element.
   *
   * @constructor
   */
  function menuOverflow($menu) {

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
     * Measure object.
     *
     * @type {Object}
     */
    let measure = aiMenuOverflowMeasure.createMeasure(menu);

    /**
     * The current overflow mode.
     *
     * Can be one of 'initial', 'some', or 'all'.
     *
     * @type {String}
     */
    let mode = 'initial';

    /**
     * Overflow menu object.
     *
     * @type {Object}
     */
    let overflowMenu = aiMenuOverflowOverflowMenu.createOverflowMenu(
      $menuItems
    );

    /**
     * Toggle object.
     *
     * @type {Object}
     */
    let toggle = aiMenuOverflowToggle.createToggle();

    $overflowContainer
      // Add classes to identify this as an expanded menu item, in addition to
      // our base class indicating this item contains the overflow menu.
      .addClass([
        classes.menuItemClass,
        classes.menuItemClass + '--expanded',
        classes.baseClass,
      ])
      .append(overflowMenu.getMenu());

    toggle.getToggle().insertBefore(overflowMenu.getMenu());

    fastdom.mutate(function() {

      $overflowContainer.appendTo($menu);

      $menu
        .addClass(classes.menuEnhancedClass)
        .trigger('menuOverflowAttached');

    });

    /**
     * Get the current overflow menu mode.
     *
     * @return {String}
     */
    this.getMode = function() {
      return mode;
    }

    /**
     * Update the visible and overflow items, based on current space.
     *
     * @param {Boolean} forceUpdate
     *   Whether to force an update even when the viewport width has not
     *   changed.
     */
    this.update = function(forceUpdate) {

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
          if (mode === 'initial') {
            return toggle.update('some');
          }

        });

      }).then(function(shouldUpdate) {

        if (shouldUpdate === false) {
          return;
        }

        measure.getOverflowingMenuItems().then(function(
          $overflowingMenuItems
        ) { return fastdom.mutate(function() {

          $hiddenMenuItems = $overflowingMenuItems;

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

              toggle.update('all');

            } else {
              $menu.removeClass(classes.menuAllOverflowClass);

              toggle.update('some');
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

        })});

      });

    };

    // Run once on attach.
    this.update();

    // Add event handlers to trigger on our debounced resize event and when
    // the viewport offsets change, such as when the Drupal toolbar trays open
    // or close in vertical mode.
    $(window).on([
      'lazyResize.' + eventNamespace,
      'drupalViewportOffsetChange.' + eventNamespace
    ].join(' '), this.update);

    /**
     * Destroy this instance.
     *
     * @return {Promise}
     *   The Promise returned by FastDom that is resolved when mutations are
     *   completed.
     */
    this.destroy = function() {

      $(window).off([
        'lazyResize.' + eventNamespace,
        'drupalViewportOffsetChange.' + eventNamespace
      ].join(' '), this.update);

      overflowMenu.destroy();

      return fastdom.mutate(function() {

        $overflowContainer.remove();

        $menu
          .removeClass(classes.menuEnhancedClass)
          .children('.' + classes.menuItemClass)
            .removeClass(classes.menuItemHiddenClass);

        $menu.trigger('menuOverflowDetached');

      });

    };

  };

  /**
   * Attach to a provided menu element.
   *
   * @param {jQuery|HTMLElement} $menu
   *   A menu element or a jQuery collection containing one or more menu
   *   elements.
   */
  this.attach = function($menu) {

    // Ensure a jQuery collection.
    $menu = $($menu);

    for (let i = 0; i < $menu.length; i++) {
      $menu[i].aiMenuOverflow = new menuOverflow($menu.eq(i));
    }

  };

  /**
   * Detach from a provided menu element.
   *
   * @param {jQuery|HTMLElement} $menu
   *   A menu element or a jQuery collection containing one or more menu
   *   elements.
   */
  this.detach = function($menu) {

    // Ensure a jQuery collection.
    $menu = $($menu);

    for (let i = 0; i < $menu.length; i++) {

      if (!('aiMenuOverflow' in $menu[i])) {
        continue;
      }

      $menu[i].aiMenuOverflow.destroy();

    }

    $menu.removeProp('aiMenuOverflow');

  };

});
});
