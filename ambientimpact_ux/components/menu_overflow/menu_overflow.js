// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu: overflow component
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

AmbientImpact.addComponent('menuOverflow', function(aiMenuOverflow, $) {
  'use strict';

  /**
   * The minimum number of menu items visible to use overflow.
   *
   * If there are fewer than this number, the all menu items will be placed in
   * the overflow menu.
   *
   * @type {Number}
   */
  this.minimumVisibleItems = 2;

  /**
   * Content to insert into the overflow toggle element in overflow mode.
   *
   * This can be anything that jQuery can work with, be it a string, an HTML
   * element, a jQuery collection, etc.
   *
   * @type {String|HTMLElement|jQuery}
   */
  this.overflowToggleContent = Drupal.t('More');

  /**
   * Content to insert into the overflow toggle element in menu.
   *
   * This can be anything that jQuery can work with, be it a string, an HTML
   * element, a jQuery collection, etc.
   *
   * @type {String|HTMLElement|jQuery}
   */
  this.menuToggleContent = Drupal.t('Menu');

  /**
   * The base BEM class for the overflow root and all child/state classes.
   *
   * @type {String}
   */
  var baseClass = 'menu-overflow';

  /**
   * The BEM modifier class for the overflow root it's hidden.
   *
   * @type {String}
   */
  var hiddenClass = baseClass + '--hidden';

  /**
   * The BEM descendent class for the overflow toggle element.
   *
   * @type {String}
   */
  var toggleClass = baseClass + '__toggle';

  /**
   * The BEM descendent class for the overflow menu element.
   *
   * @type {String}
   */
  var overflowMenuClass = baseClass + '__menu';

  /**
   * The Drupal menu item class.
   *
   * @type {String}
   */
  var menuItemClass = 'menu-item';

  /**
   * The Drupal menu item with active trail class.
   *
   * @type {String}
   */
  var menuItemActiveTrailClass = menuItemClass + '--active-trail';

  /**
   * The BEM modifier class for menu items when they're hidden.
   *
   * This is applied to both the existing menu items and the cloned menu items
   * in the overflow menu, as needed.
   *
   * @type {String}
   */
  var menuItemHiddenClass = menuItemClass + '--hidden';

  /**
   * The BEM modifier class for the target menu to indicate we've enhanced it.
   *
   * @type {String}
   */
  var menuEnhancedClass = 'menu--overflow-enhanced';

  /**
   * The BEM modifier class for the target menu when all items are in overflow.
   *
   * @type {String}
   */
  var menuAllOverflowClass = 'menu--all-overflow';

  /**
   * Update the overflow toggle content based on the current menu mode.
   *
   * @param {jQuery} $menu
   *   The target menu as a jQuery collection.
   *
   * @param {String} mode
   *   The menu mode that's being switched to; expected to be one of:
   *
   *   - 'overflow': some items are in the overflow menu, but not all
   *
   *   - 'menu': all items are in the overflow menu
   */
  function updateToggleContent($menu, mode) {
    var data = $menu[0].aiMenuOverflow;

    // If the current menu mode is the same as the passed mode, do nothing.
    if (data.mode === mode) {
      return;
    }

    data.mode = mode;

    // Grab the appropriate overflow toggle content depending on the mode.
    if (mode === 'menu') {
      var content = data.menuToggleContent;
    } else {
      var content = data.overflowToggleContent;
    }

    // Trigger an event on the overflow toggle before we make any changes.
    data.$overflowToggle.trigger('menuOverflowToggleContentBeforeUpdate');

    // Append the toggle content. Note that if the content is not a string, it's
    // likely to be an HTML element, in which case we have to use $().clone() or
    // we'll be moving the same, single element around if there is more than one
    // overflow toggle on a page. If it is a string, using $().clone() won't
    // work as expected, so we have to check for that.
    if (typeof content === 'string') {
      data.$overflowToggle.empty().append(content);

    } else {
      data.$overflowToggle.empty().append($(content).clone());
    }

    // Trigger another event on the overflow toggle after we've made our
    // updates.
    data.$overflowToggle.trigger('menuOverflowToggleContentAfterUpdate');
  };

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
    var menu = $menu[0];

    /**
     * The top-level menu items of the menu to attach to.
     *
     * @type {jQuery}
     */
    var $menuItems = $menu.children('.' + menuItemClass);

    /**
     * The overflow container which contains the toggle and overflow menu.
     *
     * @type {jQuery}
     */
    var $overflowContainer = $('<li></li>');

    /**
     * The overflow toggle element.
     *
     * @type {jQuery}
     */
    var $overflowToggle = $('<button></button>');

    /**
     * The overflow menu element.
     *
     * @type {jQuery}
     */
    var $overflowMenu = $('<ul></ul>');

    /**
     * The top-level menu items of the overflow menu.
     *
     * These are cloned from the existing items in the menu we're attaching to.
     *
     * @type {jQuery}
     */
    var $overflowItems = $menuItems.clone();

    $overflowToggle
      .addClass(toggleClass)
      .attr('type', 'button');

    $overflowMenu
      .addClass('menu ' + overflowMenuClass)
      .append($overflowItems);

    $overflowContainer
      // Add classes to identify this as an expanded menu item, in addition to
      // our base class indicating this item contains the overflow menu.
      .addClass([
        menuItemClass,
        menuItemClass + '--expanded',
        baseClass,
      ].join(' '))
      .append($overflowToggle)
      .append($overflowMenu)
      .appendTo($menu);

    $menu.addClass(menuEnhancedClass);

    // Attach an object to the menu HTML element with relevant jQuery
    // collections and various settings.
    menu.aiMenuOverflow = {
      $overflowContainer:     $overflowContainer,
      $overflowToggle:        $overflowToggle,
      overflowToggleContent:  this.overflowToggleContent,
      menuToggleContent:      this.menuToggleContent,
      mode:                   'initial'
    };

    /**
     * Update the visible and overflow items, based on current space.
     */
    menu.aiMenuOverflow.update = function() {
      // Show all items in both the visible menu and the overflow menu, so that
      // we can measure their widths and selectively hide individual items.
      $menuItems.add($overflowItems).removeClass(menuItemHiddenClass);

      /**
       * The width in pixels that the menu is currently taking up horizontally.
       *
       * @type {Number}
       */
      var menuWidth = $menu.width();

      /**
       * The maximum width in pixels the menu can display before overflowing.
       *
       * @type {Number}
       */
      var stopWidth = $overflowToggle.outerWidth();

      /**
       * Menu items in the visible menu bar that are to be hidden.
       *
       * @type {jQuery}
       */
      var $hiddenMenuItems = $();

      for (var i = 0; i < $menuItems.length; i++) {
        /**
         * The current menu item.
         *
         * @type {jQuery}
         */
        var $menuItem = $menuItems.eq(i);

        /**
         * The current menu item's width.
         *
         * @type {Number}
         */
        var menuItemWidth = $menuItem.outerWidth();

        // If the measured width up until now plus the current item width don't
        // exceed the menu width, add the current item width and keep iterating.
        if (menuWidth >= stopWidth + menuItemWidth) {
          stopWidth += menuItemWidth;

        // If we've exceeded the menu width, add this and all items after it in
        // the collection to the hidden collection, and break out of the loop.
        // This is probably the most performant and fool-proof way to ensure we
        // hide all items sequentially, versus the original method in the CSS
        // Tricks article - hiding one by one - where you could end with items
        // being hidden from the middle while following items may not be.
        } else {
          $hiddenMenuItems = $menuItems.slice(i);

          break;
        }
      }

      // If no menu items are to be hidden, hide the overflow container.
      if ($hiddenMenuItems.length === 0) {
        $overflowContainer
          .addClass(hiddenClass);

        // Don't forget to remove this in case we go right from all items in
        // overflow to none.
        $menu.removeClass(menuAllOverflowClass);

      // If we do have menu items to hide, do so while showing the overflow
      // container and overflow menu items whose counterparts were just hidden.
      } else {
        // If less than the minimum items are visible, place all items in
        // $hiddenMenuItems, add the class indicating we've entered 'menu' mode,
        // and update the toggle content to reflect this.
        if (
          $menuItems.length - $hiddenMenuItems.length <
            aiMenuOverflow.minimumVisibleItems
        ) {
          $hiddenMenuItems = $menuItems;

          $menu.addClass(menuAllOverflowClass);

          updateToggleContent($menu, 'menu');
        } else {
          $menu.removeClass(menuAllOverflowClass);

          updateToggleContent($menu, 'overflow');
        }

        $hiddenMenuItems.addClass(menuItemHiddenClass);

        $overflowContainer.removeClass(hiddenClass);

        for (var i = 0; i < $overflowItems.length; i++) {
          var $overflowItem = $overflowItems.eq(i);

          // If this overflow menu item's root menu counterpart is not in the
          // hidden menu items collection, hide the overflow menu item.
          if ($hiddenMenuItems.filter(function(index) {
            return $(this).children('a[data-drupal-link-system-path="' +
              $overflowItem.children('a').first()
                .attr('data-drupal-link-system-path') +
            '"]').first().length;
          }).length === 0) {
            $overflowItem.addClass(menuItemHiddenClass);

          // Otherwise, show the overflow menu item.
          } else {
            $overflowItem.removeClass(menuItemHiddenClass);

          }
        }

        // If any of the items displayed in the overflow menu are in the active
        // trail, add the active trail to the overflow container.
        if (
          $overflowItems.filter(':not(.' + menuItemHiddenClass + ')')
            .hasClass(menuItemActiveTrailClass)
        ) {
          $overflowContainer.addClass(menuItemActiveTrailClass);

        // If not, remove the active trail class from the overflow container.
        } else {
          $overflowContainer.removeClass(menuItemActiveTrailClass);
        }
      }
    };

    // Run once on attach. This is wrapped in a setTimeout() so that any layout
    // work that may be done immediately during or after attachment can register
    // to our calculations. Without this, the calculations may be off depending
    // on the screen width at attachment, and only correct themselves if/when
    // the viewport is resized or rotated.
    setTimeout(menu.aiMenuOverflow.update, 10);

    // Add an event handler to trigger on our debounced resize event.
    $(window).on('lazyResize.aiMenuOverflow', menu.aiMenuOverflow.update);
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
    var menu = $menu[0];

    // Don't do anything if we can't find our object attached to the menu
    // element.
    if (!('aiMenuOverflow' in menu)) {
      return;
    }

    $(window).off('lazyResize.aiMenuOverflow', menu.aiMenuOverflow.update);

    menu.aiMenuOverflow.$overflowContainer.remove();

    delete menu.aiMenuOverflow;

    $menu
      .removeClass(menuEnhancedClass)
      .children('.' + menuItemClass)
        .removeClass(menuItemHiddenClass);
  };
});
