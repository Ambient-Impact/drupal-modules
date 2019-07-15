// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Smooth scroll component demo
// -----------------------------------------------------------------------------

AmbientImpact.on([
  'smoothScroll',
  'componentDemo',
], function(aiSmoothScroll, aiComponentDemo) {
AmbientImpact.addComponent('smoothScrollDemo', function(aiSmoothScrollDemo, $) {
  'use strict';

  var $container = aiComponentDemo.getDemoContentContainer();

  var baseClass = $container.children().first().data('base-class');

  if (typeof baseClass === 'undefined') {
    console.error(
      Drupal.t('Could not find baseClass.')
    );

    return;
  }

  var $adaptiveScrollDuration =
    $('.' + baseClass + '-adaptive-scroll-duration .form-checkbox');

  if ($adaptiveScrollDuration.length < 1) {
    console.error(
      Drupal.t('Could not find the adaptive scroll duration checkbox.')
    );

    return;
  }

  function setAdaptiveScrollDuration() {
    aiSmoothScroll.useAdaptiveScrollDuration =
      $adaptiveScrollDuration.prop('checked')
  };

  setAdaptiveScrollDuration();

  $adaptiveScrollDuration.on('change', setAdaptiveScrollDuration);

  $adaptiveScrollDuration.closest('.form-item').removeClass('invisible');
});
});
