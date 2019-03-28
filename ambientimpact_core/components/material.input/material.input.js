/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Material Design text input component
----------------------------------------------------------------------------- */

AmbientImpact.on('jquery', function(aijQuery) {
AmbientImpact.onGlobals(['ally.get.activeElement'], function() {
AmbientImpact.addComponent('material.input', function(aiMaterialInput, $) {
  'use strict';

  var inputSelector     = '.form-item :textall',
    textareaSelector    = '.form-item-textarea--autosize textarea',
    // textareaSelector   = '.form-type-textarea textarea',

    containerClass      = 'material-input',
    containerTextareaClass  = containerClass + '--textarea',
    containerHasFocusClass  = containerClass + '--has-focus',
    containerIsEmptyClass = containerClass + '--is-empty',
    containerIsInvalidClass = containerClass + '--is-invalid',
    containerHasPlaceholderClass  = containerClass + '--has-placeholder',
    inputClass        = containerClass + '__input',
    labelClass        = containerClass + '__label',
    optionalIndicatorClass  = containerClass + '__optional',
    underlineClass      = containerClass + '__underline',
    messagesClass     = containerClass + '__messages',
    descriptionClass    = containerClass + '__description',
    autocompleteClass   = containerClass + '__autocomplete',
    viewsExposedWidgetHasInputClass = 'views-exposed-widget--has-' +
                  containerClass,

    linkFieldURLLabelData = 'material-input-link-field-url-old-label',

    // Event handlers.
    focusEvent        = function(event) {
      $(this).closest('.' + containerClass)
        .addClass(containerHasFocusClass);
    },
    blurEvent       = function(event) {
      $(this).closest('.' + containerClass)
        .removeClass(containerHasFocusClass);

      // These are the input types to ignore.
      var $otherInputs = $(this).parents('form').find(':textall, textarea')
        // Ignore this input.
        .not(this)
        // Ignore disabled, readonly, and otherwise visually hidden inputs.
        .not(':disabled, [readonly], :hidden');

      // Check if the field is valid on blur. This is in addition to the
      // check the browser does on form submission. By checking on blur,
      // we can alert the user that they have an error, but we don't jump
      // on it until they've left the field so as to not overload them. We
      // also don't check validity if there are no other non-button and
      // non-hidden inputs in the form. This is done to avoid obvious and
      // unnecessary errors on the search form, for example. There's no
      // need to be told that you have to fill the only text field, except
      // on submit.
      if (
        this.checkValidity &&
        $otherInputs.length > 0
      ) {
        this.checkValidity();
      }
    },
    emptyEvent        = function(event) {
      // Check if the field has a value, mark as empty if not. Note that
      // as of Chrome 55.0.2883.21 beta-m (64-bit) (and probably older),
      // this doesn't work on password fields that have been autofilled
      // by the browser on a hard load, until the user interacts with the
      // page, by clicking or focusing something, at which point the input
      // correctly evalutes the length of the value. This is most likely
      // by design, for the sake of security. Firefox doesn't exhibit this
      // behaviour.
      if (this.value.length < 1) {
        $(this).closest('.' + containerClass)
          .addClass(containerIsEmptyClass);
      } else {
        $(this).closest('.' + containerClass)
          .removeClass(containerIsEmptyClass);
      }
    },
    validationEvent     = function(event) {
      var $this = $(this);

      // Mark container with invalid class if 'invalid' event.
      if (event.type === 'invalid') {
        $this.closest('.' + containerClass)
          .addClass(containerIsInvalidClass);

        // Grab the validation message, if any.
        var text = '';
        if (this.validationMessage) {
          text = this.validationMessage;
        }
        // Present the validation message in the messages area.
        $this
          .closest('.' + containerClass)
          .find('.' + messagesClass)
            .text(text);

        // Focus the current element only if the current active element is not a
        // text input, so that we hit the first error field only, and won't end
        // up focusing all fields in sequence, finishing off on the last one.
        if (!$(ally.get.activeElement()).is(':textall')) {
          $this.trigger('focus');
        }

        // Prevent the browser from showing the default built-in errors,
        // e.g. error bubbles.
        event.preventDefault();
      } else {
        $this.closest('.' + containerClass)
          .removeClass(containerIsInvalidClass)
          // Remove any messages
          .find('.' + messagesClass)
            .text('');
      }
    },
    // Attach to specified input selectors, within context.
    attach = function(selector, context) {
      $(selector, context).once(containerClass).each(function() {
        var inputID, tempInputID, messagesID;

        // Get the input ID - required for generating the ID on the
        // messages element so that we can use aria-describedby
        inputID = $(this).attr('id');

        // If the input doesn't have an ID, we generate one, but we
        // rarely have to do this because Drupal's form API does this
        // for us. If we do, however, we only loop through 100 possible
        // variations before giving up, to avoid blocking.
        if (inputID === undefined) {
          for (var i = 0; i <= 100; i++) {
            tempInputID = 'material-input' + i;

            if (document.getElementById(tempInputID) === null) {
              inputID = tempInputID;

              break;
            }
          }
        }

        messagesID = inputID + '-material-input-messages';

        // Make sure the label is sitting within the same parent element
        // as the input. Drupal normally ensures this, but some modules
        // have weird placement. E.g. Views exposed filters.
        $('label[for="' + inputID + '"]', context)
          .insertBefore(this);

        $(this)
          // Attach events.
          .on('focus',        focusEvent)
          .on('blur',         blurEvent)
          // The empty event must also attach to 'blur' to properly
          // catch autofill in Chrome (and possibly other browsers).
          .on('input.empty',      emptyEvent)
          .on('input invalid change', validationEvent)
          // Insert underline element.
          .after('<div class="' + underlineClass + '"></div>')
          // Insert messages element.
          .siblings('.' + underlineClass)
            .after('<em id="' + messagesID + '" class="' + messagesClass + '"></em>')
          .end()
          // Associate the messages with the form field:
          // https://adactio.com/journal/11109
          .attr('aria-describedby', messagesID)
          // Add BEM classes.
          .closest('.form-item')
            .addClass(containerClass)
            .find('label')
              .addClass(labelClass)
              // .each(function() {
              //   // Add an optional indicator for better UX:
              //   // http://uxmovement.com/forms/why-users-fill-out-less-if-you-mark-required-fields/
              //   if (!$(this).has('.form-required').length) {
              //     $(this).append(
              //       '<em class="' + optionalIndicatorClass + '"> ' +
              //         Drupal.t('(optional)') +
              //       '</em>'
              //     );
              //   }
              // })
            .end()
            .find('.description')
              .addClass(descriptionClass)
            .end()
          .end()
          .addClass(inputClass);

        // Trigger the focus handlers if the input is already
        // focused by the time we've attached events to it.
        if (this === ally.get.activeElement()) {
          $(this).triggerHandler('focus');
        }

        // Add a class if a placeholder is present and not empty.
        if ($(this).is('[placeholder][placeholder!=""]')) {
          $(this).closest('.' + containerClass)
            .addClass(containerHasPlaceholderClass);
        }

        // Add a class if Drupal has marked this field as being in
        // error.
        if ($(this).is('.error')) {
          $(this).closest('.' + containerClass)
            .addClass(containerIsInvalidClass);
        }

        // Check if this is a single input inside of a Link field.
        var $linkField = $(this).closest('.form-type-link-field');
        if (
          $linkField.length &&
          $linkField.find(selector).length === 1
        ) {
          var $linkFieldLabel = $linkField.find('> label'),
            $linkURLLabel = $linkField.find('.' + labelClass);

          $linkFieldLabel.addClass('element-invisible');
          $linkURLLabel.removeClass('element-invisible');

          // Find the text nodes in the URL label.
          var $linkURLLabelText = $(
            $linkURLLabel.contents().textNodes()
          );

          // Back up the existing text for when we detach.
          $linkURLLabel.data(linkFieldURLLabelData, $linkURLLabelText.text());

          // Replace the URL label with the overall field label.
          $linkURLLabelText[0].textContent =
            $linkFieldLabel.clone()
              // Remove the required indicator from the copy,
              // so that it doesn't end up in the .text().
              .find('.required')
                .remove()
              .end()
              .contents()
                .textNodes()
                .text();
        }

        // Add a class to any containing Views exposed widget to
        // indicate it contains a Material input.
        $(this).closest('.views-exposed-widget')
          .addClass(viewsExposedWidgetHasInputClass);

        var $input = $(this),
          triggerEmptyCallback = function() {
            $input.triggerHandler('input.empty');
          };
        // Trigger the empty check on document ready...
        $(triggerEmptyCallback);
        // ...and window load events
        $(window).on('load', triggerEmptyCallback);
        // ...and also on a parent fieldset formUpdated event, in
        // case the input is initially in a display: none;
        // container, which will return an empty value until visible
        // again.
        $input.closest('fieldset').on(
          'formUpdated.AmbientImpactMaterialInput',
          triggerEmptyCallback
        );
      });
    },
    // Detach from specified input selectors, within context.
    detach = function(selector, context) {
      $(selector, context).removeOnce(containerClass).each(function() {
        var $linkField, $linkFieldLabel, $linkURLLabel,
          $linkURLLabelText, $viewsExposedWidget;

        // Check if this is a single input inside of a Link field
        $linkField = $(this).closest('.form-type-link-field');
        if (
          $linkField.length &&
          $linkField.find(selector).length === 1
        ) {
          $linkFieldLabel = $linkField.find('> label');
          $linkURLLabel = $linkField.find('.' + labelClass);

          $linkFieldLabel.removeClass('element-invisible');
          $linkURLLabel.addClass('element-invisible');

          // Find the text nodes in the URL label.
          $linkURLLabelText = $(
            $linkURLLabel.contents().textNodes()
          );

          // Restore backed up text.
          $linkURLLabelText[0].textContent = $linkURLLabel.data(linkFieldURLLabelData);
          // Remove the backup text data.
          $linkURLLabel.removeData(linkFieldURLLabelData);
        }

        $(this)
          // Detach events.
          .off('focus',         focusEvent)
          .off('blur',          blurEvent)
          .off('input.empty',       emptyEvent)
          .off('input invalid change',  validationEvent)
          // Remove underline element.
          .siblings('.' + underlineClass)
            .remove()
          .end()
          // Remove the messages element association.
          .removeAttr('aria-describedby')
          // Remove messages element.
          .siblings('.' + messagesClass)
            .remove()
          .end()
          // Remove BEM classes.
          .closest('.form-item')
            .removeClass(containerClass)
            .removeClass(containerHasPlaceholderClass)
            .removeClass(containerIsInvalidClass)
            .removeClass(containerIsEmptyClass)
            .find('label')
              .removeClass(labelClass)
            .end()
            // Remove description class.
            .find('.description')
              .removeClass(descriptionClass)
            .end()
          .end()
          .removeClass(inputClass)
          // .closest('.form-item')
          //   // Remove optional indicator.
          //   .find('label .' + optionalIndicatorClass)
          //     .remove();

        // Remove the empty input checking event from any parent
        // fieldsets.
        $(this).closest('fieldset').off(
          'formUpdated.AmbientImpactMaterialInput'
        );

        $viewsExposedWidget = $(this).closest('.views-exposed-widget');
        if ($viewsExposedWidget.length > 0) {
          // Place the label for a Views exposed widget back in its
          // non-standard location.
          $('label[for="' + $(this).attr('id') + '"]', context)
            .prependTo($viewsExposedWidget);

          // Remove the class indicating it contains a Material input.
          $viewsExposedWidget
            .removeClass(viewsExposedWidgetHasInputClass);
        }
      });
    };

  // Add behaviors for single-line inputs.
  this.addBehaviors({
    AmbientImpactMaterialInput: {
      attach: function (context, settings) {
        // Run attach().
        attach(inputSelector, context);
      }, detach: function (context, settings, trigger) {
        // Don't detach if we're not unloading. For example, we don't
        // need to detach on the serialize trigger, which is run on any
        // sort of Ajax request, including uploading a file via a file
        // form item. Detaching then would cause unnecessary layout
        // jumps, without any good reason to, since form is still
        // available for the user to view and interact with.
        if (trigger !== 'unload') {
          return;
        }

        // Run detach().
        detach(inputSelector, context);
      }
    }
  });

  // Add behaviors to textareas when the Textarea component has loaded and
  // attached Autosize to them.
  AmbientImpact.on('textarea', function(aiTextarea) {
    aiMaterialInput.addBehaviors({
      AmbientImpactMaterialInputTextarea: {
        attach: function (context, settings) {
          // Run attach().
          attach(textareaSelector, context);

          $(textareaSelector, context).once(containerTextareaClass).each(function() {
            // Set the initial rows to 1 and update Autosize. This
            // is to match the Material Design style.
            if (window.autosize) {
              $(this)
                // Backup old rows value.
                .data('material-input-old-rows', $(this).attr('rows'))
                // Set to a single row.
                .attr('rows', 1)
              // Give Autosize a poke.
              autosize.update(this);
            }
          });
        }, detach: function (context, settings, trigger) {
          // Don't detach if we're not unloading. For example, we
          // don't need to detach on the serialize trigger, which is
          // run on any sort of Ajax request, including uploading a
          // file via a file form item. Detaching then would cause
          // unnecessary layout jumps, without any good reason to,
          // since form is still available for the user to view and
          // interact with.
          if (trigger !== 'unload') {
            return;
          }

          // Run detach().
          detach(textareaSelector, context);

          $(textareaSelector, context).removeOnce(containerTextareaClass).each(function() {
            // Restore the previous rows attribute, if any.
            if ($(this).data('material-input-old-rows')) {
              $(this)
                .attr(
                  'rows',
                  $(this).data('material-input-old-rows')
                )
                .removeData('material-input-old-rows');
            }
            if (window.autosize) {
              // Give Autosize a poke.
              autosize.update(this);
            }
          });
        }
      }
    });
  });

  // Drupal autocomplete customizations.
  // if ('jsAC' in Drupal) {
  //   var originalAutocompletePopulatePopup = Drupal.jsAC.prototype.populatePopup;
  //   Drupal.jsAC.prototype.populatePopup = function () {
  //     // Run the original .populatePopup().
  //     originalAutocompletePopulatePopup.call(this);

  //     if (this.popup) {
  //       // Add our class to the popup.
  //       $(this.popup).addClass(autocompleteClass);
  //     }
  //   };
  // }
});
});
});
