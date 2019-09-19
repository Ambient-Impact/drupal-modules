// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Link: file component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('link.file', function(aiLinkFile, $) {
  'use strict';

  // Preset list of extensions that are considered a file link, i.e. not a
  // page on the site but something that is downloadable and viewed
  // separately.
  this.extensions = [
    'txt', 'pdf', 'doc', 'docx', 'csv', 'xsl', 'xslx',
    'zip', 'tar', 'gz', 'exe',
    'avi', 'mp4', 'mp3', 'ai', 'eps',
  ];

  // This class is added if a link has been detected as leading to a file.
  this.linkIsFileClass  = 'ambientimpact-is-file-link';


  // Get the extension from a path, see:
  // http://stackoverflow.com/a/12900504/6513652
  this.getExtension = function(path) {
    // Extract file name from full path (supports `\\` and `/` separators).
    var basename = path.split(/[\\/]/).pop(),

    // Get last position of '.'.
      pos = basename.lastIndexOf(".");

    // If file name is empty or '.'' not found (-1) or comes first (0).
    if (basename === '' || pos < 1) {
      return '';
    }

    // Extract extension, ignoring '.'.
    return basename.slice(pos + 1);
  };


  // Returns true if the link points to a file, false otherwise.
  this.isFileDestinationLink = function(link, options) {
    var settings    = {},
      defaults    = {
        extensions: this.extensions
      },
      linkHref    = $(link).attr('href'),
      linkExtension = '',
      linkIsFile    = false;

    if (!linkHref) {
      return false;
    }

    if (typeof options === 'object') {
      settings = $.extend({}, defaults, options);
    } else {
      settings = defaults;
    }

    linkHref    = linkHref.toLowerCase();
    linkExtension = this.getExtension(linkHref);

    return settings.extensions.indexOf(linkExtension) > -1;
  };


  this.addBehaviors({
    AmbientImpactFileLink: {
      attach: function (context, settings) {
        var $links = $('a', context);

        for (var i = $links.length - 1; i >= 0; i--) {
          if (aiLinkFile.isFileDestinationLink($links[i])) {
            // Add the class for CSS styling, etc.
            $($links[i]).addClass(aiLinkFile.linkIsFileClass);
          }
        }
      },
      detach: function (context, settings, trigger) {
        if (trigger !== 'unload') {
          return;
        }

        // Remove the class from any links that have it.
        $('a.' + aiLinkFile.linkIsFileClass)
          .removeClass(aiLinkFile.linkIsFileClass);
      }
    }
  });

  // Make links that point to a recognized file extension external, opening in
  // a new tab and disabling Ajax page loading.
  AmbientImpact.on('link.external', function(aiLinkExternal) {
    aiLinkFile.addBehaviors({
      AmbientImpactFileLinkExternal: {
        attach: function (context, settings) {
          var $links = $('a', context);

          for (var i = $links.length - 1; i >= 0; i--) {
            if (aiLinkFile.isFileDestinationLink($links[i])) {
              // Make external.
              aiLinkExternal.makeLinkExternal($links[i]);
            }
          }
        }
        // External links component runs a detach on all available links
        // on the 'unload' trigger, so we don't have to do that
        // ourselves here.
      }
    });
  });
});
