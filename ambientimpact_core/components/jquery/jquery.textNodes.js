// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - jQuery().textNodes() plugin component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('jquery.textNodes', function(aijQueryTextNodes, $) {
  'use strict';

  $.fn.extend({
    // Filter text nodes in a jQuery collection.
    // Usage: $('.parent').contents().textNodes();
    textNodes: function() {
      return this.filter(function() {
        // Return only text nodes that aren't empty.
        return this.nodeType === 3 && $.trim($(this).text()) != '';
      });
    }
  });
});
