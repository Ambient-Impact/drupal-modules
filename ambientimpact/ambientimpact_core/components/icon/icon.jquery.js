/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Icon jQuery plugins component
----------------------------------------------------------------------------- */

AmbientImpact.on([
	'icon.support', 'icon.get', 'jquery',
], function(aiIconSupport, aiIconGet, aijQuery) {
AmbientImpact.addComponent('icon.jquery', function(aiIconjQuery, $) {
	'use strict';

	$.fn.extend({
		// jQuery plugin to wrap the text contents of the current element with
		// a specified icon and settings.
		wrapTextWithIcon: function(iconCode, settings) {
			// Grab the existing text and remove it from the DOM.
			settings.text = this.contents().textNodes().remove().text();

			// Return the element with the icon inserted.
			return this.append(aiIconGet.get(iconCode, settings));
		},
		// jQuery plugin to unwrap the current element's text contents from an
		// icon, if one exists.
		// @see jQuery.fn.wrapTextWithIcon().
		unwrapTextWithIcon: function() {
			var	$icon,
				containerBaseClass = aiIconGet.settings.containerBaseClass;

			if ($(this).is('.' + containerBaseClass)) {
				$icon = $(this);
			} else {
				$icon = $(this).find('.' + containerBaseClass);
			}

			if ($icon.length < 1) {
				return this;
			}

			$icon.find('.' + containerBaseClass + '__text').contents()
				.insertAfter($icon);

			$icon.remove();

			return this;
		}
	});
});
});
