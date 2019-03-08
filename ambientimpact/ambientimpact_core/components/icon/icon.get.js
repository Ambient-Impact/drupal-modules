/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Icon get component
----------------------------------------------------------------------------- */

AmbientImpact.on([
	'icon.support', 'icon.load',
], function(aiIconSupport, aiIconLoad) {
AmbientImpact.addComponent('icon.get', function(aiIconGet, $) {
	'use strict';

	this.get = function(iconName, options) {
		var	$icon				= $(),
			settings			= {},
			replacementValues	= {},
			template			= this.settings.template,
			containerBaseClass	= this.settings.containerBaseClass;

		// Return an empty collection if the icon name is not valid.
		if ($.type(iconName) !== 'string' || iconName.length < 1) {
			return $icon;
		}

		// Merge in options over defaults.
		settings = $.extend(true, {},
			this.settings.templateDefaults,
			{
				text:				'',
				standalone:			null,
				containerClasses:	[],
				iconClasses:		[],
				textClasses:		[]
			},
			options
		);

		// If the icon URL hasn't been specified, attempt to grab the bundle
		// URL, if one is specified. We return an empty collection if we can't
		// find a valid bundle.
		if (!settings.url) {
			if (
				'bundle' in settings &&
				'bundles' in this.settings &&
				settings.bundle in this.settings.bundles
			) {
				settings.url = this.settings.bundles[settings.bundle].url;
			} else {
				return $icon;
			}
		}

		// Decide whether to consider the icon standalone.
		if (settings.standalone === null) {
			// If the default value of null is found, assume it was not
			// specified.

			if (settings.text && settings.textDisplay === 'visible') {
				// Text is found and is visible, so we're not standalone.
				settings.standalone = false;
			} else {
				// Text not found, or is not visible, so we default to
				// standalone.
				settings.standalone = true;
			}
		}

		// Determine how to display the text.
		switch (settings.textDisplay) {
			case 'invisible':
				settings.containerClasses.push(
					containerBaseClass + '--text-invisible'
				);

				break;

			case 'hidden':
				settings.containerClasses.push(
					containerBaseClass + '--text-hidden'
				);

				break;
		}

		// Add the standalone class if explicitly set to true.
		if (settings.standalone === true) {
			settings.containerClasses.push(
				containerBaseClass + '--icon-standalone'
			);

			// If the bundle exists and has already been marked as loaded, add
			// the loaded class to the icon container.
			if (
				'bundle' in settings &&
				settings.bundle in aiIconLoad.bundleStates &&
				aiIconLoad.bundleStates[settings.bundle].loaded === true
			) {
				settings.containerClasses.push(
					containerBaseClass + '--icon-standalone-loaded'
				);
			}
		}

		// Add the bundle name class.
		if (
			'bundle' in settings &&
			settings.bundle in this.settings.bundles
		) {
			settings.containerClasses.push(
				containerBaseClass + '--bundle-' + settings.bundle
			);
		}

		// These are the placeholders and their replacement values. Note that
		// 'Placeholder' is automatically appended to the name to reduce
		// repetition.
		replacementValues = {
			'url'				: settings.url,
			'iconName'			: iconName,
			'text'				: settings.text,
			'containerTag'		: settings.containerTag,
			'size'				: settings.size
		};

		// Replace all occurances of each placeholder. See:
		// http://stackoverflow.com/a/1144788
		$.each(replacementValues, function(findValue, replaceValue) {
			template = template.replace(findValue + 'Placeholder', replaceValue);

			template = template.replace(
				new RegExp(findValue + 'Placeholder', 'g'),
				replaceValue
			);
		});

		// Render the template as a DOM element via jQuery. We filter out any
		// elements that aren't the icon, such as Twig debug comments from the
		// back-end.
		$icon = $(template).filter('.' + containerBaseClass);

		// Add any defined classes to the container, icon, and text elements.
		$icon.addClass(settings.containerClasses.join(' '));
		$icon.find('.' + containerBaseClass + '__icon')
			.addClass(settings.iconClasses.join(' '));
		$icon.find('.' + containerBaseClass + '__text')
			.addClass(settings.textClasses.join(' '));

		// Attempt to load the bundle if not already loaded or currently
		// loading, so that events can be fired on the document.
		if (settings.bundle) {
			aiIconLoad.loadBundle(settings.bundle);
		}

		// Return the rendered DOM element tree.
		return $icon;
	};
});
});
