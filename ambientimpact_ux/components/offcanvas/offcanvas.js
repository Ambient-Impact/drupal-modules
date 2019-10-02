// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Off-canvas panel component
// -----------------------------------------------------------------------------

// This is a wrapper around Frend Off Canvas, adding reusable features.
// @see https://frend.co/components/offcanvas/

// We're currently using MutationObserver to watch for the panel opening and
// closing, as the Frend component doesn't yet support open/close events.
// @see https://github.com/frend/frend.co/issues/70

// In the future, either contributing to that project or just writing our own
// off canvas from scratch would be ideal, so we don't have to rely on
// MutationObserver for such basic functionality.

// @todo Why does hitting the ESC key only work once you've tabbed once, even
// though the panel itself has focus and should be receiving keyboard events?
//
// @see https://trello.com/c/ZTG48eoc/527-offcanvas-panels-should-close-when-pressing-esc

AmbientImpact.onGlobals(['Froffcanvas', 'ally.maintain.disabled'], function() {
AmbientImpact.addComponent('offcanvas', function(aiOffcanvas, $) {
  'use strict';

  // A private array of panel HTMLElements.
  var panels = [];

  // Valid option values.
  var validOptions = {
    panelLocation:  ['left', 'right', 'top', 'bottom']
  };

  this.defaults = {
    // If this is an HTML element or jQuery collection, it will be used for
    // the open button(s). If this is anything else, an open button will be
    // generated and inserted before the panel element.
    openButton:     true,
    // The open button text, if a button is to be generated.
    openButtonText:   Drupal.t('Menu'),
    // The classes to add to the open button(s).
    openButtonClasses:  ['offcanvas-open'],

    // If this is an HTML element or jQuery collection, it will be used for
    // the close button. If this is anything else, a close button will be
    // generated and appended to the panel element.
    closeButton:    true,
    // The close button text, if a button is to be generated.
    closeButtonText:  Drupal.t('Close'),
    // The classes to add to the close button.
    closeButtonClasses: ['offcanvas-panel__close', 'offcanvas-close'],

    // This is the selector to pass to Froffcanvas. Make sure this is unique
    // for every instance, or weird stuff will happen!
    panelSelector:    '',
    // Classes to add to the panel on init.
    panelClasses:   ['offcanvas-panel', 'offcanvas-panel--theme-auto'],
    // Either 'left' or 'right'.
    panelLocation:    'left',

    // If true, will create an overlay and prevent interaction with the rest
    // of the document outside of the panel while the panel is open.
    modal:        true,

    // Classes to add to the overlay on init.
    overlayClasses:   ['offcanvas-overlay']
  };

  // Update the viewport offsets for each panel if/when the offset change
  // event fires. This is mostly to account for the admin Toolbar, but could be
  // produced by other things that use Drupal.displace. Does not do anything
  // if a panel is modal, as that excludes interaction with anything else.
  $(document)
    .on('drupalViewportOffsetChange.aiOffcanvas', function(event, offsets) {
      $.each(panels, function(index, panel) {
        if (panel.aiOffcanvas.settings.modal) {
          return;
        }

        $.each([
          // Top and bottom, obviously.
          'top',
          'bottom',
          // Also the edge to which the panel is docked to, i.e.
          // 'left' or 'right';
          panel.aiOffcanvas.settings.panelLocation,
        ], function(index, edge) {
          $(panel).css(edge, offsets[edge] + 'px');
        });
      });
    });

  /**
   * Determine if a panel is open.
   *
   * @param {jQuery} $panel
   *
   * @return {bool}
   *   True if open, false otherwise.
   */
  function isPanelOpen($panel) {
    return $panel.is('[aria-hidden="false"]');
  };

  /**
   * Trigger the open event on a panel.
   *
   * @param {jQuery} $panel
   */
  function openEvent($panel) {
    $panel.trigger('openOffcanvas');
  };

  /**
   * Trigger the close event on a panel.
   *
   * @param {jQuery} $panel
   */
  function closeEvent($panel) {
    $panel.trigger('closeOffcanvas');
  };

  /**
   * Bind events so that we can provide open and close events.
   *
   * Froffcanvas doesn't currently offer any events, so we use a
   * MutationObserver watching for changes to the 'aria-hidden' attribute to
   * determine if the panel has been opened or closed. See link for
   * Froffcanvas issue. If MutationObserver is not supported by the browser,
   * no events will be fired.
   *
   * @param {jQuery} $panel
   *
   * @link https://github.com/frend/frend.co/issues/70
   * @link https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver
   */
  function bindEvents($panel) {
    if (!('MutationObserver' in window)) {
      return;
    }

    var panel = $panel[0];

    panel.aiOffcanvas.mutationObserver =
      new MutationObserver(function(mutations) {
        var action;

        for (var i = 0; i < mutations.length; i++) {
          var mutatedPanel = mutations[i].target;

          if (
            mutations[i].oldValue === 'false' &&
            mutatedPanel.getAttribute('aria-hidden') === 'true'
          ) {
            action = 'closed';
          } else if (
            mutations[i].oldValue === 'true' &&
            mutatedPanel.getAttribute('aria-hidden') === 'false'
          ) {
            action = 'opened';
          }
        }

        switch (action) {
          case 'opened':
            openEvent($panel);

            break;

          case 'closed':
            closeEvent($panel);

            break;
        }
      });
    panel.aiOffcanvas.mutationObserver.observe(panel, {
      attributes:     true,
      attributeFilter:  ['aria-hidden'],
      attributeOldValue:  true
    });
  };

  /**
   * Unbind events from bindEvents().
   *
   * This just disconnects and removes the MutationObserver.
   *
   * @param {jQuery} $panel
   *
   * @see bindEvents()
   */
  function unbindEvents($panel) {
    var panel = $panel[0];

    if (!('mutationObserver' in panel.aiOffcanvas)) {
      return;
    }

    panel.aiOffcanvas.mutationObserver.disconnect();

    delete panel.aiOffcanvas.mutationObserver;
  };

  /**
   * Initialize an off canvas panel.
   *
   * @param {HTMLElement|jQuery} panel
   *
   * @param {object} options
   *
   * @return {Froffcanvas}
   *   The Froffcanvas instance that was created and initialized.
   *
   * @see this.defaults
   */
  this.init = function(panel, options) {
    var settings  = $.extend(true, {}, this.defaults, options),
      $panel    = $(panel),
      $openButton,
      $closeButton,
      $overlay;

    // Make sure this is an HTMLElement and not a jQuery object.
    panel = $panel[0];

    // Don't do anything if this is already initialized.
    if ('aiOffcanvas' in panel) {
      return;
    }

    $panel.addClass(settings.panelClasses.join(' '));

    // If the panel location is not a valid value, set it to the default.
    if ($.inArray(
      settings.panelLocation, validOptions.panelLocation
    ) === -1) {
      settings.panelLocation = this.defaults.panelLocation;
    }

    // Add the object to the panel element that we'll use to store the
    // Froffcanvas instance, settings, and references to elements.
    panel.aiOffcanvas = {instance: {}, settings: {}, elements: {}};

    // Add a modifier class indicating the location of the panel, i.e.
    // 'left' or 'right'.
    $panel.addClass(
      settings.panelClasses[0] + '--' + settings.panelLocation
    );

    // Set up the open button.
    if (settings.openButton.attr || settings.openButton.tagName) {
      // A button was provided, so use that.
      $openButton = $(settings.openButton);

    } else {
      // No button was provided, so generate one.
      $openButton = $('<button></button>');

      $openButton.text(settings.openButtonText);

      $panel.before($openButton);
    }

    $openButton
      .addClass(settings.openButtonClasses.join(' '))
      .attr('aria-controls', $panel.attr('id'));

    panel.aiOffcanvas.elements.open = $openButton;


    // Set up the close button.
    if (settings.closeButton.attr || settings.closeButton.tagName) {
      $closeButton = $(settings.closeButton).first();

    } else {
      $closeButton = $('<button></button>');

      $closeButton.text(settings.closeButtonText);

      $panel.append($closeButton);
    }

    $closeButton.addClass(settings.closeButtonClasses.join(' '));

    panel.aiOffcanvas.elements.close = $closeButton;


    // Initialize the Froffcanvas instance and save it to the element.
    panel.aiOffcanvas.instance = Froffcanvas({
      // String - Selector for the panel.
      selector:   settings.panelSelector,

      // String - Selector for the open button(s).
      openSelector: '.' + settings.openButtonClasses[0],

      // String - Selector for the close button.
      closeSelector:  '.' + settings.closeButtonClasses[0],

      // String - Class name that will be added to the panel when the
      // component has been initialised.
      readyClass:   settings.panelClasses[0] + '--is-ready',

      // String - Class name that will be added to the panel when the
      // panel is visible.
      activeClass:  settings.panelClasses[0] + '--is-active'
    });

    // Save settings to the element.
    panel.aiOffcanvas.settings = settings;

    // Save the panel element to our array so that know which panels are
    // initialized later.
    panels.push(panel);

    // Bind events.
    bindEvents($panel);


    // If we're modal, create and insert an overlay element.
    if (settings.modal) {
      $overlay = $('<div></div>');

      $overlay.addClass(settings.overlayClasses.join(' '));

      $panel
        .before($overlay)
        .addClass(settings.panelClasses[0] + '--modal')
        .on('openOffcanvas.aiOffcanvasOverlay', function(event) {
          $overlay.addClass(
            settings.overlayClasses[0] + '--active'
          );
        })
        .on('closeOffcanvas.aiOffcanvasOverlay', function(event) {
          $overlay.removeClass(
            settings.overlayClasses[0] + '--active'
          );

          // Re-enable disabled elements on the page.
          if (
            'overlayFocusDisabledHandle' in panel.aiOffcanvas &&
            'disengage' in
              panel.aiOffcanvas.overlayFocusDisabledHandle
          ) {
            panel.aiOffcanvas.overlayFocusDisabledHandle
              .disengage();

            delete panel.aiOffcanvas.overlayFocusDisabledHandle;

            // Focus the open button, as Froffcanvas will have
            // failed to do so because the button was disabled.
            panel.aiOffcanvas.elements.open.trigger('focus');
          }
        })
        .on('transitionend.aiOffcanvasOverlay', function(event) {
          if (
            event.target === panel &&
            isPanelOpen($panel) &&
            !('overlayFocusDisabledHandle' in panel.aiOffcanvas)
          ) {
            panel.aiOffcanvas.overlayFocusDisabledHandle =
              ally.maintain.disabled({
                filter: $panel
              });

            // This fixes a bug where Gecko/Firefox would not
            // register the ESC key being used until you would tab
            // to a child element. While Froffcanvas seems to focus
            // the panel, this doesn't seem to stick with Gecko,
            // either because it was getting blurred or something
            // else was happening.
            $panel.trigger('focus');
          }
        });

      panel.aiOffcanvas.elements.overlay = $overlay;
    }


    return panel.aiOffcanvas.instance;
  };

  /**
   * Remove all modifications/events from an off canvas panel.
   *
   * @param {HTMLElement|jQuery} panel
   *
   * @see this.init()
   * @see Froffcanvas.destroy()
   */
  this.destroy = function(panel) {
    var $panel      = $(panel),
      settings,
      elements,
      panelsIndex   = -1,
      $openButton,
      $closeButton;

    // Make sure this is an HTMLElement and not a jQuery object.
    panel = $panel[0];

    // Don't do anything if this panel hasn't been altered by us.
    if (!('aiOffcanvas' in panel)) {
      return;
    }

    // Get a reference to the settings.
    settings = panel.aiOffcanvas.settings;

    // Get a reference to the panel elements.
    elements = panel.aiOffcanvas.elements;

    // Unbind events.
    unbindEvents($panel);

    // Disable the Froffcanvas instance. Note that this only undoes its
    // modifications, removing event listeners, etc., but the instance can
    // still be enabled again by calling .init() on it.
    panel.aiOffcanvas.instance.destroy();

    // Find the panel element in the panels array and remove it. Note that
    // $.inArray(panels, panel) doesn't seem to find it for whatever reason.
    for (var i = panels.length - 1; i >= 0; i--) {
      if (panels[i] === panel) {
        panelsIndex = i;

        break;
      }
    }
    if (panelsIndex > -1) {
      panels.splice(panelsIndex, 1);
    }


    // If we have an overlay, remove it and its event listeners.
    if (elements.overlay) {
      elements.overlay.remove();

      $panel.off([
        'openOffcanvas.aiOffcanvasOverlay',
        'closeOffcanvas.aiOffcanvasOverlay',
        'transitionend.aiOffcanvasOverlay',
      ].join(' '));
    }

    // Disengage the focus disabled handle if it's active. This can happen
    // if we're told to destroy while the panel is open.
    if (
      'overlayFocusDisabledHandle' in panel.aiOffcanvas &&
      'disengage' in panel.aiOffcanvas.overlayFocusDisabledHandle
    ) {
      panel.aiOffcanvas.overlayFocusDisabledHandle
        .disengage();

      delete panel.aiOffcanvas.overlayFocusDisabledHandle;
    }

    // Undo changes to the close button, and remove it if we generated it.
    if (settings.closeButton.attr || settings.closeButton.tagName) {
      // If it was passed to us, just find it and remove the classes.
      $closeButton = $(settings.closeButton).first();

      $closeButton.removeClass(settings.closeButtonClasses.join(' '));
    } else {
      // If it was generated, to find it and remove it.
      $closeButton = elements.close;

      $closeButton.remove();
    }


    // Undo changes to the open button, removing it if we generated it.
    if (settings.openButton.attr || settings.openButton.tagName) {
      // The button was provided, so just wrap it.
      $openButton = $(settings.openButton);

      $openButton
        .removeClass(settings.openButtonClasses.join(' '))
        .removeAttr('aria-controls');
    } else {
      // A button was generated by us, so find it and remove it.
      $openButton = elements.open;

      $openButton.remove();
    }


    // Remove classes from the panel.
    $panel.removeClass(settings.panelClasses.join(' '));
    $panel.removeClass([
      settings.panelClasses[0] + '--' + settings.panelLocation,
      settings.panelClasses[0] + '--modal',
    ].join(' '));


    // Delete the object from the panel element.
    delete panel.aiOffcanvas;
  };

  /**
   * Enable a previously disabled off canvas panel.
   *
   * @param {HTMLElement|jQuery} panel
   *
   * @see this.init()
   * @see Froffcanvas.init()
   */
  this.enable = function(panel) {
    $(panel)[0].aiOffcanvas.instance.init();
  };

  /**
   * Disable a previously initialized off canvas panel.
   *
   * @param {HTMLElement|jQuery} panel
   *
   * @see this.init()
   * @see Froffcanvas.init()
   * @see Froffcanvas.destroy()
   */
  this.disable = function(panel) {
    $(panel)[0].aiOffcanvas.instance.destroy();
  };
});
});
