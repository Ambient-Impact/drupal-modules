// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Pointer focus hide component
// -----------------------------------------------------------------------------

// This hides the focus ring (outline) if a pointer was used to focus the
// element, but maintains focus and will show the outline if a user uses
// keyboard navigation.

// Note that this will always show the focus outline when the document loses and
// regains focus. While we could use the Page Visibility API to try and lock and
// unlock the focus source on hidden and visible states, this is not done for
// two reasons:
// - The Page Visibility API doesn't guarantee us that the element will still be
//   focused on triggering the visibilitychange event with the hidden state, and
//   this seems to be inconsistent even within the same browser, depending on
//   how the document lost focus, i.e. window was blurred or the tab was
//   switched away from/to.
//
// - There's arguably a UX benefit to showing the focus outlines on coming back
//   to the document after it was blurred, as it can remind users what they
//   interacted with last so that they can resume where they left off. Making
//   this more granular or specific to certain elements may be explored at a
//   later time.
//
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
  var elements = [
    'a', ':button', ':submit', ':reset', ':radio', ':checkbox',
    '[role="button"]', '[tabindex][tabindex!="-1"]',
  ];

  /**
   * The element data attribute/jQuery().data() name that hides pointer focus.
   *
   * If this is found and set to true, pointer focus hiding will be forced on an
   * element.
   *
   * @type {String}
   */
  var dataName = 'pointer-focus-hide';

  /**
   * The class to apply to elements when a pointer was used to focus them.
   *
   * This hides the focus outline in pointer_focus_hide.scss.
   *
   * @type {String}
   */
  var pointerFocusClass = 'pointer-focus-hide';

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
   * Whether the focus source is currently locked.
   *
   * @type {Boolean}
   *
   * @see this.lock()
   *   Set to true here.
   *
   * @see this.unlock()
   *   Set to false here.
   */
  var focusLocked = false;

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

    focusLocked = true;
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

    focusLocked = false;
  };

  /**
   * Determine if the focus source is currently locked.
   *
   * @return {Boolean}
   *   The current value of focusLocked.
   */
  this.isLocked = function() {
    return focusLocked;
  };

  /**
   * Hide the pointer focus on a given element.
   *
   * @param {HTMLElement|jQuery} element
   *   An HTML element or a jQuery collection containing one.
   */
  this.hide = function(element) {
    $(element).data(dataName, true);
  };

  /**
   * Show the pointer focus on a given element.
   *
   * @param {HTMLElement|jQuery} element
   *   An HTML element or a jQuery collection containing one.
   */
  this.show = function(element) {
    $(element).data(dataName, false);
  };

  // We bind globally instead of using a behaviour since there isn't really any
  // significant benefit to binding to specific containers and doing so would
  // add more complexity.
  $('body').on('focus', elements.join(), function(event) {
    var $this = $(this);

    if (
      // If the data attribute/jQuery data has been set to true, always apply
      // the class.
      $this.data(dataName) === true  ||
      // If the data attribute/jQuery data is not defined, apply the class.
      typeof $this.data(dataName) === 'undefined'
    ) {
      $this.addClass(pointerFocusClass);
    }
  })
  .on('blur', elements.join(), function(event) {
    $(this).removeClass(pointerFocusClass)
  });
});
});
