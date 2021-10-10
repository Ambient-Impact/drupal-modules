// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - FastDom component
// -----------------------------------------------------------------------------

AmbientImpact.onGlobals(['fastdom', 'fastdomPromised'], function() {
AmbientImpact.addComponent('fastdom', function(aiFastDom, $) {

  'use strict';

  /**
   * Fastdom instance.
   *
   * @type {FastDom}
   */
  let instance;

  /**
   * Get the FastDom instance.
   *
   * @return {FastDom}
   *   The FastDom instance with the Promise extension.
   */
  this.getInstance = function() {

    if (typeof fastdomInstance === 'undefined') {
      instance = fastdom.extend(fastdomPromised);
    }

    return instance;

  };

});
});
