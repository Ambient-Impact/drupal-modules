/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Icon component
----------------------------------------------------------------------------- */

// This component only becomes available once the sub-components have loaded.
AmbientImpact.on([
	'icon.load', 'icon.get', 'icon.jquery',
], function(aiIconLoad, aiIconGet, aiIconjQuery) {
AmbientImpact.addComponent('icon', function(aiIcon, $) {
	'use strict';

	// Share settings with sub-components so that they have access to the same
	// settings as the this component. These are references, so any changes they
	// make to the settings will be synced.
	aiIconLoad.settings		= this.settings;
	aiIconGet.settings		= this.settings;
	aiIconjQuery.settings	= this.settings;

	// Expose sub-component methods as methods of this component.
	this.get		= aiIconGet.get;
	this.loadBundle	= aiIconLoad.loadBundle;
});
});
