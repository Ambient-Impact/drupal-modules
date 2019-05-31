// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Tooltip component
// -----------------------------------------------------------------------------

// This component is a wrapper around Tippy.js:
// https://atomiks.github.io/tippyjs/

AmbientImpact.onGlobals(['tippy', 'ally.when.key'], function() {
AmbientImpact.addComponent('tooltip', function(aiTooltip, $) {
	'use strict';

	var gutter = 5;

	/**
	 * Get a valid HTML element to trigger the tooltip events on.
	 *
	 * @param {Object} instance
	 *   The Tippy instance to look in.
	 *
	 * @return {HTMLElement}
	 *   Returns instance.reference if it looks like an HTML element, otherwise
	 *   returns instance.popper. The reference element can either be an element
	 *   that the tooltip is visually attached to, e.g. pointing to, or an
	 *   object with coordinates, in which case we use the popper element, which
	 *   is the tooltip itself.
	 */
	function getEventTarget(instance) {
		if ('nodeName' in instance.reference) {
			return instance.reference;
		} else {
			return instance.popper;
		}
	};

	/**
	 * Tooltip and trigger focusout event handler.
	 *
	 * This hides the tooltip if anything other than the trigger, tooltip, or a
	 * tooltip descendent gains focus.
	 */
	function focusOutHandler(event) {
		// Don't do anything if relatedTarget isn't present/supported.
		if (!('relatedTarget' in event)) {
			return;
		}

		if (
			// Did the trigger gain focus?
			event.relatedTarget === event.data.$trigger[0] ||
			// Did the tooltip element gain focus?
			event.relatedTarget === event.data.$tooltip[0] ||
			// Did a descendent of the tooltip gain focus?
			event.data.$tooltip.has(event.relatedTarget).length > 0
		) {
			return;
		}

		event.data.instance.hide();
	};

	/**
	 * Tippy.js callbacks that trigger events and perform various tasks.
	 *
	 * onShown and onHide manage a MutationObserver that hides the tooltip if it
	 * is scrolled off the screen. See:
	 *   * https://popper.js.org/popper-documentation.html#modifiers..hide
	 *   * https://github.com/FezVrasta/popper.js/blob/master/packages/popper/src/modifiers/hide.js
	 *   * https://github.com/atomiks/tippyjs/issues/284
	 *
	 * onShown and onHide manage hiding of the tooltip if scrolling occurs on
	 * the document.
	 *
	 * @type {Object}
	 */
	var events = {
		onShow: function(instance) {
			var	aiObject		= instance.options.AmbientImpact,
				trigger			= getEventTarget(instance),
				$trigger		= $(trigger),
				tooltip			= instance.popper,
				$tooltip		= $(tooltip),
				tooltipMoved	= false;

			// Bind a focusout handler to interactive tooltips and their
			// triggers to hide them if focus is moved to anything outside the
			// tooltip or the trigger. We have to use focusout as blur fails to
			// trigger sometimes.
			if (instance.options.interactive === true) {
				$trigger.add($tooltip).on('focusout.aiTooltip', {
					instance:	instance,
					$trigger:	$trigger,
					$tooltip:	$tooltip
				}, focusOutHandler);
			}

			// If the tooltip is marked as interactive, determine if we should
			// insert it after the trigger (the default), or use a provided
			// callback to insert it to a custom location.
			if (instance.options.interactive === true) {
				// If a callback is provided, defer to that.
				if (typeof aiObject.insertCallback === 'function') {
					aiObject.insertCallback($tooltip, $trigger);

					tooltipMoved = true;

				// If insertAfterElement is true, insert the tooltip after the
				// trigger. This ensures the tab sequence in and out of the
				// tooltip makes sense for the user.
				} else if (aiObject.insertAfterElement === true) {
					$tooltip.insertAfter($trigger);

					tooltipMoved = true;
				}

				// If the tooltip has been moved by either of the options above,
				// temporarily set Tippy's appendTo option to the parent of the
				// tooltip to avoid Tippy throwing an error when it tries to
				// remove the tooltip on hiding it. See the onHidden callback
				// for restoring this after Tippy has removed the tooltip.
				if (tooltipMoved === true) {
					aiObject.oldAppendTo = instance.options.appendTo;

					instance.options.appendTo = $tooltip.parent()[0];
				}
			}

			// Listen for the ESC key on the tooltip and the trigger, and hide
			// the tooltip if it's used.
			aiObject.tooltipEscapeHandle = ally.when.key({
				escape:		function(event, disengage) {
					// Make sure to focus the trigger before closing. This may
					// not be strictly necessary as tab sequence doesn't seem
					// affected, but might as well make sure.
					// @todo Should this lock the focus source on showing the
					// tooltip so focus outlines are only shown if keyboard was
					// used to show, or does that even matter?
					$trigger.focus();

					instance.hide();

					disengage();
				},
				context:	$tooltip
			});
			aiObject.triggerEscapeHandle = ally.when.key({
				escape:		function(event, disengage) {
					instance.hide();

					disengage();
				},
				context:	$trigger
			});

			$trigger.trigger('TooltipShow', [instance]);
		},
		onShown: function(instance) {
			var	aiObject	= instance.options.AmbientImpact,
				trigger		= getEventTarget(instance),
				$trigger	= $(trigger);

			$trigger.trigger('TooltipShown', [instance]);

			// Bind an event to hide the tooltip on scroll if set to do so.
			if (aiObject.hideOnScroll === true) {
				$(document).on('scroll.aiTooltipUID' + instance.id,
				$.debounce(100, function(event) {
					if (instance.state.visible) {
						instance.popperInstance.disableEventListeners();
						instance.hide();
					}
				}));
			}

			// Start observing for the out of bounds attribute if not set to
			// hide on scroll.
			if (
				'hideOnOutOfBoundsObserver' in aiObject &&
				aiObject.hideOnScroll !== true
			) {
				aiObject.hideOnOutOfBoundsObserver.observe(
					instance.popper, {
						attributes:			true,
						attributeFilter:	['x-out-of-boundaries']
					}
				);
			}
		},
		onHide: function(instance) {
			var	aiObject	= instance.options.AmbientImpact,
				trigger		= getEventTarget(instance),
				$trigger	= $(trigger),
				tooltip		= instance.popper,
				$tooltip	= $(tooltip);

			$trigger.trigger('TooltipHide', [instance]);

			// Stop observing for the out of bounds attribute.
			if ('hideOnOutOfBoundsObserver' in aiObject) {
				aiObject.hideOnOutOfBoundsObserver.disconnect();
			}

			// Remove the tooltip and trigger escape key events and delete their
			// handles.
			if ('tooltipEscapeHandle' in aiObject) {
				aiObject.tooltipEscapeHandle.disengage();

				delete aiObject.tooltipEscapeHandle;
			}
			if ('triggerEscapeHandle' in aiObject) {
				aiObject.triggerEscapeHandle.disengage();

				delete aiObject.triggerEscapeHandle;
			}

			// Remove the focusout handler.
			$trigger.add($tooltip).off('focusout.aiTooltip', focusOutHandler);

			// Remove the scroll event as it's no longer needed.
			$(document).off('scroll.aiTooltipUID' + instance.id);
		},
		onHidden: function(instance) {
			var	aiObject	= instance.options.AmbientImpact,
				trigger		= getEventTarget(instance),
				$trigger	= $(trigger);

			// If there's a saved appendTo from the onShow callback, restore it
			// and delete the saved property.
			if ('oldAppendTo' in aiObject) {
				instance.options.appendTo = aiObject.oldAppendTo;

				delete aiObject.oldAppendTo;
			}

			$trigger.trigger('TooltipHidden', [instance]);
		}
	};

	/**
	 * Default settings when creating a tooltip.
	 *
	 * @type {Object}
	 */
	this.defaults = {
		// If true, will hide the tooltip when Popper considers it out of
		// bounds, meaning that it has been scrolled out of view. This requires
		// MutationObserver at the moment, as we can't use onUpdate(). :(
		hideOnOutOfBounds:	true,

		// If true, will hide the tooltip on scroll. Note that if this is true,
		// hideOnOutOfBounds will have no effect.
		hideOnScroll:		false,

		// If the tooltip is interactive, whether to insert it directly after
		// the trigger so that it's in the expected tab sequence. This will
		// override Tippy's appendTo option if true and interactive is true.
		// Ignored if insertCallback is a function.
		insertAfterElement:	true,

		// If the tooltip is interactive and this is provided, this callback is
		// responsible for inserting the tooltip element into a custom location
		// right before the tooltip starts transitioning into view. The callback
		// is passed two jQuery collections as arguments: $tooltip and $trigger.
		// If this is a function, insertAfterElement will be ignored. Note that
		// this must not break the tabbing order.
		insertCallback:		undefined,

		/**
		 * Settings passed to Tippy.js.
		 *
		 * @see https://atomiks.github.io/tippyjs/#all-options
		 */
		tippy: {
			// This sets the font-size to inherit the document size. This works
			// because Tippy.js will output whatever is provided to the
			// data-size attribute, so we use this in the CSS to apply
			// font-size: inherit;
			size:			'inherit',

			// This adds the touchstart event as a trigger which fixes an issue
			// with hideOnOutOfBounds on touch devices where you would have to
			// tap elsewhere to move the browser's hover/mouseenter state from
			// the element before you could tap it again to show the tooltip.
			trigger:		'mouseenter focus touchstart',

			// Minimizes the data attributes added to the DOM to increase
			// performance.
			performance:	true,

			theme:			'material-dark',

			// Popper.js options go here. See:
			// https://popper.js.org/popper-documentation.html
			// NOTE: Tippy.js overrides the onCreate() and onUpdate() callbacks
			// with its own.
			popperOptions:	{
				modifiers:	{
					preventOverflow:	{
						// Makes tooltips use the AdaptiveTheme gutter (if
						// available) when being positioned, so they stay within
						// it. See:
						// https://popper.js.org/popper-documentation.html#modifiers..preventOverflow.padding
						padding:	gutter
					}
				}
			}
		}
	};

	/**
	 * Create a tooltip or tooltips.
	 *
	 * @param {HTMLElement|String} element
	 *   The first parameter to pass to the Tippy.js call, either an HTML
	 *   element or a selector string.
	 *
	 * @param {Object} options
	 *   Additional options. See this.defaults. Note that event handlers are
	 *   merged on top of this, so they will overwrite any passed here. Bind to
	 *   events instead.
	 *
	 * @return {object}
	 *   The return value of the tippy() call.
	 *
	 * @see tippy()
	 * @see this.defaults
	 */
	this.create = function(element, options) {
		var settings = $.extend(true,
			{},
			this.defaults,
			options,
			{tippy: events}
		),
		// The array returned by calling tippy().
		tippyReturn,
		// Whether we're set to hide the tooltip if it goes out of bounds and
		// MutationObserver is supported.
		hideOnOutOfBounds =
			settings.hideOnOutOfBounds === true &&
			'MutationObserver' in window;

		// Create an object within the Tippy settings to store our own settings.
		// Trying to create a property of the popper element here doesn't seem
		// to translate to the element when in the hide/show callbacks, but this
		// does make it through.
		settings.tippy.AmbientImpact = {};
		$.each(settings, function(name, value) {
			if (name === 'tippy') {
				return;
			}

			settings.tippy.AmbientImpact[name] = value;
		});

		if (hideOnOutOfBounds === true) {
			$.extend(true, settings, {
				tippy: {popperOptions: {modifiers: {preventOverflow: {
					// Tell Popper to use the viewport as the element to
					// determine if the tooltip is scrolled off screen. This
					// fixes the x-out-of-boundaries attribute not being added
					// to the .tippy-popper element. See:
					// https://popper.js.org/popper-documentation.html#modifiers..preventOverflow.boundariesElement
					boundariesElement:	'viewport',
				}}}}
			});
		}

		// Create the Tippy instance(s).
		tippyReturn = tippy(element, settings.tippy);

		// Add a Mutation Observer to each Popper element to hide it if set to
		// do so. This will start observing in the onShown() callback and will
		// be disconnected in the onHide() callback.
		if (hideOnOutOfBounds === true) {
			for (var i = 0; i < tippyReturn.tooltips.length; i++) {
				var	tooltip		= tippyReturn.tooltips[i],
					aiObject	= tooltip.options.AmbientImpact;

				aiObject.hideOnOutOfBoundsObserver =
				new MutationObserver(function(mutations) {
					for (var j = 0; j < mutations.length; j++) {
						var attributes = mutations[j].target.attributes;

						for (var k = 0; k < attributes.length; k++) {
							if (attributes[k].name === 'x-out-of-boundaries') {
								mutations[j].target._tippy.popperInstance
									.disableEventListeners();
								mutations[j].target._tippy.hide();
							}
						}
					}
				});
			}
		}

		return tippyReturn;
	};

	/**
	 * Destroy tooltips for a given element.
	 *
	 * @param {HTMLElement} element
	 *   The HTML element that tooltips are attached to.
	 *
	 * @param {Boolean} destroyDelegated
	 *   Passed to element._tippy.destroy() as the only parameter.
	 *
	 * @return {Boolean}
	 *   True if the tippy instance was found and destroyed, false if not found.
	 *
	 * @see https://atomiks.github.io/tippyjs/#methods
	 */
	this.destroy = function(element, destroyDelegated) {
		if (
			AmbientImpact.objectPathExists(
				'_tippy.destroy',
				element
			) &&
			typeof element._tippy.destroy === 'function'
		) {
			if (typeof destroyDelegated === 'undefined') {
				destroyDelegated = true;
			}

			element._tippy.destroy(destroyDelegated);

			return true;
		}

		return false;
	};
});
});
