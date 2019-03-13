/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Image link component
----------------------------------------------------------------------------- */


AmbientImpact.on(['jquery', 'link.file'], function(aijQuery, aiLinkFile) {
AmbientImpact.addComponent('link.image', function(aiLinkImage, $) {
	'use strict';

	// These are file extensions that will be considered as an image file when
	// evaluating link URLs in .isImageDestinationLink().
	this.extensions	= ['jpg', 'jpeg', 'png', 'gif', 'svg'];

	// These are the element selectors to test for in .isImageContainingLink().
	this.elements	= ['img', 'picture', 'svg:not(.material-ripple)', 'canvas'];

	// Text inside of these elements will be ignored in $().wrapImageLinkText().
	this.notWrapInElements = ['svg'];

	// This class is applied to links that point to a recognized image file
	// extension by the Drupal behavior.
	this.imageDestinationLinkClass = 'ambientimpact-is-image-link';

	// Classes for links that contain image elements.
	this.imageLinkClass		= 'ambientimpact-link-has-image';
	this.imageLinkTextClass	= 'ambientimpact-link-has-image__text';


	// Determine if a link points to a recognized image file.
	this.isImageDestinationLink = function(link) {
		return aiLinkFile.isFileDestinationLink(
			link,
			{
				extensions: aiLinkImage.extensions
			}
		);
	};


	// Determine if a link contains an image element.
	this.isImageContainingLink = function(link) {
		return !!$(link).has(this.elements.join()).length;
	};


	// jQuery plugin to wrap a link's text if it contains an image, to allow
	// styling just the text.
	$.fn.extend({
		wrapImageLinkText: function() {
			if (aiLinkImage.isImageContainingLink(this)) {
				$(this)
					// Find all child elements.
					.find('*')
					// Include parent link.
					.addBack()
					// Get all text nodes.
					.contents().textNodes()
						// Filter out any text nodes that are already wrapped,
						// and any text nodes that are within elements we're not
						// supposed to wrap within.
						.filter(function() {
							return (
								$(this).parents(
									'.' + aiLinkImage.imageLinkTextClass
								).length < 1 &&
								$(this).parents(
									aiLinkImage.notWrapInElements.join(',')
								).length < 1
							);
						})
						.wrap('<span class="' +
							aiLinkImage.imageLinkTextClass +
						'"></span>');
			}

			return this;
		},
		unwrapImageLinkText: function() {
			return this
					.find('.' + aiLinkImage.imageLinkTextClass)
						.contents()
							.unwrap();
		}
	});


	this.addBehaviors({
		AmbientImpactImageLink: {
			attach: function (context, settings) {
				var $links = $('a', context);

				for (var i = $links.length - 1; i >= 0; i--) {
					// Links that point to a recognized image file.
					if (aiLinkImage.isImageDestinationLink($links[i])) {
						$($links[i]).addClass(
							aiLinkImage.imageDestinationLinkClass
						);
					}
					// Links that contain a recognized image element.
					if (aiLinkImage.isImageContainingLink($links[i])) {
						$($links[i]).addClass(aiLinkImage.imageLinkClass);
					}
				}
			},
			detach: function (context, settings, trigger) {
				if (trigger !== 'unload') {
					return;
				}

				// Remove the classes from any links that have them.
				$('a.' + aiLinkImage.imageDestinationLinkClass, context)
					.removeClass(aiLinkImage.imageDestinationLinkClass);
				$('a.' + aiLinkImage.imageLinkClass, context)
					.removeClass(aiLinkImage.imageLinkClass);
			}
		}
	});

	// Drupal behaviour to mark links that point to a recognized image file
	// extension as external, opening in a new tab and not attempting to load
	// via Ajax pages.
	AmbientImpact.on('link.external', function(aiLinkExternal) {
		aiLinkImage.addBehaviors({
			AmbientImpactImageLinkExternal: {
				attach: function (context, settings) {
					var $links = $('a', context);

					for (var i = $links.length - 1; i >= 0; i--) {
						if (aiLinkImage.isImageDestinationLink($links[i])) {
							aiLinkExternal.makeLinkExternal($links[i]);
						}
					}
				},
				detach: function (context, settings, trigger) {
					if (trigger !== 'unload') {
						return;
					}

					var $links = $('a', context);

					for (var i = $links.length - 1; i >= 0; i--) {
						if (aiLinkImage.isImageDestinationLink($links[i])) {
							aiLinkExternal.detachFromLink($links[i]);
						}
					}
				}
			}
		});
	});
});
});
