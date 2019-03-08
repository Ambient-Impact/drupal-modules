/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Framework object utilities
----------------------------------------------------------------------------- */


(function() {
	'use strict';

	/**
	 * Check if a nested path of objects exists.
	 *
	 * @param {string} objectPath
	 *   The path of the object as a string.
	 *
	 * @param {object} rootObject
	 *   The root object to search for objectPath in. If not provided, defaults
	 *   to window.
	 *
	 * @return {bool}
	 *   True if the path exists, false if not.
	 */
	AmbientImpact.constructor.prototype.objectPathExists = function(
		objectPath, rootObject
	) {
		// If you don't pass a string, you get false.
		if (typeof objectPath !== 'string') {
			return false;
		}

		if (typeof rootObject === 'undefined') {
			rootObject = window;
		}

		var	objectPathArray = objectPath.split('.'),
			currentObject	= rootObject;

		// Remove the 'window' root object if found at the beginning of the
		// array and the root object is the window.
		if (
			objectPathArray.length > 0 &&
			objectPathArray[0] === 'window' &&
			rootObject === window
		) {
			objectPathArray.shift();
		}

		// Loop through the object path keys in order.
		for (var i = 0; i <= objectPathArray.length - 1; i++) {
			if (typeof currentObject[objectPathArray[i]] !== 'undefined') {
				// If the child object exists, set the current object to it and
				// continue down the chain.
				currentObject = currentObject[objectPathArray[i]];
			} else {
				// If the child object doesn't exist, return false.
				return false;
			}
		}

		// If the loop got all the way to the end, the full object path exists.
		return true;
	}
})();
