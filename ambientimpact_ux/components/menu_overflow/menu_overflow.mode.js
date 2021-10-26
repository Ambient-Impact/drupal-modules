// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Menu overflow mode component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('menuOverflowMode', function(aiMenuOverflowMode, $) {

  'use strict';

  /**
   * Overflow mode object.
   *
   * @constructor
   */
  function overflowMode() {

    /**
     * Array of allowed overflow modes.
     *
     * @type {Array}
     */
    const allowedModes = ['initial', 'none', 'some', 'all'];

    /**
     * The current overflow mode.
     *
     * Should be one of 'initial', 'none', 'some', or 'all'.
     *
     * @type {String}
     */
    let currentMode = 'initial';

    /**
     * The previous overflow mode.
     *
     * @type {String}
     */
    let previousMode = '';

    /**
     * Get the current overflow menu mode.
     *
     * @return {String}
     */
    this.getMode = function() {
      return currentMode;
    }

    /**
     * Get the previous overflow menu mode.
     *
     * @return {String}
     */
    this.getPreviousMode = function() {
      return previousMode;
    }

    /**
     * Set the overflow mode.
     *
     * @param {String} newMode
     *   The new mode to set to.
     *
     * @return {Boolean}
     *   True if an update occurred, i.e. newMode is not the same as the
     *   previous mode; false if no update occurred, i.e. newMode is the same as
     *   the previous mode.
     *
     * @throws {Error}
     *   If newMode isn't one of 'initial', 'none', 'some', or 'all'.
     */
    this.setMode = function(newMode) {

      if (allowedModes.indexOf(newMode) === -1) {
        throw new Error(
          "newMode must be be one of 'initial', 'none', 'some', or 'all'."
        );
      }

      if (previousMode === newMode) {
        return false;
      }

      previousMode = currentMode;

      currentMode = newMode;

      return true;

    };

  };

  /**
   * Create an overflow mode object.
   *
   * @return {Object}
   *   An initialized overflow mode object.
   */
  this.createOverflowMode = function() {
    return new overflowMode();
  };

});
