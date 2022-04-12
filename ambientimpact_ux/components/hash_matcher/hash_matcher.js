// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Hash matcher component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('hashMatcher', function(aiHashMatcher, $) {

  'use strict';

  /**
   * Event namespace name.
   *
   * @type {String}
   */
  const eventNamespace = this.getName();

  /**
   * The hash match change event.
   *
   * This is triggered on the document when the location hash has changed to
   * match a watched hash or stops matching a watched hash. Changes to the
   * location hash that don't involve a watched hash will not trigger this
   * event.
   *
   * Event handlers will be provided the following parameters:
   *
   * - The string hash (including the '#') that the event was triggered for.
   *
   * - A boolean indicating Whether the hash matches as the result of this
   *   change; if the hash has just started matching, this will be true; if the
   *   has just stopped matching, this will be false.
   *
   * @type {String}
   */
  const changeEventName = 'hashMatchChange';

  /**
   * Hash matcher object.
   *
   * @param {String} hash
   *   The hash for this instance to match, including the '#'.
   *
   * @constructor
   */
  function hashMatcher(hash) {

    // Initialize a URL object to verify that the provided hash is valid. If it
    // isn't, this will throw an error. If no error gets thrown, extract the
    // hash from it so that we have a predictable hash value.
    try {

      /**
       * URL object representing the parsed and normalized hash.
       *
       * @type {URL}
       */
      let urlObject = new URL(hash, location.href);

      hash = urlObject.hash;

    } catch (error) {

      console.error(
        'The provided hash ("%s") failed validation via a URL object: %o',
        hash, error
      );

      return;

    }

    /**
     * Whether the hash currently matches the provided hash.
     *
     * @type {Boolean}
     */
    let matches = location.hash === hash;

    /**
     * Get the current matches state.
     *
     * Note that we don't check location.hash here but rather the matches
     * variable because location.hash may not have updated yet in some edge
     * cases, in which case this would return an incorrect value.
     *
     * @return {Boolean}
     */
    this.matches = function() {
      return matches;
    };

    /**
     * Set the matching state to active.
     */
    function setActive() {

      matches = true;

      $(document).trigger(changeEventName, [hash, true]);

    };

    /**
     * Set the matching state to inactive.
     */
    function setInactive() {

      matches = false;

      $(document).trigger(changeEventName, [hash, false]);

    }

    /**
     * Set the matching state to active if it doesn't currently match.
     *
     * This will update the location hash if it doesn't already contain the hash
     * and will trigger the hash match change event.
     */
    this.setActive = function() {

      if (this.matches() === true) {
        return;
      }

      // Update the location hash if it doesn't already contain the watched hash
      // and return here as this will trigger hashChangeHandler() which will
      // call setActive() for us.
      if (location.hash !== hash) {
        location.hash = hash;

        return;
      }

      setActive();

    };

    /**
     * Set the matching state to inactive if it currently matches the hash.
     */
    this.setInactive = function() {

      if (this.matches() === false) {
        return;
      }

      // This will trigger hashChangeHandler() so don't call setInactive(), as
      // that would invoke setInactive() twice.
      history.back();

    };

    /**
     * hashchange event handler.
     *
     * @param {jQuery.Event} event
     *   The jQuery Event object.
     */
    function hashChangeHandler(event) {

      /**
       * The hash value for the old URL the window navigated from.
       *
       * @type {USVString}
       */
      let oldHash = new URL(event.originalEvent.oldURL).hash;

      /**
       * The hash value for the new URL the window is navigating to.
       *
       * @type {USVString}
       */
      let newHash = new URL(event.originalEvent.newURL).hash;

      if (newHash === hash && oldHash !== hash) {
        setActive();

      } else if (oldHash === hash && newHash !== hash) {
        setInactive();
      }

    };

    $(window).on('hashchange.' + eventNamespace, hashChangeHandler);

    /**
     * Destroy this instance.
     */
    this.destroy = function() {
      $(window).off('hashchange.' + eventNamespace, hashChangeHandler);
    };

  };

  /**
   * Create a hash matcher instance.
   *
   * @param {String} hash
   *   The hash for the instance to match, including the '#'.
   *
   * @return {hashMatcher}
   */
  this.create = function(hash) {
    return new hashMatcher(hash);
  }

});
