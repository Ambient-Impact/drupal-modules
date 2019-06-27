// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Link: external component
// -----------------------------------------------------------------------------

// This forces links outside of this domain to open in a new tab, and disables
// Ajax page loading on said links if/when the Ajax page component is available.

AmbientImpact.addComponent('link.external', function(aiLinkExternal, $) {
  'use strict';

  // This class is applied to links that are marked external.
  this.externalLinkClass  = 'external-link';

  // This is the name of the data attached to individual links, to track if
  // they are external, internal, or to determine that we haven't processed
  // the link yet.
  this.linkDataName   = 'externalLinkStatus';

  // This is the name of the data attached to individual links to track the
  // target attribute that was present before we attached to it, to restore it
  // if we detach from the link.
  this.linkTargetDataName = 'externalLinkOriginalTarget';

  // jQuery plugins wrapping our methods.
  $.fn.extend({
    forceExternalLink: function() {
      aiLinkExternal.makeLinkExternal(this);

      return this;
    },
    undoForceExternalLink: function() {
      aiLinkExternal.detachFromLink(this);

      return this;
    }
  });


  // Make a link external, i.e. open in a new tab.
  this.makeLinkExternal = function(link) {
    var $link = $(link);

    // Guard against window.opener vulnerability:
    // https://dev.to/ben/the-targetblank-vulnerability-by-example
    if (
      $link.attr('rel') === undefined ||
      $.trim($link.attr('rel')) === ''
    ) {
      $link.attr('rel', 'noopener noreferrer');
    } else {
      $link.attr('rel', $.trim($link.attr('rel')) + ' noopener noreferrer');
    }

    // Save the original target attribute for if/when we detach.
    this.processLinkTarget($link);

    $link.attr('target', '_blank');

    // Save status to element data
    $link.data(this.linkDataName, true);

    // Add the class.
    $link.addClass(this.externalLinkClass);

    // Disable Ajax page loading on external links.
    AmbientImpact.on('ajaxPages', function(aiAjaxPages) {
      $link.disableAjaxLink();
    });
  };

  // Make a link internal, i.e. open in the same tab.
  this.makeLinkInternal = function(link) {
    var $link = $(link);

    // Detach to remove any traces of an external link, if any.
    this.detachFromLink(link);

    // Save the original target attribute just in case we need to change it.
    this.processLinkTarget($link);

    // Save status to element data.
    $link.data(this.linkDataName, false);
  };

  // Remove all traces of our work.
  this.detachFromLink = function(link) {
    var $link = $(link);

    // Split the rel attribute into an array.
    if ($link.attr('rel') !== undefined) {
      var relArray = $link.attr('rel').split(/\s+/);
      for (var i = relArray.length - 1; i >= 0; i--) {
        if (
          relArray[i] === 'noopener' ||
          relArray[i] === 'noreferrer'
        ) {
          // Remove both the 'noopener' and 'noreferrer' items.
          relArray.splice(i, 1);
        }
      }
      if (relArray.length > 0) {
        // If there are still other items, join back into a string.
        $link.attr('rel', relArray.join(' '));
      } else {
        // Otherwise, just remove the attribute.
        $link.removeAttr('rel');
      }
    }

    // Restore the original 'target' attribute, if any.
    this.restoreLinkTarget($link);

    // Remove element data.
    $link.removeData(this.linkDataName);
    $link.removeData(this.linkTargetDataName);

    // Remove the class.
    $link.removeClass(this.externalLinkClass);

    // If/when the Ajax pages component is available, undo disabling Ajax
    // page loading on this link.
    AmbientImpact.on('ajaxPages', function(aiAjaxPages) {
      $link.undisableAjaxLink();
    });
  };

  // Process a link's target attribute, saving it so we know what to restore
  // if need be.
  this.processLinkTarget = function(link) {
    var $link = $(link);

    if ($link.attr('target') === undefined) {
      // If not found, we use false to indicate this, rather than
      // undefined.
      $link.data(this.linkTargetDataName, false);
    } else if ($link.attr('target') !== false) {
      // If the target is a non-false value, save it.
      $link.data(this.linkTargetDataName, $link.attr('target'));
    }
  };

  // Restore a link's target attribute to what it was before we processed it.
  this.restoreLinkTarget = function(link) {
    var $link = $(link);

    if ($link.data(this.linkTargetDataName) === false) {
      // If there was no target attribute, remove it.
      $link.removeAttr('target');
    } else if ($link.data(this.linkTargetDataName) !== undefined) {
      // If there was one saved, restore it.
      $link.attr('target', $link.data(this.linkTargetDataName));
    }
  };

  // Process a link, determining if it is internal or external.
  this.processLink = function(link) {
    var $link = $(link);

    // Skip this link if we find the data already set.
    if ($link.data(this.linkDataName) !== undefined) {
      return;
    }

    if (
      // If the link has an href attribute...
      $link.attr('href') !== undefined &&
      // ...and if the host of the link doesn't match the
      // window.location...
      $link[0].host.indexOf(window.location.host) === -1 &&
      // ...and this isn't one of those *shudder* JavaScript
      // pseudo protocol links...
      !$link.is('a[href^=javascript:]')
    ) {
      // Make external.
      this.makeLinkExternal($link);
    } else {
      // Make internal. We do this so that we can track that
      // we've processed the link.
      this.makeLinkInternal($link);
    }
  };


  // Drupal behaviour.
  this.addBehaviors({
    AmbientImpactLinkExternal: {
      attach: function (context, settings) {
        var $links = $('a', context);

        for (var i = $links.length - 1; i >= 0; i--) {
          aiLinkExternal.processLink($links[i]);
        }
      },
      detach: function (context, settings, trigger) {
        if (trigger !== 'unload') {
          return;
        }

        var $links = $('a', context);

        // Detach from all links.
        for (var i = $links.length - 1; i >= 0; i--) {
          aiLinkExternal.detachFromLink($links[i]);
        }
      }
  }});
});
