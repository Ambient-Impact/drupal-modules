/* -----------------------------------------------------------------------------
  Ambient.Impact - Core - Menu has active item component
----------------------------------------------------------------------------- */

// Add and remove a class on the menu if a menu item is hovered or focused,
// so that we can remove the indicator from the current page link, etc.

AmbientImpact.onGlobals(['ally.get.activeElement'], function() {
AmbientImpact.addComponent('menuHasActiveItem', function(
  aiMenuHasActiveItem, $
) {
  'use strict';

  this.menuHasActiveItemClass = 'menu--has-active-item';

  // Private event handlers
  var menuEnterHandler = function(event) {
      $(this).closest('.menu')
        .addClass(aiMenuHasActiveItem.menuHasActiveItemClass);
    },
    menuLeaveHandler = function(event) {
      $(this).closest('.menu')
        .filter(function() {
          // Don't do anything if one of the links is
          // still focused
          return $(ally.get.activeElement())
            .closest('.menu')[0] != this;
        })
        .removeClass(aiMenuHasActiveItem.menuHasActiveItemClass);
    };

  $('body')
    // Enter/leave menu via mouse
    .on({
      mouseenter: menuEnterHandler,
      mouseleave: menuLeaveHandler
    }, '.menu')
    // Enter/leave links via keyboard
    .on({
      focus:    menuEnterHandler,
      blur:   menuLeaveHandler
    }, '.menu a');
});
});
