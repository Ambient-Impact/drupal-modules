/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Material Design ripple component
----------------------------------------------------------------------------- */

// This is adapted from the JavaScript code by Jessie Proffitt:
// @see https://codepen.io/jproffitt71/pen/PBPKmw

// @todo Uncomment, test, and enable the ability to have elements trigger a
// ripple on other elements, e.g. a <label> triggering a ripple on a checkbox/
// radio button.

// @todo Get keyboard activate events working, e.g. hitting Enter on a button.

// @todo Enable/fix ability to reset the animation if a pointer event occurs
// while the animation is in progress.

AmbientImpact.onGlobals(['Modernizr.customproperties'], function() {

// Requires CSS custom properties support.
if (Modernizr.customproperties !== true) {
  return;
}

AmbientImpact.addComponent('material.ripple', function(
  aiMaterialRipple, $
) {
  'use strict';

  // The names of the events that trigger a ripple.
  var triggerEventNames = [
    'mousedown.aiMaterialRipple',
    'touchstart.aiMaterialRipple',
    'touchend.aiMaterialRipple',
    // 'keyup.aiMaterialRipple',
  ],

  // The names of the animation events to bind to to remove our temporary custom
  // properties and swap out classes.
  animationEventNames = [
    'animationend.aiMaterialRipple',
    'animationcancel.aiMaterialRipple',
  ],

  // The base class to add to the element that has a Material ripple and also
  // the class to derive BEM modifier classes from.
  baseClass           = 'material-ripple',

  // The class added to elements to reset their ripple animation to the starting
  // frame. This equates to the 'pre-click-fx' class from the original Pen.
  resetClass          = baseClass + '--ripple-reset',

  // The class added to elements to animate the ripple to completion even if
  // it's no longer the active element. This equates to the 'click-fx' from the
  // original Pen.
  activeClass         = baseClass + '--ripple-active',

  // The class added to inputs once they've been focused, to avoid triggering
  // the ripple more than once. This equates to the 'post-click-fx' class from
  // the original Pen.
  inputActiveClass    = baseClass + '--input-active',

  // The class added to elements to disable the ripple, if needed.
  noRippleClass       = baseClass + '--no-ripple',

  // The class added to elements to disable their default active state styles.
  noActiveStateClass  = 'disable-default-active-state';

  /**
   * Find an element with a Material ripple given an event target.
   *
   * This looks at the event target and each ancestor up the tree until it finds
   * the an element that has the '--material-ripple-duration' custom property.
   *
   * @param {jQuery} eventTarget
   *   A jQuery collection containing the event target element from an
   *   interaction event.
   *
   * @return {jQuery}
   *   A jQuery collection containing the first element found that has the
   *   '--material-ripple-duration' custom property, starting with the passed
   *   event target and going up the tree. If no element can be found, an empty
   *   collection is returned.
   */
  function findRippleElement($eventTarget) {
    var $potentialTargets = $eventTarget.add($eventTarget.parents());

    for (var i = 0; i < $potentialTargets.length; i++) {
      if (getComputedStyle($potentialTargets[i]).getPropertyValue(
        '--material-ripple-duration'
      )) {
        return $potentialTargets.eq(i);
      }
    }

    // If we can't find an element, return an empty collection.
    return $();
  }

  /**
   * Determine if the passed element or descendents have a CSS animation.
   *
   * This checks for the presence of the 'animation-duration' CSS property and
   * whether it has a value that's greater than 0.
   *
   * @param {jQuery} element
   *   A jQuery collection containing the element to look at.
   *
   * @return {Boolean}
   *   True if the element or any descendents have the 'animation-duration' CSS
   *   property with a value greater than 0, false otherwise.
   */
  function hasAnimation($element) {
    var $elements = $element.add($element.find('*'));

    for (var i = $elements.length - 1; i >= 0; i--) {
      if (parseFloat(
        getComputedStyle($elements[i], null)
          .getPropertyValue('animation-duration') || '0'
      ) > 0) {
        return true;
      }
    }

    return false;
  }

  /**
   * Perform a ripple on the passed element, with optional coordinates.
   *
   * @param {jQuery} $element
   *   A jQuery collection containing the element to display a ripple on.
   *
   * @param {Object|Boolean} pointerCoordinates
   *   Either an object containing 'x' and 'y' keys containing coordinates of a
   *   pointer event relative to the document, or false to indicate this was not
   *   a pointer event.
   */
  function ripple($element, pointerCoordinates) {
    if (
      $element.hasClass(activeClass) ||
      $element.hasClass(resetClass) ||
      $element.hasClass(noRippleClass)
    ) {
      return;
    }

    var element = $element[0];

    if (pointerCoordinates) {
      // $element.offset() gives us wrong numbers here for some reason?
      var elOffset  = element.getBoundingClientRect(),
      clickOffset   = {
        x: Math.round(pointerCoordinates.x - elOffset.left),
        y: Math.round(pointerCoordinates.y - elOffset.top)
      },
      // outerWidth  = element.offsetWidth,
      // outerHeight = element.offsetHeight;
      outerWidth    = $element.outerWidth(),
      outerHeight   = $element.outerHeight();

      var properties = {
        '--material-ripple-pointer-offset-x': clickOffset.x + 'px',
        '--material-ripple-pointer-offset-y': clickOffset.y + 'px',
        '--material-ripple-max-radius':    Math.round(Math.sqrt(
          Math.pow(Math.max(clickOffset.x, outerWidth   - clickOffset.x), 2) +
          Math.pow(Math.max(clickOffset.y, outerHeight  - clickOffset.y), 2)
        )) + 'px',
        '--material-ripple-element-width':    outerWidth  + 'px',
        '--material-ripple-element-height':   outerHeight + 'px'
      };

      // Set the custom properties on the element.
      for (var property in properties) {
        element.style.setProperty(property, properties[property]);
      }
    }

    $element.addClass(resetClass);

    requestAnimationFrame(function() {
      // If its ignoring reset, exit early.
      if (hasAnimation($element)) {
        $element.removeClass(resetClass);

        return;
      }

      $element.addClass(activeClass).removeClass(resetClass);

      // Only attach the animation events once per element.
      if ('animationEndEvent' in element.aiMaterialRipple) {
        return;
      }

      $element.on(animationEventNames.join(' '), function(event) {
        $element.removeClass(activeClass);

        // Remove the temporary custom properties to keep the DOM more tidy.
        for (var property in properties) {
          element.style.removeProperty(property);
        }

        // // If this is a stateful element, it might have a stateful effect.
        // if (
        //   $element.is([
        //     // Using native selectors rather than jQuery's :button since
        //     // native are faster.
        //     'input:not([type=submit]):not([type=button]):not([type=reset])',
        //     'textarea',
        //     'select',
        //   ].join(', ')) &&
        //   element === document.activeElement
        // ) {
        //   $element.addClass(inputActiveClass);

        //   // Give other plugins a chance to do their magic to the element (e.g.
        //   // select2 hidden input) before checking if it is still focused.

        //   $('body').one([
        //     'mouseup.aiMaterialRipple',
        //     'touchend.aiMaterialRipple',
        //     'keyup.aiMaterialRipple',
        //   ].join(' '), function(event) {
        //     setTimeout(function() {
        //       // No longer focused.
        //       if (element !== document.activeElement) {
        //         $element.removeClass(inputActiveClass);
        //       }
        //     }, 0);
        //   });
        // }
      });

      // Indicate that this element now has animation events attached.
      element.aiMaterialRipple.animationEndEvent = true;
    });
  }

  this.addBehaviour(
    'AmbientImpactMaterialRipple',
    'ambientimpact-material-ripple',
    'body',
    function(context, settings) {
      $(this).on(triggerEventNames.join(' '), function(event) {
        var $element = findRippleElement($(event.target));

        // Don't do anything if no element with a ripple was found.
        if ($element.length === 0) {
          return;
        }

        var element = $element[0];

        // Add classes to make detaching simpler and to disable any existing
        // active state styles.
        if (!$element.hasClass(baseClass)) {
          $element
            .addClass(baseClass)
            .addClass(noActiveStateClass);
        }

        if (!('aiMaterialRipple' in element)) {
          element.aiMaterialRipple = {
            lastEvent: null
          };
        }

        var lastEvent = element.aiMaterialRipple.lastEvent,
        ignoreEvent   = false;

        if (
          lastEvent &&
          event.type === 'mousedown' &&
          lastEvent.type === 'touchend' &&
          event.target === lastEvent.target
        ) {
          // This is a mousedown fired automatically after a touchend on the
          // same target so it's ignored.
          ignoreEvent = true;

        // touchend events are always ignored and only bound to so that we can
        // detect if they were the previous event.
        } else if (event.type === 'touchend') {
          ignoreEvent = true;
        }

        // Save this event as the last event.
        element.aiMaterialRipple.lastEvent = event;

        if (ignoreEvent) {
          return;
        }

        var pointerCoordinates = false;

        // Only grab coordinates for pointer events.
        if (event.type !== 'focusin') {
          if (event.changedTouches && event.changedTouches[0]) {
            pointerCoordinates = {
              x: event.changedTouches[0].clientX,
              y: event.changedTouches[0].clientY
            };
          } else {
            pointerCoordinates = {
              x: event.clientX,
              y: event.clientY
            };
          }
        }

        ripple($element, pointerCoordinates);

        // // Check if this element should trigger effect on other elements.
        // if ('undefined' !== typeof el.dataset.applyClickFx) {
        //   document.querySelectorAll(el.dataset.applyClickFx).forEach(function(otherEl) {
        //     ripple(otherEl, clickCoords);
        //   });

        //   let parent = el.parent;

        //   while (parent) {
        //     if ('undefined' !== parent.dataset.applyClickFx) {
        //       document.querySelectorAll(parent.dataset.applyClickFx).forEach(function(otherEl) {
        //         ripple(otherEl, clickCoords);
        //       });
        //     }

        //     parent = parent.parent;
        //   }
        // }

        // // Check if other elements should be triggered by this element.
        // document.querySelectorAll('[data-subscribe-click-fx]').forEach(function(otherEl) {
        //   if (el.matches(otherEl.dataset.subscribeClickFx)) {
        //     ripple(otherEl, clickCoords);
        //   }
        // });
      });
    },
    function(context, settings, trigger) {
      var $body       = $(this),
      $rippleElements = $body.find('.' + baseClass);

      $body
        .off(triggerEventNames.join(' '));

      for (var i = $rippleElements.length - 1; i >= 0; i--) {
        delete $rippleElements[i].aiMaterialRipple;
      }

      $rippleElements
        .removeClass(baseClass)
        .removeClass(noActiveStateClass)
        .off(animationEventNames.join(' '));
    }
  );
});
});
