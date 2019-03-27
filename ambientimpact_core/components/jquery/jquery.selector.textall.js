/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - jQuery selector :textall component
----------------------------------------------------------------------------- */

// @see http://markdalgleish.com/2011/05/jquery-selector-for-html5-input-types/


AmbientImpact.addComponent('jquery.selector.textall', function(
	aiSelectorTextAll, $
) {
	'use strict';

	var types = [
		'text',
		'password',
		'search',
		'email',
		'tel',
		'url',
		'number',
		'range',

		// These are currently disabled until they can be verified and tested to
		// work with Drupal's custom form widgets:
		// 'datetime',
		// 'datetime-local',
		// 'date',
		// 'month',
		// 'week',
		// 'time',
		// 'color',
	],
	length = types.length;

	$.expr[':']['textall'] = function(elem) {
		var type = elem.getAttribute('type');

		// Starting from the end is allegedly faster?
		for (var i = length - 1; i >= 0; i--) {
			if (type === types[i]) {
				return true;
			}
		}

		return false;
	};
});
