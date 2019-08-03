// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu: horizontal peek component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('menuHorizontalPeek', function(
  aiMenuHorizontalPeek, $
) {
  'use strict';

  /**
   * The number of pixels to peek by scrolling horizontally and back.
   *
   * @type {Number}
   */
  this.peekAmount = 100;

  /**
   * The amount the horizontal content must exceed its container to peek.
   *
   * @type {Number}
   */
  this.peekThreshold = 30;

  /**
   * The duration of the peek animation.
   *
   * @type {Number}
   */
  this.peekDuration = 1500;

  /**
   * Run a peek animation on a menu.
   *
   * @param {jQuery|HTMLElement} $menu
   *   The menu to peek, wrapped in a jQuery object or as a plain HTML element.
   */
  this.peek = function($menu) {
    // Make sure this is wrapped in a jQuery object.
    $menu = $($menu);

    /**
     * The width in pixels that the menu is currently taking up horizontally.
     *
     * @type {Number}
     */
    var menuWidth = $menu.width();

    /**
     * The total width of the content inside of the menu, in pixels.
     *
     * @type {Number}
     */
    var contentWidth = Math.round($menu.find('li:last').offset().left) +
      $menu.find('li:last').width() -
      Math.round($menu.find('li:first').offset().left);

    // If the content width doesn't exceed the menu width plus the threshold,
    // return without animating.
    if ((menuWidth + this.peekThreshold) >= contentWidth) {
      return;
    }

    // Clamp peek amount to the available space so that the animation doesn't
    // risk awkwardly stopping mid-animation if the available scrolling space is
    // smaller than the default peek amount.
    if (contentWidth - menuWidth < this.peekAmount) {
      var peekAmount = contentWidth - menuWidth;
    } else {
      var peekAmount = this.peekAmount;
    }

    $menu
      // Gently scroll to the right.
      .animate({scrollLeft: peekAmount}, this.peekDuration)
      // Gently scroll back to left.
      .animate({scrollLeft: 0}, this.peekDuration)
      .one('touchstart', function(event) {
        // Stop the animation immediately if a touch event is detected, and
        // clear the queue.
        $menu.stop(true);
      });
  };
});
