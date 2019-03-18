// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Immerse component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('immerse', function(aiImmerse, $) {
  'use strict';

  var bodyClass = 'is-immersed';

  // Add and remove the <body> class on immerse events.
  $(document).on('immerseEnter.aiImmerse', function(event, element) {
    $('body').addClass(bodyClass);

  }).on('immerseExit.aiImmerse', function(event, element) {
    $('body').removeClass(bodyClass);
  });
});
