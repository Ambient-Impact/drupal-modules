/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Form validation component
----------------------------------------------------------------------------- */

AmbientImpact.onGlobals([
  // Component is not registered if the browser doesn't support constraint
  // validation by checking for this method on form elements.
  'HTMLFormElement.prototype.checkValidity',
], function() {
AmbientImpact.on('jquery', function(aijQuery) {
AmbientImpact.addComponent('form.validation', function(aiFormValidation, $) {
  'use strict';

  // This triggers 'invalid' and 'valid' events on the form when submission is
  // attempting, only allowing submission if form passes validation. Based on:
  // https://stackoverflow.com/a/48626400
  this.addBehaviour(
    'AmbientImpactFormValidationEvents',
    'ambientimpact-form-validation-events',
    'form',
    function(context, settings) {
      var $form = $(this);

      $form.on('submit.aiFormValidationEvents', function(event) {
        if (this.checkValidity() === false) {
          event.preventDefault();

          $form.trigger('invalid');
        } else {
          $form.trigger('valid');
        }
      });

      // Tell the browser to not validate automatically, as that would prevent
      // submit from being triggered. We prevent the submission above if form
      // doesn't pass validation. This is here in case there's an error in the
      // preceding code, so this (hopefully) doesn't get applied in that case
      // and browser validation takes place as a fallback.
      this.noValidate = true;
    },
    function(context, settings, trigger) {
      var $form = $(this);

      this.noValidate = false;

      $form.off('submit.aiFormValidationEvents');
    }
  );

  // This focuses the first invalid text field on 'invalid' event on the form.
  // @todo What about other input types, e.g. radio buttons, etc?
  this.addBehaviour(
    'AmbientImpactFormInvalidFocus',
    'ambientimpact-form-invalid-focus',
    'form',
    function(context, settings) {
      var $form = $(this);

      $form.on('invalid.aiFormInvalidFocus', function(event) {
          $form.find(':textall, textarea').filter(function() {
            return $(this).is(':invalid');
          }).first().trigger('focus');
      });
    },
    function(context, settings, trigger) {
      var $form = $(this);

      $form.off('invalid.aiFormInvalidFocus');
    }
  );
});
});
});
