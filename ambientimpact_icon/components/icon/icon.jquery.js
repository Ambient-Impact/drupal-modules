// -----------------------------------------------------------------------------
//   Ambient.Impact - Icons - Icon jQuery component
// -----------------------------------------------------------------------------

AmbientImpact.on(['icon.get', 'jquery'], function(aiIconGet, aijQuery) {
AmbientImpact.addComponent('icon.jquery', function(aiIconjQuery, $) {
  'use strict';

  $.fn.extend({
    /**
     * Wrap one or more elements' text content in icons.
     *
     * @param {String} iconName
     *   The name of the icon to use.
     *
     * @param {Object} settings
     *   Additional settings to pass to aiIconGet.get()
     *
     * @return {jQuery}
     *   The jQuery collection, for chaining.
     */
    wrapTextWithIcon: function(iconName, settings) {
      return this.each(function() {
        var $this = $(this);

        $this.append(aiIconGet.get(
          iconName,
          $.extend(true, {}, settings, {
            // Grab the existing text after removing it from the DOM.
            text: $this.contents().textNodes().remove().text()
          })
        ));
      });
    },

    /**
     * Unwrap one or more elements' text content from an icon, if found.
     *
     * @return {jQuery}
     *   The jQuery collection, for chaining.
     *
     * @see jQuery.fn.wrapTextWithIcon()
     */
    unwrapTextWithIcon: function() {
      return this.each(function() {
        var $this = $(this);
        var $icon;
        var containerBaseClass = aiIconGet.settings.containerBaseClass;

        if ($this.is('.' + containerBaseClass)) {
          $icon = $this;
        } else {
          $icon = $this.find('.' + containerBaseClass);
        }

        if ($icon.length < 1) {
          return;
        }

        $icon.find('.' + containerBaseClass + '__text').contents()
          .insertAfter($icon);

        $icon.remove();
      });
    }
  });
});
});
