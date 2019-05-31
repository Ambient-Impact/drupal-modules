// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Abbreviation (abbr) component
// -----------------------------------------------------------------------------

// This creates Tippy.js tooltips for abbr elements, which improve mobile
// accessibility by appearing on touch in addition to looking pretty.

AmbientImpact.on(['tooltip'], function(aiTooltip) {
AmbientImpact.addComponent('abbr', function(aiAbbr, $) {
  'use strict';

  this.addBehaviour(
    'AmbientImpactAbbr',
    'ambientimpact-abbr',
    '.layout-container',
    function(context, settings) {
      aiTooltip.create(this, {
        tippy: {
          target: 'abbr[title]'
        }
      });
    },
    function(context, settings, trigger) {
      aiTooltip.destroy(this);
    }
  );
});
});
