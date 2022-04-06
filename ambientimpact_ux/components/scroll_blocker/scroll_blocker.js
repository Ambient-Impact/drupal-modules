// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Scroll blocker component
// -----------------------------------------------------------------------------

// Provides an API to temporarily prevent scrolling the viewport or a scrollable
// element when one or more blocking elements are provided. Layout shifting due
// to removal of scrollbars on platforms where they take up space is prevented
// using the scrollbar gutter component. The primary use-case for this is with
// overlays and other modal elements.
//
// @see https://stackoverflow.com/questions/9280258/prevent-body-scrolling-but-allow-overlay-scrolling#9280412

AmbientImpact.on(['fastdom'], function(aiFastDom) {
AmbientImpact.addComponent('scrollBlocker', function(aiScrollBlocker, $) {

  'use strict';

  /**
   * FastDom instance.
   *
   * @type {FastDom}
   */
  const fastdom = aiFastDom.getInstance();

  /**
   * HTML class applied to an overflow element when scrolling is blocked.
   *
   * @type {String}
   */
  const blockedElementClass = 'scroll-blocked';

  /**
   * The overflow element property name where we store the instance reference.
   *
   * @type {String}
   */
  const propertyName = 'aiScrollBlocker';

  /**
   * Scroll blocker object.
   *
   * @param {HTMLElement|jQuery} $overflow
   *   A scrollable HTML element whose scrolling can be blocked, or a jQuery
   *   collection containing such an element. If this is a jQuery collection,
   *   only the first element will be used. If this is not provided, the <html>
   *   element will be used. In most cases, this should not be specified so that
   *   the <html> element is used to ensure that this instance acts as a
   *   singleton for co-ordination between components that require scroll
   *   blocking.
   *
   * @constructor
   */
  function scrollBlocker($overflow) {

    // Ensure a jQuery collection containing only one overflow element.
    $overflow = $($overflow).first();

    // If no overflow element was provided, fall back to the <html> element.
    if ($overflow.length === 0) {
      $overflow = $('html');
    }

    /**
     * Overflow element.
     *
     * @type {HTMLElement}
     */
    let overflow = $overflow[0];

    /**
     * Zero or more HTML elements which are currently active blockers.
     *
     * @type {jQuery}
     */
    let $activeBlockers = $();

    /**
     * Get this instance's overflow element.
     *
     * @return {jQuery}
     *   The overflow element jQuery collection.
     */
    this.getOverflowElement = function() {
      return $overflow;
    }

    /**
     * Determine if this instance has any active blockers.
     *
     * @return {Boolean}
     *   True if there's at least one active blocker, false otherwise.
     */
    this.hasBlockers = function() {
      return $activeBlockers.length > 0;
    };

    /**
     * Mark an element as blocking scroll.
     *
     * @param {HTMLElement|jQuery} $blockers
     *   An HTML element to store as a scroll blocker, or a jQuery collection
     *   containing one HTML element.
     *
     * @return {Promise}
     *   A Promise that is resolved when scrolling has been blocked.
     */
    this.block = function($blockers) {

      // Ensure a jQuery collection.
      $blockers = $($blockers);

      if ($blockers.length === 0) {
        return;
      }

      // Add $blockers to $activeBlockers.
      $activeBlockers = $activeBlockers.add($blockers);

      // If the overflow element already has the scroll blocked class, return an
      // already resolved Promise.
      if ($overflow.hasClass(blockedElementClass)) {

        return Promise.resolve();

      // Otherwise, return a FastDom Promise that resolves when the class has
      // been added.
      } else {

        return fastdom.mutate(function() {

          $overflow.addClass(blockedElementClass);

        });

      }

    };

    /**
     * Mark a previously opened scroll blocker as closed.
     *
     * @param {HTMLElement|jQuery} $blockers
     *   An HTML element to store as a scroll blocker, or a jQuery collection
     *   containing one HTML element.
     *
     * @return {Promise}
     *   A Promise that is resolved when scrolling has been unblocked.
     *
     * @todo Should this return a rejected Promise if there are still active
     *   blockers remaining?
     */
    this.unblock = function($blockers) {

      // Ensure a jQuery collection.
      $blockers = $($blockers);

      if ($blockers.length === 0) {
        return;
      }

      // Remove $blockers from $activeBlockers.
      $activeBlockers = $activeBlockers.not($blockers);

      // If no scroll blockers are still open, remove the HTML class from the
      // overflow element.
      if (
        $activeBlockers.length === 0 &&
        $overflow.hasClass(blockedElementClass)
      ) {

        return fastdom.mutate(function() {

          $overflow.removeClass(blockedElementClass);

        });

      // Otherwise, return an already resolved Promise.
      } else {

        return Promise.resolve();

      }

    };

    /**
     * Destroy this instance.
     *
     * @param {bool} force
     *   If true, will destroy the instance even if there are still active
     *   blockers, after unblocking them all. Defaults to false.
     *
     * @return {Promise}
     *   A Promise that's resolved when all blocking elements are unblocked,
     *   or resolved immediately if there still are blocking elements and the
     *   'force' parameter was not passed as true.
     */
    this.destroy = function(force) {

      if (this.hasBlockers() && force !== true) {
        return Promise.resolve();
      }

      $overflow.removeProp(propertyName);

      // If there are any elements still blocking, unblock them all.
      return this.unblock($activeBlockers);

    };

  }

  /**
   * Create a scroll blocker for the provided overflow element.
   *
   * This will create a scroll blocker if one is not already present on the
   * provided overflow element. If one is already present, that existing
   * instance will be returned.
   *
   * @param {HTMLElement|jQuery} $overflow
   *   A scrollable HTML element whose scrolling can be blocked, or a jQuery
   *   collection containing such an element. If this is a jQuery collection,
   *   only the first element will be used. If this is not provided, the <html>
   *   element will be used. In most cases, this should not be specified so that
   *   the <html> element is used to ensure that this instance acts as a
   *   singleton for co-ordination between components that require scroll
   *   blocking.
   *
   * @return {scrollBlocker}
   *   A scroll blocker instance.
   */
  this.create = function($overflow) {

    /**
     * Scroll blocker instance.
     *
     * @type {scrollBlocker}
     */
    let instance = new scrollBlocker($overflow);

    /**
     * The overflow element as determined by the instance.
     *
     * @type {jQuery}
     */
    $overflow = instance.getOverflowElement();

    // If there's already a scroll blocker instance for this overflow element,
    // return that.
    if (typeof $overflow.prop(propertyName) !== 'undefined') {
      return $overflow.prop(propertyName);
    }

    // Save the newly created instance as the active scroll blocker if one
    // wasn't found.
    $overflow.prop(propertyName, instance);

    return instance;

  };

});
});
