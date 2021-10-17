// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu overflow, overflow menu component
// -----------------------------------------------------------------------------

AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent(
  'menuOverflowOverflowMenu',
function(aiMenuOverflowOverflowMenu, $) {

  'use strict';

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * The base BEM class for the overflow root and all child/state classes.
   *
   * @type {String}
   */
  const baseClass = 'menu-overflow';

  /**
   * The BEM descendent class for the overflow menu element.
   *
   * @type {String}
   */
  const overflowMenuClass = baseClass + '__menu';

  /**
   * The Drupal menu item class.
   *
   * @type {String}
   */
  const menuItemClass = 'menu-item';

  /**
   * The BEM modifier class for menu items when they're hidden.
   *
   * @type {String}
   */
  const menuItemHiddenClass = menuItemClass + '--hidden';

  /**
   * Overflow menu object.
   *
   * @param {jQuery} $menuItems
   *   jQuery collection containing one or more original menu items to be cloned
   *   to the overflow menu.
   *
   * @constructor
   */
  function overflowMenu($menuItems) {

    /**
     * The overflow menu element.
     *
     * @type {jQuery}
     */
    let $overflowMenu = $('<ul></ul>');

    /**
     * The top-level menu items of the overflow menu.
     *
     * These are cloned from the existing menu items that we're provided.
     *
     * @type {jQuery}
     */
    let $overflowItems = $menuItems.clone();

    /**
     * Overflow menu items that are currently visible.
     *
     * @type {jQuery}
     */
    let $visibleOverflowItems = $overflowItems;

    // Save each each menu item element to its overflow menu item counterpart
    // and vice-versa for later reference.
    for (let i = 0; i < $menuItems.length; i++) {
      $menuItems[i].aiMenuOverflowCounterpart     = $overflowItems[i];
      $overflowItems[i].aiMenuOverflowCounterpart = $menuItems[i];
    }

    $overflowMenu
      .addClass(['menu', overflowMenuClass])
      .append($overflowItems);

    /**
     * Get the overflow menu jQuery collection.
     *
     * @return {jQuery}
     *   The overflow menu jQuery collection.
     */
    this.getMenu = function() {
      return $overflowMenu;
    };

    /**
     * Get the overflow menu items jQuery collection.
     *
     * @return {jQuery}
     *   The overflow menu items jQuery collection.
     */
    this.getItems = function() {
      return $overflowItems;
    };

    /**
     * Get overflow menu items that are currently visible.
     *
     * @return {jQuery}
     */
    this.getVisibleItems = function() {
      return $visibleOverflowItems;
    };

    /**
     * Get overflow menu items that are currently hidden.
     *
     * @return {jQuery}
     */
    this.getHiddenItems = function() {
      return $overflowItems.not(this.getVisibleItems());
    };

    /**
     * Update overflow menu item visibility based on original menu items.
     *
     * @return {Promise}
     *   The Promise returned by FastDom that is resolved when mutations are
     *   completed.
     */
    this.updateItemVisibility = function() {

      return fastdom.mutate(function() {

        /**
         * Temporary collection of new visible overflow menu items.
         *
         * @type {jQuery}
         */
        let $newVisible = $();

        for (let i = 0; i < $menuItems.length; i++) {

          /**
           * The current original menu item.
           *
           * @type {jQuery}
           */
          let $menuItem = $menuItems.eq(i);

          /**
           * The counterpart overflow menu item to $menuItem.
           *
           * @type {jQuery}
           */
          let $overflowItem = $($menuItem.prop('aiMenuOverflowCounterpart'));

          // If the original menu item is hidden, make the counterpart overflow
          // item visible.
          if ($menuItem.is('.' + menuItemHiddenClass)) {
            $overflowItem.removeClass(menuItemHiddenClass);
            $newVisible = $newVisible.add($overflowItem);

          // Otherwise, the original menu item is visible so hide the overflow
          // counterpart.
          } else {
            $overflowItem.addClass(menuItemHiddenClass);
          }

        }

        // Replace the visible overflow item collection with the new one.
        $visibleOverflowItems = $newVisible;

      });

    };

    /**
     * Destroy this overflow menu instance.
     *
     * @return {Promise}
     *   The Promise returned by FastDom that is resolved when mutations are
     *   completed.
     */
    this.destroy = function() {

      $menuItems.removeProp('aiMenuOverflowCounterpart');

      return fastdom.mutate(function() {
        $overflowMenu.remove();
      });

    };

  };

  /**
   * Create an overflow menu object.
   *
   * @param {jQuery} $menuItems
   *   jQuery collection containing one or more original menu items to be cloned
   *   to the overflow menu.
   *
   * @return {Function}
   *   An initialized overflow menu object.
   */
  this.createOverflowMenu = function($menuItems) {
    return new overflowMenu($menuItems);
  };

});
});
