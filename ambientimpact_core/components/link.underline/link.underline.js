// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Link: underline component
// -----------------------------------------------------------------------------

// Currently this component is used to mark links that are part of a text
// selection with a class for browsers that support styling the text selection
// but only when the document has focus. At the time of writing (November 2017)
// this is mainly Firefox, but possibly other browsers too. In addition, this
// component functions as an entry point for loading the child components if
// custom properties are supported.

// @see https://developer.mozilla.org/en-US/docs/Web/API/Selection
// @see https://developer.mozilla.org/en-US/docs/Web/API/Range

// @todo Wrap selected text within links so that we only remove underline crop
// on the part of the link that's selected?

// @todo Should this be moved to the typography underline component?

AmbientImpact.onGlobals(['Modernizr.customproperties'], function() {

// Don't register the component if the browser doesn't support CSS custom
// properties. These are used by the CSS to inherit the background colour for
// the text-shadow that crops the underlines.
if (Modernizr.customproperties !== true) {
  return;
}

AmbientImpact.addComponent('link.underline', function(aiLinkUnderline, $) {
  'use strict';

  // Cut the mustard. We still register the component even if these aren't
  // available, so that the child components still load if custom properties are
  // supported.
  if (
    !window.getSelection ||
    !document.querySelectorAll ||
    !document.body.classList
  ) {
    return;
  }

  // This is added to links when the window is not focused so that we can remove
  // underlines if they are part of the selection. This is done because the
  // inactive selection pseudoclasses are not well supported yet.
  this.windowBlurClass = 'ambientimpact-link-underline-window-blur';

  // Set a class on all links that are part of the text selection.
  function setSelectedLinks() {
    var selection   = window.getSelection();

    if (selection.rangeCount === 0) {
      return;
    }

    var range     = selection.getRangeAt(0),
      container   = range.commonAncestorContainer;

    if (!container.querySelectorAll) {
      return;
    }

    var links     = container.querySelectorAll('a');

    for (var i = links.length - 1; i >= 0; i--) {
      if (selection.containsNode(links[i], true)) {
        links[i].classList.add(aiLinkUnderline.windowBlurClass);
      }
    }
  }
  // Remove the selected class from any links that have it.
  function removeSelectedLinks() {
    var links = document.querySelectorAll(
      '.' + aiLinkUnderline.windowBlurClass
    );

    for (var i = links.length - 1; i >= 0; i--) {
      links[i].classList.remove(aiLinkUnderline.windowBlurClass);
    }
  }

  function windowBlur() {
    setSelectedLinks();
  };
  function windowFocus() {
    removeSelectedLinks();
  };

  if (document.hasFocus()) {
    windowFocus();
  } else {
    windowBlur();
  }

  $(window).on({
    'blur.aiLinkUnderline':   windowBlur,
    'focus.aiLinkUnderline':  windowFocus
  });
});
});
