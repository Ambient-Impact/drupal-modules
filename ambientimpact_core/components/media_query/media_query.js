/* -----------------------------------------------------------------------------
  Ambient.Impact - Core - Media Query component
----------------------------------------------------------------------------- */

// This wraps the native window.matchMedia with some convenience methods. Will
// not register if window.matchMedia isn't present.
// @see https://developer.mozilla.org/en-US/docs/Web/API/Window/matchMedia
// @see https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries/Testing_media_queries

// @todo include-media style breakpoint events, see bottom of file.
// @see  https://github.com/eduardoboucas/include-media-export

AmbientImpact.onGlobals(['matchMedia'], function() {
AmbientImpact.addComponent('mediaQuery', function(aiMediaQuery, $) {
  'use strict';

  /**
   * Get the MediaQueryList object from a media query string.
   *
   * If passed a MediaQueryList object, will simply return it. This is useful
   * to reuse a MediaQueryList object but not have to check every time what
   * we're passed.
   *
   * @param {string|MediaQueryList} mediaQuery
   *
   * @return {MediaQueryList}
   */
  this.getQuery = function(mediaQuery) {
    // Create a new MediaQueryList if this is a string.
    if (typeof mediaQuery === 'string') {
      return matchMedia(mediaQuery);

    // If this looks like a MediaQueryList, just return it as-is.
    } else if ('matches' in mediaQuery) {
      return mediaQuery;
    }
  };

  /**
   * Check if a media query currently matches.
   *
   * @param {string|MediaQueryList} mediaQuery
   *
   * @return {bool}
   *   True if the string or MediaQueryList currently matches, false
   *   otherwise.
   */
  this.matches = function(mediaQuery) {
    return this.getQuery(mediaQuery).matches;
  };

  /**
   * Bind an event listener to watch for media query events.
   *
   * @param {string|MediaQueryList} mediaQuery
   *
   * @param {function} callback
   *
   * @return {MediaQueryList}
   */
  this.onMedia = function(mediaQuery, callback) {
    mediaQuery = this.getQuery(mediaQuery);

    mediaQuery.addListener(callback);

    return mediaQuery;
  };

  /**
   * Unbind an event listener from media query events.
   *
   * @param {string|MediaQueryList} mediaQuery
   *
   * @param {function} callback
   *
   * @return {MediaQueryList}
   */
  this.offMedia = function(mediaQuery, callback) {
    mediaQuery = this.getQuery(mediaQuery);

    mediaQuery.removeListener(callback);

    return mediaQuery;
  };

  // this.onBreakpoint = function(breakpoint, callback) {};

  // this.offBreakpoint = function(breakpoint, callback) {};
});
});
