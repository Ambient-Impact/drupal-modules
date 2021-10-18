// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu overflow toggle component
// -----------------------------------------------------------------------------

AmbientImpact.on([
  'fastdom', 'icon', 'menuOverflowShared',
], function(aiFastDom, aiIcon, aiMenuOverflowShared) {
AmbientImpact.addComponent(
  'menuOverflowToggle',
function(aiMenuOverflowToggle, $) {

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
   * Toggle object.
   *
   * @constructor
   */
  function toggle() {

    /**
     * The current overflow mode.
     *
     * @type {String}
     */
    let currentMode = 'initial';

    /**
     * Content for the overflow toggle element when some items are in overflow.
     *
     * This can be anything that jQuery can work with, be it a string, an HTML
     * element, a jQuery collection, etc.
     *
     * @type {String|HTMLElement|jQuery}
     */
    let toggleContentSome = aiIcon.get('arrow-down', {
      bundle: 'core',
      text:   Drupal.t('More')
    });

    /**
     * Content for the overflow toggle element when all items are in overflow.
     *
     * This can be anything that jQuery can work with, be it a string, an HTML
     * element, a jQuery collection, etc.
     *
     * @type {String|HTMLElement|jQuery}
     */
    let toggleContentAll = aiIcon.get('arrow-down', {
      bundle: 'core',
      text:   Drupal.t('Menu')
    });

    /**
     * The overflow toggle element jQuery collection.
     *
     * @type {jQuery}
     */
    let $toggle = $('<button></button>');

    $toggle.attr('type', 'button').addClass(classes.toggleClass);

    /**
     * Get the toggle element jQuery collection.
     *
     * @return {jQuery}
     *   The toggle element jQuery collection.
     */
    this.getToggle = function() {
      return $toggle;
    };

    /**
     * Get the toggle content for the provided overflow mode.
     *
     * @param {String} mode
     *   The mode to get content for.
     *
     * @return {String|HTMLElement|jQuery}
     *   The toggle content for the provided mode. Can be a string, an HTML
     *   element, or a jQuery collection. If 'mode' is 'all', the content for
     *   all items in overflow is returned. If 'mode' is anything else, the
     *   content for some items in overflow is returned.
     */
    this.getToggleContent = function(mode) {

      if (mode === 'all') {
        return toggleContentAll;
      } else {
        return toggleContentSome;
      }

    };

    /**
     * Set toggle content for the provided overflow mode.
     *
     * @param {String|HTMLElement|jQuery} content
     *   The content to set. Can be a string, an HTML element, or a jQuery
     *   collection.
     *
     * @param {String} mode
     *   The mode to set content for. If this is 'all', the content for all
     *   items in overflow is set. If this is anything else, the content for
     *   some items in overflow is set.
     */
    this.setToggleContent = function(content, mode) {

      if (mode === 'all') {
        toggleContentAll = content;
      } else {
        toggleContentSome = content;
      }

    }

    /**
     * Update the overflow toggle content to the provided overflow mode.
     *
     * @param {String} newMode
     *   The mode to switch to. If this is 'all', the mode is set for all items
     *   in overflow. If this is anything else, the mode is set for some items
     *   in overflow.
     *
     * @return {Promise}
     *   The Promise returned by FastDom which resolves once the toggle
     *   mutations are complete, or a resolved Promise if no changes were
     *   needed.
     */
    this.update = function(newMode) {

      // If the current menu mode is the same as the passed mode, do nothing.
      if (currentMode === newMode) {
        return Promise.resolve();
      }

      /**
       * The previous overflow mode we're updating from.
       *
       * @type {String}
       */
      let previousMode = currentMode;

      currentMode = newMode;

      /**
       * The toggle content.
       *
       * @type {String|HTMLElement|jQuery}
       */
      let content = this.getToggleContent(currentMode);

      // Trigger an event on the overflow toggle before we make any changes.
      $toggle.trigger(
        'menuOverflowToggleContentBeforeUpdate', [previousMode, newMode]
      );

      return fastdom.mutate(function() {

        // Append the toggle content. Note that if the content is not a string,
        // it's likely to be an HTML element, in which case we have to use
        // $().clone() or we'll be moving the same, single element around if
        // there is more than one overflow toggle on a page. If it is a string,
        // using $().clone() won't work as expected, so we have to check for
        // that.
        $toggle.empty().append(
          typeof content === 'string' ? content : $(content).clone()
        );

      }).then(function() {

        // Trigger another event on the overflow toggle after we've made our
        // updates.
        $toggle.trigger(
          'menuOverflowToggleContentAfterUpdate', [previousMode, newMode]
        );

      });

    };

  };

  /**
   * Create a toggle.
   *
   * @return {Function}
   *   An initialized toggle object.
   */
  this.createToggle = function() {
    return new toggle();
  };

});
});
