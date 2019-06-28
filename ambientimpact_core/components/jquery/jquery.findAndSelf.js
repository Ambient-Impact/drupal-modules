// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - jQuery().findAndSelf() plug-in component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent(
  'jquery.findAndSelf',
function(aijQueryFindAndSelf, $) {
  'use strict';

  $.fn.extend({
    /**
     * Find find descendents matching a selector, including the element itself.
     *
     * Usage: $('.parent').findAndSelf('.selector');
     *
     * @param {String} selector
     *   The selector to filter descendents and the element itself by.
     *
     * @return {jQuery}
     *   The found/filtered elements.
     *
     * @see https://stackoverflow.com/a/13094449
     *   The performance of this method versus adding all and then .filter().
     */
    findAndSelf: function(selector) {
      if (!selector) {
        return this;
      }

      var $found = $();

      // If the element itself matches the selector, add it as the first element
      // in the collection.
      if (this.is(selector)) {
        $found = $found.add(this);
      }

      // Now find any descendents that match the selector.
      $found = $found.add(this.find(selector));

      return $found;
    }
  });
});
