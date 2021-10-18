// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu overflow shared data component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent(
  'menuOverflowShared',
function(aiMenuOverflowShared, $) {

  'use strict';

  /**
   * The base BEM class for the overflow root and all child/state classes.
   *
   * @type {String}
   */
  const baseClass = 'menu-overflow';

  /**
   * The BEM modifier class for the overflow root when it's hidden.
   *
   * @type {String}
   */
  const hiddenClass = baseClass + '--hidden';

  /**
   * The BEM descendent class for the overflow toggle element.
   *
   * @type {String}
   */
  const toggleClass = baseClass + '__toggle';

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
   * The Drupal menu item with active trail class.
   *
   * @type {String}
   */
  const menuItemActiveTrailClass = menuItemClass + '--active-trail';

  /**
   * The BEM modifier class for menu items when they're hidden.
   *
   * This is applied to both the existing menu items and the cloned menu items
   * in the overflow menu, as needed.
   *
   * @type {String}
   */
  const menuItemHiddenClass = menuItemClass + '--hidden';

  /**
   * The BEM modifier class for the target menu to indicate we've enhanced it.
   *
   * @type {String}
   */
  const menuEnhancedClass = 'menu--overflow-enhanced';

  /**
   * The BEM modifier class for the target menu when all items are in overflow.
   *
   * @type {String}
   */
  const menuAllOverflowClass = 'menu--all-overflow';

  /**
   * Get all the menu overflow classes.
   *
   * @return {Object}
   *   An object of class names as keys with the actual class strings as values.
   */
  this.getClasses = function() {
    return {
      baseClass:                baseClass,
      hiddenClass:              hiddenClass,
      toggleClass:              toggleClass,
      overflowMenuClass:        overflowMenuClass,
      menuItemClass:            menuItemClass,
      menuItemActiveTrailClass: menuItemActiveTrailClass,
      menuItemHiddenClass:      menuItemHiddenClass,
      menuEnhancedClass:        menuEnhancedClass,
      menuAllOverflowClass:     menuAllOverflowClass
    };
  };

});
