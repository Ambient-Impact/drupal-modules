/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Component framework core
----------------------------------------------------------------------------- */


(function() {
	'use strict';

	/**
	 * Feature detection to determine if the browser cuts the mustard.
	 *
	 * @return {boolean}
	 *   True if the browser appears to have all the required features, false if
	 *   one or more aren't detected.
	 *
	 * @see http://responsivenews.co.uk/post/18948466399/cutting-the-mustard
	 */
	function mustard() {
		return (
			// framework.onComponents() requires Promise.all().
			'Promise' in window &&
			'all' in Promise &&

			// framework.on() requires this.
			'isArray' in Array &&

			// initComponent() requires this. Just about every browser has this,
			// but it was only added to IE in IE9, so exclude older versions.
			'indexOf' in Array.prototype
		);
	};

	// Create an empty framework that does nothing if the browser doesn't cut
	// the mustard and return so that the full framework is never defined.
	if (!mustard()) {
		var framework = function() {
			// An array of methods to create empty versions of that don't do
			// anything but return the framework instance (as expected).
			var emptyMethods = [
				'component', 'addComponent', 'registerComponent', 'on',
				'onComponent', 'onComponents',
			];

			// Expose the cut-the-mustard function so it can be used externally.
			// Replacing it is not recommended, as it can potentially break the
			// whole framework.
			this.mustard = mustard;

			for (var i = emptyMethods.length - 1; i >= 0; i--) {
				this[emptyMethods[i]] = function() {
					return this;
				};
			}
		};

		// Instantiate and make global.
		window.AmbientImpact = new framework('AmbientImpact');

		// Byyyyyye!
		return;
	}

	var
		// This is false until Drupal has attached behaviors once, at which
		// point this is set to true. This is used to attach behaviors in
		// framework.component.addBehaviors() if behaviors are added after
		// Drupal has already attached to the page.
		drupalHasAttached	= false;

	// Add a small Drupal behavior to determine when Drupal attaches behaviors
	// for the first time.
	Drupal.behaviors.AmbientImpactFrameworkAttachTest = {
		attach: function(context, settings) {
			drupalHasAttached = true;

			// Remove this behavior as its job is done.
			delete Drupal.behaviors['AmbientImpactFrameworkAttachTest'];
		}
	};

	/**
	 * Component framework.
	 *
	 * @param {string} drupalSettingsKey	- The key to look under
	 * 										  drupalSettings to fetch initial
	 * 										  component settings from, if found.
	 *
	 * @constructor
	 */
	var framework = function(drupalSettingsKey) {
		// Map of components, keyed by component machine name, structured like
		// so:

		// {
		//		componentMachineName1: {
		//			component:	<component constructor/object>,
		//			callbacks:	[<array of queued callbacks>],
		// 			registered:	<true if constructor has run, false otherwise>
		//		},
		// 		componentMachineName2: {
		// 			...
		// 		},
		// 		...
		// }
		var	components	= {},

		// An array of objects containing two keys:
		//   'names':	an array of component machine names that the Promise
		//   			should delay.
		//
		//   'promise':	a Promise used to delay the addition/registration of the
		//   			specified components.
		delayPromises	= [],

		// Save 'this' as a variable for when the scope may change in any
		// function below.
		thisFramework	= this;

		// Expose the cut-the-mustard function so it can be used externally.
		// Replacing it is not recommended, as it can potentially break the
		// whole framework.
		this.mustard = mustard;

		/**
		 * Register a Promise that delays component registration/initialization.
		 *
		 * @param {array|Promise} componentNames
		 *   The machine names of components to delay, as an array. If the array
		 *   is empty, the Promise will apply to all components. If this looks
		 *   like a Promise rather than an array, an empty array will be assumed
		 *   and this will be treated as the delayPromise. This allows passing
		 *   just the Promise to indicate all components.
		 *
		 * @param {Promise} delayPromise
		 *   The Promise that, when resolved, will initiate component
		 *   registration/initialization.
		 */
		this.delayComponents = function(componentNames, delayPromise) {
			if (
				'all' in componentNames &&
				typeof componentNames.all === 'function'
			) {
				delayPromise = componentNames;
			}

			if (!Array.isArray(componentNames)) {
				componentNames = [];
			}

			delayPromises.push({
				names:		componentNames,
				promise:	delayPromise
			});
		};

		/**
		 * Initialize a component.
		 *
		 * This creates the object structure for a component if not found, and
		 * if a constructor is passed, will execute it and mark the component as
		 * registered.
		 *
		 * @param {string} name				- The machine name of the component.
		 *
		 * @param {function} constuctor		- The constructor function for the
		 * 									  component which builds settings,
		 * 									  methods, etc.
		 *
		 * @see this.component
		 */
		function initComponent(name, constructor) {
			// Create the component object if it doesn't exist.
			if (!components.hasOwnProperty(name)) {
				components[name] = {
					component:	null,
					callbacks:	[],
					registered:	false
				};
			}

			if (typeof constructor !== 'function') {
				return;
			}

			var usePromises = [];

			// Build an array of delay Promises that apply to this component.
			for (var i = delayPromises.length - 1; i >= 0; i--) {
				if (
					// If the component machine names array for this Promise is
					// empty, this Promise applies to all components.
					delayPromises[i].names.length === 0 ||
					// If the machine name of this component is found, the
					// Promise applies.
					delayPromises[i].names.indexOf(name) > -1
				) {
					usePromises.push(delayPromises[i].promise);
				}
			}

			// Wait for any/all applicable registered delay promises to resolve
			// before initializing the component. This allows external code to
			// perform various tasks and conditionally delay the component until
			// said tasks complete. Promise.all() will resolve immediately if an
			// empty iterable is passed, so we don't have to check for that.
			// @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Promise/all#Return_value
			Promise.all(usePromises).then(function() {
				// Construct the component and save it. We can't rely on 'this'
				// here for whatever reason to point to the framework instance,
				// so use the saved variable.
				components[name].component =
					new thisFramework.component(name, constructor);

				// Mark as registered.
				components[name].registered = true;
			});
		};

		/**
		 * Determine if a component has been registered.
		 *
		 * @param {string} name				- The machine name of the component.
		 *
		 * @return {bool}					- Returns true if the component has
		 * 									  been registered, false otherwise.
		 *
		 * @see initComponent().
		 */
		function isComponentRegistered(name) {
			// Initialize in case the component hasn't been yet.
			initComponent(name);

			return components[name].registered;
		};

		/**
		 * Queue the passed callback on the specified component.
		 *
		 * @param {string} name				- The machine name of the component.
		 *
		 * @param {function} callback		- The function to queue on the
		 * 									  specified component.
		 *
		 * @see initComponent().
		 * @see fireComponentCallback().
		 */
		function queueComponentCallback(name, callback) {
			// Initialize in case the component hasn't been yet.
			initComponent(name);

			components[name].callbacks.push(callback);
		};

		/**
		 * Call the passed callback on the specified component(s).
		 *
		 * This function has two uses:
		 *   1. to fire component constructors
		 *   2. to fire callbacks when a component is registered
		 *
		 * @param {string|array|function} names	- A component machine name as a
		 * 										  string or an array of strings
		 * 										  to act on. If a function is
		 * 										  passed, it is assumed to be a
		 * 										  callback and 'this' is used to
		 * 										  get the component to act on.
		 *
		 * @param {function} callback			- The function to call on the
		 * 										  specified components. It is
		 * 										  passed all component objects
		 * 										  in specified order, with
		 * 										  jQuery as the last parameter.
		 * 										  The 'this' context is set to
		 * 										  the first component specified.
		 */
		function fireComponentCallback(names, callback) {
			var callbackArguments = [];

			switch (typeof names) {
				case 'string':
					names		= [names];

					break;

				case 'function':
					callback	= names;
					names		= [];

					break;
			}

			// If an array is passed, it will contain component machine names,
			// so add those to the callback arguments.
			if (
				names &&
				names.length &&
				names.length > 0
			) {
				for (var i = 0; i < names.length; i++) {
					callbackArguments.push(components[names[i]].component);
				}

			// If no array was passed, use 'this' as the first argument.
			} else {
				callbackArguments.push(this);
			}

			// Push jQuery onto the end of the arguments array.
			callbackArguments.push(jQuery);

			// Call the callback.
			callback.apply(callbackArguments[0], callbackArguments);
		}

		/**
		 * Get a given component's settings, or an empty object if not found.
		 *
		 * @param {string} componentName
		 *   The machine name of the component.
		 *
		 * @return {object}
		 *   If the given component name has settings under drupalSettings, this
		 *   will be that; if no settings are found, an empty object.
		 */
		this.getComponentSettings = function(componentName) {
			if (
				drupalSettingsKey in drupalSettings &&
				'components' in drupalSettings[drupalSettingsKey] &&
				componentName in drupalSettings[drupalSettingsKey].components
			) {
				return drupalSettings[drupalSettingsKey]
						.components[componentName];
			} else {
				return {};
			}
		}

		/**
		 * Component object. Instantiate this to create a new component.
		 *
		 * This is exposed as a method of the framework object so that it can
		 * be extended.
		 *
		 * @constructor
		 *
		 * @param {string} name				- The machine name of the component.
		 *
		 * @param {function} constuctor		- The constructor function for the
		 * 									  component which builds settings,
		 * 									  methods, etc.
		 *
		 * @see framework.addComponent().
		 */
		this.component = function(name, constructor) {
			var component = this;

			/**
			 * Get the machine name of this component.
			 *
			 * @return {string}
			 */
			this.getName = function() {
				return name;
			};

			// Initialize settings object.
			this.settings = thisFramework.getComponentSettings(name);

			/**
			 * Add Drupal behavior(s) to a component and attach them.
			 *
			 * Note that this uses "behaviour" in all cases except when
			 * referring to the Drupal.behaviors object, which must use the
			 * American "behavior".
			 *
			 * Note that in Drupal 8, the newer version of jQuery.once that's
			 * bundled changed its API so that you no longer pass a callback,
			 * but it functions as a filter plug-in, returning only elements that
			 * have not been processed yet.
			 *
			 * @param {object} behaviours
			 *   An object containing Drupal behaviors, as per the Drupal API.
			 *
			 * @see https://www.drupal.org/docs/8/api/javascript-api/javascript-api-overview
			 *   Examples of attaching and detaching behaviors with jQuery.once.
			 *
			 * @see https://github.com/RobLoach/jquery-once/blob/master/API.md
			 *   The new jQuery.once API in Drupal 8.
			 */
			this.addBehaviours = function(behaviours) {
				for (var behaviourName in behaviours) {
					if (
						behaviours.hasOwnProperty(behaviourName) &&
						behaviours[behaviourName].attach &&
						typeof behaviours[behaviourName].attach === 'function'
					) {
						// Add any defined behaviours to the Drupal object.
						Drupal.behaviors[behaviourName] =
							behaviours[behaviourName];

						// Attach this behaviour manually if we're being called
						// after Drupal has already attached other behaviours.
						if (drupalHasAttached === true) {
							Drupal.behaviors[behaviourName]
								.attach(document.body);
						}
					}
				}
			};

			/**
			 * Backwards-compatible alias for this.addBehaviours.
			 *
			 * @deprecated
			 */
			this.addBehaviors = this.addBehaviours;

			/**
			 * Add a single behaviour.
			 *
			 * This is a convenience method intended to reduce repetition and
			 * indent hell by generating a single Drupal behaviour based on the
			 * provided settings and callbacks.
			 *
			 * @param {string} behaviourName
			 *   The machine name of the behaviour object to generate.
			 *
			 * @param {string} onceName
			 *   The name to use for jQuery().once() and jQuery.removeOnce().
			 *
			 * @param {string} selector
			 *   The selector to use for jQuery().once() and
			 *   jQuery.removeOnce(). If omitted or an empty string, will only
			 *   use context for attaching/detaching.
			 *
			 * @param {array} detachTriggers
			 *   An array of triggers that will cause this behaviour to detach.
			 *   Defaults to ['unload'] if omitted. If an empty array is passed,
			 *   all triggers will cause detach to occur.
			 *
			 * @param {function} attach
			 *   The attach jQuery.once() callback; arguments are 'context' and
			 *   'settings', passed from the generated behaviour attach
			 *   callback. 'this' refers to the current element that
			 *   jQuery.once() is acting on.
			 *
			 * @param {function} detach
			 *   The detach jQuery.removeOnce() callback; arguments are
			 *   'context', 'settings', and 'trigger', passed from the generated
			 *   behaviour detach callback. 'this' refers to the current element
			 *   that jQuery.removeOnce() is acting on.
			 *
			 * @see this.addBehaviours()
			 */
			this.addBehaviour = function(
				behaviourName, onceName, selector, detachTriggers,
				attach, detach
			) {
				var	behaviour	= {},
					behaviours	= {};

				// If selector is not a string, assume it was omitted and shift
				// parameters accordingly.
				if (typeof selector !== 'string') {
					detach			= attach;
					attach			= detachTriggers;
					detachTriggers	= selector;

					selector		= '';
				}

				// If detachTriggers is a function, it's assumed to be the
				// attach callback; parameters are shifted.
				if (typeof detachTriggers === 'function') {
					detach			= attach;
					attach			= detachTriggers;
				}

				// If detachTriggers is not an array, set it to the default.
				// 'unload' is used because most components only need to be
				// detached when the page is unloading and not during
				// 'serialize', during which only some form items will need to
				// be detached.
				if (!Array.isArray(detachTriggers)) {
					detachTriggers	= ['unload'];
				}

				behaviour.attach = function(context, settings) {
					getBehaviourTargets(selector, context)
						.once(onceName).each(function() {
							attach.apply(this, [context, settings]);
						});
				};

				behaviour.detach = function(context, settings, trigger) {
					// If there are detach triggers defined but the current
					// trigger is not in the array, don't do anything. Note that
					// an empty detachTriggers will always detach.
					if (
						detachTriggers.length > 0 &&
						detachTriggers.indexOf(trigger) === -1
					) {
						return;
					}

					getBehaviourTargets(selector, context)
						.removeOnce(onceName).each(function() {
							detach.apply(this, [context, settings, trigger]);
						});
				};

				behaviours[behaviourName] = behaviour;

				// Add the generated behaviour.
				this.addBehaviours(behaviours);
			};

			/**
			 * Get the target elements of a behaviour.
			 *
			 * This uses the provided selector and context to determine what
			 * elements to collect and return.
			 *
			 * @param {string} selector
			 *   The selector to find within the context; can be empty.
			 *
			 * @param {object|HTMLElement} context
			 *   The context to search for the selector within.
			 *
			 * @return {jQuery}
			 *   A jQuery collection containing one of the following:
			 *
			 *   * If selector is not empty, the elements found matching the
			 *     selector within the context.
			 *
			 *   * If selector is empty and the context is the document, this
			 *     will be document.body.
			 *
			 *   * If the context is not the document, will be the context.
			 */
			function getBehaviourTargets(selector, context) {
				var $targets;

				if (selector.length > 0) {
					$targets = jQuery(selector, context);
				} else {
					$targets = jQuery(
						context === document ? document.body : context
					);
				}

				return $targets;
			};

			// Call the passed component constructor.
			fireComponentCallback.call(this, constructor);

			// Run any callbacks that are queued for this component.
			if (components.hasOwnProperty(name)) {
				for (var i = 0; i <= components[name].callbacks.length - 1; i++) {
					fireComponentCallback.call(this, components[name].callbacks[i]);
				}
			}

			return this;
		};

		/**
		 * Add/register a new component.
		 *
		 * @param {string} name				- The machine name of the component.
		 *
		 * @param {object|function} options	- Either a settings object
		 * 									  (currently unused), or a function
		 * 									  which is treated as the third
		 * 									  parameter, i.e. the component
		 * 									  constructor.
		 *
		 * @param {function} constuctor		- The constructor function for the
		 * 									  component which builds settings,
		 * 									  methods, etc.
		 *
		 * @return {object}					- Framework instance, for chaining.
		 *
		 * @see this.component
		 */
		this.addComponent = function(name, options, constructor) {
			// Allow passing the constructor as the second argument.
			if (typeof options === 'function') {
				constructor = options;
			}

			// Initialize the component.
			initComponent(name, constructor);

			// Return framework instance for chaining.
			return this;
		};

		/**
		 * Queue a callback to run when specified component(s) load.
		 *
		 * This is a convenience method for code brevity.
		 *
		 * @param {string|array} names	- A machine name of a component, as a
		 * 								  string, or an array of machine names
		 * 								  of components, as strings.
		 *
		 * @param {function} callback	- The function to call when the
		 * 								  specified component(s) load.
		 *
		 * @return {object}				- Framework instance, for chaining.
		 *
		 * @see framework.onComponent().
		 * @see framework.onComponents().
		 */
		this.on = function(name, callback) {
			if (typeof name === 'string') {
				return this.onComponent(name, callback);

			// See mustard() for Array.isArray() feature detection/requirement.
			} else if (Array.isArray(name)) {
				return this.onComponents(name, callback);
			}

			// Return framework instance for chaining if neither a string nor
			// an array are passed for the component name(s).
			return this;
		};

		/**
		 * Queue a callback to run when a single specified component loads.
		 *
		 * @param {string} name			- The machine name of the component.
		 *
		 * @param {function} callback	- The function to call when the
		 * 								  specified component loads.
		 *
		 * @return {object}				- Framework instance, for chaining.
		 */
		this.onComponent = function(name, callback) {
			// Is the component already available?
			if (isComponentRegistered(name)) {
				// Run right away if so.
				fireComponentCallback(name, callback);
			} else {
				// If not available, add it to the queue for if/when it is.
				queueComponentCallback(name, callback);
			}

			// Return framework instance for chaining.
			return this;
		};

		/**
		 * Queue a callback to run when all specified components load.
		 *
		 * @param {array} names			- Array of machine names of components.
		 *
		 * @param {function} callback	- The function to call when the
		 * 								  specified components load.
		 *
		 * @return {object}				- Framework instance, for chaining.
		 */
		this.onComponents = function(names, callback) {
			var	componentPromises	= [],
				instance			= this;

			for (var i = 0; i < names.length; i++) {
				componentPromises.push(new Promise(function(resolve, reject) {
					instance.onComponent(names[i], resolve);
				}));
			}

			Promise.all(componentPromises).then(function() {
				fireComponentCallback(names, callback);
			});

			// Return framework instance for chaining.
			return this;
		};
	};

	// Create a global instance. If you need to create your own instance, do:
	// var frameworkInstance = new AmbientImpact.constructor('settingsKey');
	// @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/constructor
	window.AmbientImpact = new framework('AmbientImpact');
})();
