// -----------------------------------------------------------------------------
//   Ambient.Impact - Blocks - Social links tooltips
// -----------------------------------------------------------------------------

// @todo Should this be removed as it's out of scope for this block? It would
// make more sense to have this handled on a theme or otherwise global level.

AmbientImpact.on('tooltip', function(aiTooltip) {
AmbientImpact.addComponent(
  'socialLinks.tooltips',
function(aiSocialLinksTooltips, $) {
  'use strict';

  this.addBehaviour(
    'AmbientImpactSocialLinksTooltips',
    'ambientimpact-social-links-tooltips',
    '.ambientimpact-social-links',
    function(context, settings) {
      aiTooltip.create(this, {
        tippy: {
          target: 'a[title]'
        }
      });
    },
    function(context, settings, trigger) {
      aiTooltip.destroy(this);
    }
  );
});
});
