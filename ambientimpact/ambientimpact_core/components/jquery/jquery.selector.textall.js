/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - jQuery selector :textall component
----------------------------------------------------------------------------- */

// @see http://markdalgleish.com/2011/05/jquery-selector-for-html5-input-types/


AmbientImpact.addComponent('jquery.selector.textall', function(
	aiSelectorTextAll, $
) {
	'use strict';

	var types = 'text password search number email datetime datetime-local date month week time tel url color range'.split(' '),
		len = types.length;

	$.expr[':']['textall'] = function(elem) {
		var type = elem.getAttribute('type');

		for (var i = 0; i < len; i++) {
			if (type === types[i]) {
				return true;
			}
		}
		return false;
	};
});
