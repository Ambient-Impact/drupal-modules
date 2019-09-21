// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Pointer type component
// -----------------------------------------------------------------------------

// When attached, this component listens to pointer-related events to determine
// the type of pointer of the current interaction. This is more user-friendly
// than attempting to determine one pointer type on page load because it allows
// dynamically adapting if a device has more than one pointer type; e.g. laptops
// that support both mouse and touch.
//
// If pointer events are supported, PointerEvent.pointerType is used to get the
// pointer type. If pointer events are not supported, uses a combination of
// touch and mouse events to infer if the pointer type is touch or a mouse.
//
// @see https://allyjs.io/api/is/focusable.html
//   Uses ally.is.focusable() to test if elements are focusable.
//
// @see https://ambientimpact.com/web/snippets/touch-and-mouse-together-again-for-the-first-time
//   If touch and mouse events are used, they're always triggered in that order
//   by the browser, so we can use that to infer if touch was used.
//
// @see https://developer.mozilla.org/en-US/docs/Web/API/Pointer_events
//
// @see https://developer.mozilla.org/en-US/docs/Web/API/PointerEvent/pointerType
//
// @see https://developer.mozilla.org/en-US/docs/Web/CSS/@media/hover
//   This media feature was used previously to attempt to only apply styles when
//   the primary pointer had convenient hover functionality, which the MDN
//   documentation says is not touch, yet both Firefox and Chrome on Android
//   match this media query regardless, so it's not useful to us as it currently
//   is implemented.

AmbientImpact.onGlobals('ally.is.focusable', function() {
AmbientImpact.addComponent('pointerType', function(aiPointerType, $) {
  'use strict';

  /**
   * The data attribute name to attach to focusable elements.
   *
   * @type {String}
   */
  var dataAttributeName = 'data-pointer-used';

  /**
   * The class applied to container elements we attach to.
   *
   * This is used to limit the focusable element element up the DOM tree to the
   * attached container.
   *
   * @type {String}
   */
  var containerClass = 'pointer-used-container';

  /**
   * Get the first focusable element up the tree given a target element.
   *
   * @param {HTMLElement} element
   *   The target element to start searching from.
   *
   * @return {HTMLElement|Boolean}
   *   Either returns a focusable HTML element if one can be found, or false if
   *   none is found.
   */
  function getFocusableTarget(element) {
    // If the target element is focusable, return it.
    if (ally.is.focusable(element)) {
      return element;
    }

    // Get all parents of the target element until the attached container.
    var parents = $(element).parentsUntil('.' + containerClass);

    // Loop through the found parent elements, returning the first one that is
    // focusable.
    for (var i = 0; i < parents.length; i++) {
      if (ally.is.focusable(parents[i])) {
        return parents[i];
      }
    }

    // If we haven't found anything, return false.
    return false;
  };

  /**
   * Event handler for pointer events.
   *
   * @param {jQuery.Event} event
   *
   * @see getFocusableTarget()
   *   Finds the first focusable element; either the element that triggered this
   *   event or an ancestor element, if one can be found.
   *
   * @see @see https://developer.mozilla.org/en-US/docs/Web/API/PointerEvent/pointerType
   *   PointerEvent.pointerType is used to report the pointer type that was used
   *   in this event.
   */
  function pointerEventHandler(event) {
    var focusableTarget = getFocusableTarget(event.target);

    if (focusableTarget === false) {
      return;
    }

    $(focusableTarget).attr(dataAttributeName, event.originalEvent.pointerType);
  };

  /**
   * Event handler for touch events.
   *
   * This is always triggered before mouse events, so we set a property on the
   * focusable target, if one is found, to inform the subsequent mouse event
   * that touch was used.
   *
   * @param {jQuery.Event} event
   *
   * @see getFocusableTarget()
   *   Finds the first focusable element; either the element that triggered this
   *   event or an ancestor element, if one can be found.
   */
  function touchEventHandler(event) {
    var focusableTarget = getFocusableTarget(event.target);

    if (focusableTarget === false) {
      return;
    }

    focusableTarget.aiPointerTypeTouchUsed = true;
  };

  /**
   * Event handler for mouse events.
   *
   * Checks for the presence of a property on a focusable target, if one is
   * found, and infers whether this was a touch or mouse event based on whether
   * the property exists or doesn't, respectively. Mouse events are always
   * triggered after touch events by browsers, so we use this to our advantage.
   *
   * @param {jQuery.Event} event
   *
   * @see getFocusableTarget()
   *   Finds the first focusable element; either the element that triggered this
   *   event or an ancestor element, if one can be found.
   */
  function mouseEventHandler(event) {
    var focusableTarget = getFocusableTarget(event.target);

    if (focusableTarget === false) {
      return;
    }

    if ('aiPointerTypeTouchUsed' in focusableTarget) {
      $(focusableTarget).attr(dataAttributeName, 'touch');

      delete focusableTarget.aiPointerTypeTouchUsed;
    } else {
      $(focusableTarget).attr(dataAttributeName, 'mouse');
    }
  };

  /**
   * Attach to a provided container element.
   *
   * @param {jQuery|HTMLElement} element
   *   An HTML element or a jQuery collection containing at least one element.
   */
  this.attach = function(element) {
    // Make sure this is a jQuery collection by wrapping it in one. If element
    // is already a jQuery collection, jQuery just returns it as-is.
    var $element = $(element);

    $element.addClass(containerClass);

    if ('PointerEvent' in window) {
      $element.on('pointerover.aiPointerType', pointerEventHandler);
    } else {
      $element.on({
        'touchstart.aiPointerType': touchEventHandler,
        'mouseover.aiPointerType':  mouseEventHandler
      });
    }
  };

  /**
   * Detach from a provided container element.
   *
   * @param {jQuery|HTMLElement} element
   *   An HTML element or a jQuery collection containing at least one element.
   */
  this.detach = function(element) {
    // Make sure this is a jQuery collection by wrapping it in one. If element
    // is already a jQuery collection, jQuery just returns it as-is.
    var $element = $(element);

    $element
      .removeClass(containerClass)
      .off({
        'pointerover.aiPointerType':  pointerEventHandler,
        'touchstart.aiPointerType':   touchEventHandler,
        'mouseover.aiPointerType':    mouseEventHandler
      })
      .find('[' + dataAttributeName + ']')
        .removeAttr(dataAttributeName);
  };
});
});
