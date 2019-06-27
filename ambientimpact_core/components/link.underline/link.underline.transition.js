// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Link: underline transition component
// -----------------------------------------------------------------------------

// Fancy link underlines that clear descenders are achieved using a linear
// gradient as a background image on the <a> elements. Unfortunately, most (no?)
// browsers currently support transitions on background images. This works
// around that by animating a CSS custom property (in browsers that support
// them), which the background linear gradient uses. So while we can't
// transition the gradient directly, we can transition the property it uses.

AmbientImpact.on('link.underline', function(aiLinkUnderline) {

AmbientImpact.onGlobals([
  'ally.get.activeElement',
  // Only register the component when TweenLite is available. 'link.underline'
  // only registers its component if CSS custom properties are supported, so we
  // don't need to check for support - we simply won't get this far.
  'TweenLite.to',
], function() {

AmbientImpact.addComponent('link.underline.transition', function(
  aiunderlineTransition, $
) {
  'use strict';

  // This is applied to the underlines container to tell the CSS to use the
  // custom property for the underline colour that's updated via JavaScript.
  this.containerClass =
    aiLinkUnderline.containerClass + '--underline-transitions';

  var
    // This is the custom property that we programmatically fade out, as it's
    // used in the CSS for the underline.
    linkUnderlineCurrentColourProperty = '--link-underline-colour',

    // This is the selector to match links to, for the events.
    linkEventSelector = 'a[href]:not(' +
      aiLinkUnderline.excludeLinkClass +
    ')',

    // The name of the data key to save the current link's TweenLite instance
    // to.
    underlineTweenDataName = 'underlineTween',

    // This is the object passed to jQuery to bind and unbind events. It's
    // filled later with the handlers.
    linkEvents = {};

  // This returns the custom properties we need to fade the underlines for a
  // given link element. These are set in the CSS, in @mixin
  // fancy-link-underlines.
  function getLinkProperties(link) {
    var properties = {
      'link-underline-normal-colour': '',
      'link-underline-hover-colour':  '',
      'fancy-link-transition-in-duration':  '',
      'fancy-link-transition-out-duration': ''
    };

    // Read the custom properties set in CSS to get the colours for the
    // underline.
    for (var propertyName in properties) {
      if (properties.hasOwnProperty(propertyName)) {
        properties[propertyName] =
          getComputedStyle($(link)[0]).getPropertyValue(
            '--' + propertyName
          );
      }
    }

    return properties;
  };

  function linkEnterHandler(event) {
    var $element    = $(event.target).closest(linkEventSelector),
      element     = $element[0],
      tween     = $(element).data(underlineTweenDataName);

    // If the tween is defined, kill any currently running fade.
    if (tween !== undefined && tween.kill) {
      tween.kill();
    }

    // Remove the current colour property so that the active state colour can be
    // applied via CSS.
    element.style.removeProperty(linkUnderlineCurrentColourProperty);
  };

  function linkLeaveHandler(event) {
    var $element    = $(event.target).closest(linkEventSelector),
      element     = $element[0],
      properties    = getLinkProperties(element),
      tween     = $(element).data(underlineTweenDataName),
      tweenSettings = {overwrite: true};

    // If this element is still the currently focused element, don't fade out.
    // This can happen if the user clicks and drags the link, in which case
    // 'mouseleave' will fire but the element is still focused and will fire
    // 'blur'. This is to prevent the underline from fading from the active
    // state until it actually should.
    if (ally.get.activeElement() === element) {
      return;
    }

    // Set the current underline property to the hover colour.
    element.style.setProperty(
      linkUnderlineCurrentColourProperty,
      properties['link-underline-hover-colour']
    );

    // Tell the tween to transition to the normal underline colour.
    tweenSettings[linkUnderlineCurrentColourProperty] =
      properties['link-underline-normal-colour'];

    // This doesn't work, but the enter handler clears this anyways, so leave
    // this hear for now in case GSAP starts removing it as it should.
    tweenSettings.clearProps = linkUnderlineCurrentColourProperty;

    // Run the tween.
    $element.data(underlineTweenDataName, TweenLite.to(
      element,
      // This assumes the duration is in seconds, not milliseconds.
      parseFloat(properties['fancy-link-transition-out-duration']),
      tweenSettings
    ));
  };

  // Define the events.
  linkEvents      = {
    // Enter/leave menu via mouse.
    mouseenter: linkEnterHandler,
    mouseleave: linkLeaveHandler,
    // Enter/leave links via keyboard.
    focus:    linkEnterHandler,
    blur:   linkLeaveHandler
  };

  // Add container class to container to indicate we're going to attempt to
  // transition underlines.
  $(aiLinkUnderline.container).addClass(this.containerClass);

  this.addBehaviors({
    AmbientImpactLinkUnderlineTransition: {
      attach: function(context, settings) {
        $(context)
          // Bind event handlers. Since we're using named event handlers, we
          // probably don't need to use $().once()?
          .on(linkEvents, linkEventSelector);
      },
      detach: function(context, settings, trigger) {
        // Only unbind from links on unload.
        if (trigger !== 'unload') {
          return;
        }

        $(context)
          // Unbind event handlers.
          .off(linkEvents, linkEventSelector);
      }
    }
  });
});
});
});
