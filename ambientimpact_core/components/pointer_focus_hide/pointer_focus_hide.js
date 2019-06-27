// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Pointer focus hide component
// -----------------------------------------------------------------------------

// This hides the focus ring (outline) if a pointer was used to focus the
// element, but maintains focus and will show the outline if a user uses
// keyboard navigation.

// @todo Should we bind events to page visibility events to lock and unlock the
// pointer focus source so that it doesn't register as "script" when the page is
// shown again, thus always outlining the last focused element?
// @see https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API

// @todo When :focus-visible becomes better supported, deprecate this in favour
// of that?
// @see https://caniuse.com/#feat=css-focus-visible

AmbientImpact.onGlobals('ally.style.focusSource', function() {
AmbientImpact.addComponent('pointerFocusHide', function(
  aiPointerFocusHide, $
) {
  'use strict';

  /**
   * An array of element selectors to watch.
   *
   * @type {Array}
   */
  this.elements = [
    'a', ':button', ':submit', ':reset', ':radio',
    ':checkbox', '[role="button"]', '[tabindex][tabindex!="-1"]'
  ];

  /**
   * The class to apply to elements when a pointer was used to focus them.
   *
   * This hides the focus outline in pointer_focus_hide.scss.
   *
   * @type {String}
   */
  this.pointerFocusClass = 'pointer-focus-hide';

  /**
   * The ally.js focus source global service object.
   *
   * This initializes ally.js' focus source service, which starts watching the
   * document and applies the data-focus-source attribute to the <html> element.
   *
   * Note that this updates only after a focus event, so we can't get an
   * accurate result in the focus handler but have to rely on the data attribute
   * in pointer_focus_hide.scss.
   *
   * @type {Object}
   *
   * @see https://allyjs.io/api/style/focus-source.html
   *   ally.js documentation.
   */
  var focusSourceHandle = ally.style.focusSource();

  /**
   * Lock the focus source detection to the current one.
   *
   * This tells ally.js to pause detection and leave the current source as the
   * active one. To unlock, use the .unlock() method of this component.
   *
   * @see this.unlock()
   *   Call this to unlock the focus source detection.
   *
   * @see https://github.com/medialize/ally.js/issues/150#issuecomment-244898298
   *   You may have to use setTimeout() to get an accurate focus source if
   *   calling this from within a click event handler.
   *
   * @see https://allyjs.io/api/style/focus-source.html
   *   ally.js documentation.
   */
  this.lock = function() {
    focusSourceHandle.lock(focusSourceHandle.current());
  };

  /**
   * Unlock the focus source detection.
   *
   * This tells ally.js to resume detection.
   *
   * @see this.lock()
   *   Call this to lock the focus source detection.
   *
   * @see https://github.com/medialize/ally.js/issues/150#issuecomment-244898298
   *   You may have to use setTimeout() to get an accurate focus source if
   *   calling this from within a click event handler.
   *
   * @see https://allyjs.io/api/style/focus-source.html
   *   ally.js documentation.
   */
  this.unlock = function() {
    focusSourceHandle.unlock();
  };

  // We bind globally instead of using a behaviour since there isn't really any
  // significant benefit to binding to specific containers and doing so would
  // add more complexity.
  $('body').on('focus', this.elements.join(), function(event) {
    if (
      // If the data attribute has been set to true, always apply the
      // class.
      $(this).data('pointer-focus-hide') === true ||
      // If no data attribute, make sure this isn't a link that is set to
      // open in a new tab/window. Unless overridden by the data
      // attribute, these always show the focus outline to make it easier
      // for a user who clicked a link to see where they left off.
      $(this).attr('target') != '_blank'
    ) {
      $(this).addClass(aiPointerFocusHide.pointerFocusClass);
    }
  })
  .on('blur', this.elements.join(), function(event) {
    $(this).removeClass(aiPointerFocusHide.pointerFocusClass)
  });
});
});
