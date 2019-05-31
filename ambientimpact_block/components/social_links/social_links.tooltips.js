// -----------------------------------------------------------------------------
//   Ambient.Impact - Blocks - Social links tooltips
// -----------------------------------------------------------------------------

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
