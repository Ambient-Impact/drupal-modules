/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Icon support component
----------------------------------------------------------------------------- */

// This empty component is used as a dependency for the other icon components,
// so that they don't register if SVG is not supported.

AmbientImpact.onGlobals(['Modernizr.svg'], function() {
	// Don't register this component if Modernizr determines SVG is not
	// supported by the browser. This means bundle load/fail events will not
	// fire and icons cannot be generated either with aiIcon.get() or the jQuery
	// plugins. The CSS is designed so that standalone icons will be treated as
	// though their text were set to visible until the load class is added to
	// each icon, so text will still be visible when no icon is visible because
	// of lack of support.
	if (Modernizr.svg !== true) {
		return;
	}

	AmbientImpact.addComponent('icon.support', function(aiIconSupport, $) {
		'use strict';
	});
});
