module.exports = function(grunt) {
  'use strict';

  // Load our Grunt task configs from external files in the 'grunt' directory.
  require('load-grunt-config')(grunt, {
    init: true,
    data: {
      modulePaths:      [
        'ambientimpact_core',
        'ambientimpact_block',
        'ambientimpact_icon',
        'ambientimpact_media',
        'ambientimpact_ux',
        'ambientimpact_web/ambientimpact_web_components',
      ].join(','),

      stylesheetPaths:  'stylesheets,components',
      javascriptPaths:  'javascript,components',

      librariesPath:    'assets/vendor'
    }
  });

  grunt.registerTask('all', [
    'sass',
    'postcss',
    //'sassdoc',
    'svgstore',
  ]);

  grunt.registerTask('css', [
    'sass',
    'postcss',
  ]);
};
