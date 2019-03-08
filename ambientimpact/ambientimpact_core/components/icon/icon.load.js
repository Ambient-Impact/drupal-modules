/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Icon loading component
----------------------------------------------------------------------------- */

AmbientImpact.on('icon.support', function() {
AmbientImpact.addComponent('icon.load', function(aiIconLoad, $) {
	'use strict';

	/**
	 * An object of bundle states, keyed by bundle name.
	 *
	 * Each bundle value contains the following keys with boolean values:
	 *   - 'loaded':	true when the bundle has successfully loaded.
	 *   - 'loading':	true if a request is currently in progress to load the
	 *               	bundle.
	 *   - 'loadTried':	true if an attempt has been made to load the bundle.
	 *
	 * @type {Object}
	 */
	var bundleStates	= {};

	/**
	 * Reference to icon Drupal settings.
	 *
	 * @type {Object}
	 */
	var bundleSettings	= AmbientImpact.getComponentSettings('icon').bundles;

	// Expose bundle states.
	this.bundleStates	= bundleStates;

	// Build default bundle state keys and values.
	$.each(bundleSettings, function(bundleName, bundleData) {
		bundleStates[bundleName] = {
			loaded:		false,
			loading:	false,
			loadTried:	false
		};
	});

	/**
	 * Attempt to load a bundle, setting flags and firing events.
	 *
	 * This will initiate an Ajax request to load the specified bundle. If the
	 * bundle request is in progress or the bundle has already loaded
	 * successfully, calling this will do nothing. The following events are
	 * triggered on the document:
	 *
	 *   - 'IconBundleLoaded':
	 *       the icon bundle has loaded. Is passed the bundle name as the second
	 *       parameter.
	 *
	 *   - 'IconBundleLoadFailed':
	 *       the icon bundle has failed to load. Is passed the bundle name as
	 *       the second parameter.
	 *
	 * Additionally, a class will be added to the body for each bundle that
	 * successfully loads, in the format: 'icon-bundle-loaded--<bundle name>'
	 *
	 * @param {string} bundleName
	 *   The name of the bundle to attempt to load.
	 */
	function loadBundle(bundleName) {
		// Don't do anything if the bundle has been loaded or the request is
		// currently in progress.
		if (
			bundleStates[bundleName].loaded === true ||
			bundleStates[bundleName].loading === true
		) {
			return;
		}

		// Attempt to load the bundle via jQuery's Ajax functionality, firing
		// events and changing bundle flags on success, failure, and any
		// outcome.
		$.get(bundleSettings[bundleName].url)
		.done(function() {
			bundleStates[bundleName].loaded = true;

			$(document).trigger('IconBundleLoaded', [bundleName]);

			$('body').addClass('icon-bundle-loaded--' + bundleName);
		})
		.fail(function() {
			$(document).trigger('IconBundleLoadFailed', [bundleName]);
		})
		.always(function() {
			bundleStates[bundleName].loading	= false;
			bundleStates[bundleName].loadTried	= true;
		});

		bundleStates[bundleName].loading = true;
	}

	// Expose loadBundle() as a method.
	this.loadBundle = loadBundle;

	// Load any bundles that have been marked by the back end as being used on
	// the page via theme_ambientimpact_icon().

	// @todo Enable loading only bundles marked as used when backend usage
	// marking is reliable when JS aggregation is active. Currently loading all
	// available bundles regardless of usage.
	$.each(bundleSettings, function(bundleName, bundleData) {
		// if (bundleData.used === true) {
			loadBundle(bundleName);
		// }
	});

	/**
	 * Mark icons within the provided context as loaded.
	 *
	 * @param {object|HTMLElement} context
	 *   The context to search for icons within.
	 *
	 * @param {string} bundleName
	 *   The bundle name whose icons we need to mark as loaded.
	 */
	function markIconsLoaded(context, bundleName) {
		var containerBaseClass = aiIconLoad.settings.containerBaseClass;

		$(context).find(
			'.' + containerBaseClass + '--icon-standalone' +
			'.' + containerBaseClass + '--bundle-' + bundleName
		).addClass(containerBaseClass + '--icon-standalone-loaded');
	};

	// When each bundle loads, find all existing icons of that bundle and mark
	// them as loaded.
	$(document).on('IconBundleLoaded.aiIcon', function(event, bundleName) {
		markIconsLoaded(document.body, bundleName);
	});

	// Define a Drupal behaviour to mark icons as loaded. This is necessary so
	// that standalone icons are properly marked as loaded when inserted via
	// Drupal's Ajax framework.
	this.addBehaviour(
		'AmbientImpactIconLoad',
		'ambientimpact-icon-load',
		function(context, settings) {
			$.each(bundleStates, function(bundleName, bundleState) {
				if (bundleState.loaded === true) {
					markIconsLoaded(context, bundleName);
				}
			});
		},
		function(context, settings, trigger) {
			// Do we need a detach?
		}
	);
});
});
