// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu: drop-down component
// -----------------------------------------------------------------------------

// JavaScript implementation of drop-down sub-menu showing and hiding based on
// click/tap, focus, and mouse over. This mostly duplicates what we can do with
// CSS, but adds the ability to programmatically alter the show/hide logic.
//
// @see https://caniuse.com/#feat=focusin-focusout-events
//   Browser support for focusin and focusout. It may be a good idea to add a
//   fall back in case the browser doesn't support them?

AmbientImpact.on('icon', function(aiIcon) {
AmbientImpact.onGlobals('jQuery.fn.hoverIntent', function() {
AmbientImpact.addComponent('menuDropDown', function(aiMenuDropDown, $) {
  'use strict';

  /**
   * Class applied to a menu item when its child menu is open.
   *
   * @type {String}
   */
  var menuItemOpenClass = 'menu-item--menu-open';

  /**
   * Class applied to a menu item when its child menu is closed.
   *
   * @type {String}
   */
  var menuItemClosedClass = 'menu-item--menu-closed';

  /**
   * Minimum time required for a click to close a menu from it opening.
   *
   * This is in milliseconds, and is a good enough assumption that most
   * browsers will have much less than this between a focusin, and a click event
   * that follows, while users will be very unlikely to click this fast
   * intentionally to close a menu right after opening.
   *
   * @type {Number}
   *
   * @see clickHandler
   *   Uses this.
   */
  var lastOpenThreshold = 100;

  /**
   * hoverIntent settings for drop-down menus.
   *
   * @type {Object}
   *
   * @see https://briancherne.github.io/jquery-hoverIntent/
   */
  var hoverIntentSettings = {
    over:         mouseEnterHandler,
    out:          mouseLeaveHandler,

    // This sets a timeout before the 'out' handler can be triggered after the
    // pointer leaves the element, cancelling the 'out' if the user moves the
    // pointer back into the element. This is to give the sub-menu a buffer
    // against user error and not close prematurely.
    timeout:      400,

    // These make the the 'over' handler trigger sooner than the default
    // hoverIntent settings.
    sensitivity:  3,
    interval:     30
  };

  /**
   * Expanded menu item link click handler.
   *
   * This enables clicks or taps on the menu item to open and close the menu.
   *
   * Note that this performs a check that a menu open has not occurred within
   * the millisecond threshold in lastOpenThreshold, to avoid the menu being
   * opened by a focusin and then instantly closing when the click that caused
   * the focusin is triggered.
   *
   * @param {jQuery.Event} event
   *   The jQuery event object.
   *
   * @see lastOpenThreshold
   *   Threshold amount, in milliseconds.
   */
  function clickHandler(event) {
    var data = this.aiMenuDropDown;

    if (Date.now() - data.lastOpen > lastOpenThreshold) {
      if (data.isOpen()) {
        data.close();
      } else {
        data.open();
      }
    }

    event.preventDefault();
  };

  /**
   * Menu item focusin event handler; opens menu.
   *
   * @param {jQuery.Event} event
   *   The jQuery event object.
   */
  function focusInHandler(event) {
    this.aiMenuDropDown.open();
  };

  /**
   * Menu item focusout event handler; closes menu.
   *
   * @param {jQuery.Event} event
   *   The jQuery event object.
   */
  function focusOutHandler(event) {
    var data = this.aiMenuDropDown;

    if (
      'relatedTarget' in event &&
      data.$menuItem.find(event.relatedTarget).length === 0
    ) {
      data.close();
    }
  };

  /**
   * Menu item mouseenter event handler; opens menu.
   *
   * @param {jQuery.Event} event
   *   The jQuery event object.
   */
  function mouseEnterHandler(event) {
    this.aiMenuDropDown.open();
  };

  /**
   * Menu item mouseleave event handler; closes menu.
   *
   * @param {jQuery.Event} event
   *   The jQuery event object.
   */
  function mouseLeaveHandler(event) {
    this.aiMenuDropDown.close();
  };

  /**
   * Attach to a provided menu element.
   *
   * @param {jQuery|HTMLElement} $menu
   *   An HTML element or a jQuery collection containing one element. Note that
   *   if the jQuery collection contains more than one element, only the first
   *   will be attached to.
   *
   * @see this.detach()
   *   The reverse of this.
   */
  this.attach = function($menu) {
    // Make sure $menu is a jQuery collection by having jQuery wrap it. If it's
    // already a jQuery collection, jQuery will return it as-is.
    $menu = $($menu).first();

    /**
     * The menu items, wrapped in a jQuery collection.
     *
     * This only looks for top level, expanded menu items, i.e. menu items that
     * have a sub-menu.
     *
     * @type {jQuery}
     */
    var $menuItems = $menu.children('.menu-item--expanded');

    /**
     * The menu item triggers, wrapped in a jQuery collection.
     *
     * These are the links or buttons that are direct children of the menu item.
     *
     * @type {jQuery}
     */
    var $triggers = $();

    // Menu items start off with the closed class applied.
    $menuItems.addClass(menuItemClosedClass);

    for (var i = 0; i < $menuItems.length; i++) {
      /**
       * The current menu item, wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $menuItem = $menuItems.eq(i);

      /**
       * The menu item trigger, wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $trigger = $menuItem.children('a, button').first();

      $triggers = $triggers.add($trigger);

      // Attach an object containing our properties and methods to the menu item
      // HTML element, for easy access.
      $menuItem[0].aiMenuDropDown = {
        /**
         * The current menu item, wrapped in a jQuery collection.
         *
         * @type {jQuery}
         */
        $menuItem: $menuItem,

        /**
         * The menu item trigger, wrapped in a jQuery collection.
         *
         * @type {jQuery}
         */
        $trigger: $trigger,

        /**
         * The last time this menu item's child menu was opened, as a timestamp.
         *
         * @type {Number}
         */
        lastOpen: Date.now(),

        /**
         * Open this menu item's child menu.
         */
        open: function() {
          // Update the last open timestamp.
          this.lastOpen = Date.now();

          if (this.isOpen()) {
            return;
          }

          this.$menuItem.trigger(
            'menuDropDownOpening',
            this.$menuItem[0].aiMenuDropDown
          );

          this.$menuItem
            .addClass(menuItemOpenClass)
            .removeClass(menuItemClosedClass);

          this.$menuItem.trigger(
            'menuDropDownOpened',
            this.$menuItem[0].aiMenuDropDown
          );
        },

        /**
         * Close this menu item's child menu.
         */
        close: function() {
          if (!this.isOpen()) {
            return;
          }

          this.$menuItem.trigger(
            'menuDropDownClosing',
            this.$menuItem[0].aiMenuDropDown
          );

          this.$menuItem
            .addClass(menuItemClosedClass)
            .removeClass(menuItemOpenClass);

          this.$menuItem.trigger(
            'menuDropDownClosed',
            this.$menuItem[0].aiMenuDropDown
          );
        },

        /**
         * Determine if this menu item's child menu is currently open.
         *
         * @return {Boolean}
         *   True if the menu is open, false if not.
         */
        isOpen: function() {
          return this.$menuItem.hasClass(menuItemOpenClass);
        }
      };

      // Duplicate the aiMenuDropDown object for the click handler.
      $trigger[0].aiMenuDropDown = $menuItem[0].aiMenuDropDown;
    }

    // Give the menu a temporary explicit height so that inserting the icon
    // doesn't cause the layout to shift.
    $menu.css('height', $menu.height() + 'px');

    // Wrap trigger content in an icon, and attach the click event handler.
    $triggers
      .wrapTextWithIcon('arrow-down', {bundle: 'core'})
      .on('click.aiMenuDropDown', clickHandler);

    // Remove the explicit height after a brief delay to allow the browser to
    // repaint/layout.
    setTimeout(function() {
      $menu.css('height', '');
    }, 10);

    // Attach the focus and mouse event handlers to the menu item itself.
    $menuItems
      .on({
        'focusin.aiMenuDropDown':   focusInHandler,
        'focusout.aiMenuDropDown':  focusOutHandler
      })
      .hoverIntent(hoverIntentSettings);
  };

  /**
   * Detach from a provided menu element.
   *
   * @param {jQuery|HTMLElement} $menu
   *   An HTML element or a jQuery collection containing one element. Note that
   *   if the jQuery collection contains more than one element, only the first
   *   will be detached from.
   *
   * @see this.attach()
   *   The reverse of this.
   */
  this.detach = function($menu) {
    // Make sure $menu is a jQuery collection by having jQuery wrap it. If it's
    // already a jQuery collection, jQuery will return it as-is.
    $menu = $($menu).first();

    /**
     * The menu items, wrapped in a jQuery collection.
     *
     * This only looks for top level, expanded menu items, i.e. menu items that
     * have a sub-menu.
     *
     * @type {jQuery}
     */
    var $menuItems = $menu.children('.menu-item--expanded');

    /**
     * The menu item triggers, wrapped in a jQuery collection.
     *
     * These are the links or buttons that are direct children of the menu item.
     *
     * @type {jQuery}
     */
    var $triggers = $();

    for (var i = 0; i < $menuItems.length; i++) {
      var $menuItem = $menuItems.eq(i);
      var $trigger = $menuItem[0].aiMenuDropDown.$trigger;

      $triggers = $triggers.add($trigger);

      delete $menuItem[0].aiMenuDropDown;

      delete $trigger[0].aiMenuDropDown;
    }

    $triggers
      .unwrapTextWithIcon()
      .off('click.aiMenuDropDown', clickHandler);

    $menuItems
      .removeClass([menuItemClosedClass, menuItemOpenClass].join(' '))
      .off({
        'focusin.aiMenuDropDown':   focusInHandler,
        'focusout.aiMenuDropDown':  focusOutHandler
      })
      .off([
        // hoverIntent doesn't yet have a way to unbind easily, so we have to
        // manually unbind the handlers it attaches.
        //
        // @see https://github.com/briancherne/jquery-hoverIntent/issues/55
        'mouseenter.hoverIntent',
        'mouseleave.hoverIntent',
      ].join(' '))
      // Remove hoverIntent data.
      .removeData('hoverIntent');
  };
});
});
});
