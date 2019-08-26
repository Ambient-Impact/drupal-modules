// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu: drop-down component
// -----------------------------------------------------------------------------

AmbientImpact.on('icon', function(aiIcon) {
AmbientImpact.addComponent('menuDropDown', function(aiMenuDropDown, $) {
  'use strict';

  /**
   * Expanded menu item link click handler.
   *
   * This prevents the default action on the link so that the menu can be
   * expanded on touch devices.
   *
   * @param {jQuery.Event} event
   */
  function clickHandler(event) {
    event.preventDefault();
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

    $menu.find('.menu-item--expanded').children('a, button')
      .wrapTextWithIcon('arrow-down', {bundle: 'core'})
      .on('click.aiMenuDropDown', clickHandler);
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

    $menu.find('.menu-item--expanded').children('a, button')
      .unwrapTextWithIcon()
      .off('click.aiMenuDropDown', clickHandler);
  };
});
});
