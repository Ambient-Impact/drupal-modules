/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Component framework stub
----------------------------------------------------------------------------- */


(function() {
	'use strict';

	/**
	 * Component framework stub to capture method calls.
	 *
	 * This only captures calls to specific methods, so that any code that
	 * happens to load and execute before the full framework is loaded will have
	 * their calls registered and executed once the framework is running.
	 *
	 * @constructor
	 */
	var framework = function() {
		var
			// Stored calls to stub methods, to be used when the full framework
			// loads. Each key is the name of a method that was called, with the
			// value being an array of Arguments objects.
			storedCalls = {},
			// A list of methods to create stub versions of, so that they can
			// capture any calls and store them for when the full framework
			// loads.
			stubMethods	=  [
				'component', 'addComponent', 'on',
				'onComponent', 'onComponents', 'delayComponents',
			];

		/**
		 * Feature detection to determine if the browser cuts the mustard.
		 *
		 * This is exposed for use by other code, but replacing it is not
		 * recommended as it can potentially break the whole framework.
		 *
		 * @return {boolean}
		 *   True if the browser appears to have all the required features,
		 *   false if one or more aren't detected.
		 *
		 * @see http://responsivenews.co.uk/post/18948466399/cutting-the-mustard
		 */
		this.mustard = function() {
			return (
				// framework.onComponents() requires Promise.all().
				'Promise' in window &&
				'all' in Promise &&

				// framework.on() requires this.
				'isArray' in Array &&

				// initComponent() requires this. Just about every browser has
				// this, but it was only added to IE in IE9, so exclude older
				// versions.
				'indexOf' in Array.prototype
			);
		};

		/**
		 * Get the storedCalls object.
		 *
		 * @return {object}
		 *   The storedCalls object.
		 */
		this.getStoredCalls = function() {
			return storedCalls;
		};

		/**
		 * Create a method that stores calls under the given name.
		 *
		 * @param {string} methodName
		 *   The name of the method to store the calls of.
		 *
		 * @return {function}
		 *   A function that stores calls to it, under storedCalls[methodName].
		 */
		function makeMethod(methodName) {
			return function() {
				if (!(methodName in storedCalls)) {
					storedCalls[methodName] = [];
				}

				storedCalls[methodName].push(arguments);

				return this;
			};
		}

		// Create a stub method for every one listed in stubMethods.
		for (var i = 0; i < stubMethods.length; i++) {
			this[stubMethods[i]] = makeMethod(stubMethods[i]);
		}
	};

	// Instantiate and make global.
	window.AmbientImpact = new framework();

	window.AmbientImpact.on('test', function(aiTestComponent, $) {});
	window.AmbientImpact.addComponent('test', function(aiTestComponent, $) {});
	console.log(window.AmbientImpact.getStoredCalls());
})();
