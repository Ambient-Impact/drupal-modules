/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Texarea component
----------------------------------------------------------------------------- */

// This attaches an autosizing behaviour to textareas.

AmbientImpact.onGlobals('autosize', function() {
AmbientImpact.addComponent('textarea', function(aiTextarea, $) {
  'use strict';

  var autosizeWrapperClass    = 'autosize-textarea-wrapper',
      containerClassAutosize  = 'form-item-textarea--autosize';

  this.addBehaviour(
    'AmbientImpactTextarea',
    'ambientimpact-textarea',
    '.form-textarea-wrapper',
    function(context, settings) {
      var $this     = $(this),
          $textarea = $this.find('textarea');

      $this
        .addClass(autosizeWrapperClass)
        .closest('.form-item')
          .addClass(containerClassAutosize);

      autosize($textarea);
    },
    function(context, settings, trigger) {
      var $this     = $(this),
          $textarea = $this.find('textarea');

      $this
        .removeClass(autosizeWrapperClass)
        .closest('.form-item')
          .removeClass(containerClassAutosize);

      autosize.destroy($textarea);
    }
  );
});
});
