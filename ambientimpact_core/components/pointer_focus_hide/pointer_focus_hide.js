/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Hide pointer focus component
----------------------------------------------------------------------------- */

// This hides the focus ring (outline) if a pointer was used to focus the
// element, but maintains focus and will show the outline if a user uses
// keyboard navigation.

// TO DO: when :focus-visible becomes better supported, deprecate this in favour
// of that? https://caniuse.com/#feat=css-focus-visible


AmbientImpact.onGlobals('ally.style.focusSource', function() {
AmbientImpact.addComponent('pointerFocusHide', function(
	aiPointerFocusHide, $
) {
	'use strict';

	// List of element selectors to watch. Is there a point to exposing this?
	this.elements = [
		'a', ':button', ':submit', ':reset', ':radio', ':checkbox',
		'[role="button"]', '[tabindex][tabindex!="-1"]'
	];

	// The class to apply to the above elements when a pointer was used to focus
	// them, hiding the outline. See pointer_focus_hide.scss.
	this.pointerFocusClass = 'pointer-focus-hide';

	// Initialize ally.js' focus source service.This is so that it starts
	// watching and applies the data-focus-source attribute to the <html>
	// element. Note that this updates only after a focus event, so we can't get
	// an accurate result in the focus handler but have to rely on the data
	// attribute in pointer_focus_hide.scss. See:

	// https://allyjs.io/api/style/focus-source.html
	var focusSourceHandle = ally.style.focusSource();

	// Lock the focus source to the current one. Note that you may have to use
	// setTimeout() to get an accurate focus source if calling this from within
	// a click event handler. See:
	// https://github.com/medialize/ally.js/issues/150#issuecomment-244898298
	this.lock = function() {
		focusSourceHandle.lock(focusSourceHandle.current());
	};
	// Unlock the focus source.
	this.unlock = function() {
		focusSourceHandle.unlock();
	};

	$('body').on('focus', this.elements.join(), function(event) {
		if (
			// If the data attribute has been set to true, always apply the
			// class.
			$(this).data('pointer-focus-hide') === true ||
			// If no data attribute, make sure this isn't a link that is set to
			// open in a new tab/window. Unless overridden by the data
			// attribute, these always show the focus outline to make it easier
			// for a user who clicked a link to see where they left off.
			$(this).attr('target') != '_blank'
		) {
			$(this).addClass(aiPointerFocusHide.pointerFocusClass);
		}
	})
	.on('blur', this.elements.join(), function(event) {
		$(this).removeClass(aiPointerFocusHide.pointerFocusClass)
	});
});
});
