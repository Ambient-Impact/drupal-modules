module.exports = function(grunt) {
  'use strict';

  // Load our Grunt task configs from external files in the 'grunt' directory.
  var configs = require('load-grunt-configs')(grunt, {
    config: {
      src: 'grunt/*'
    }
  });

  // Initialize the Grunt config with the read config plus the package and paths
  // merged in.
  grunt.initConfig(Object.assign(configs, {
    pkg:              grunt.file.readJSON('package.json'),

    modulePaths:      [
      'ambientimpact_core',
      'ambientimpact_block',
      'ambientimpact_media',
      'ambientimpact_ux',
    ].join(','),

    stylesheetPaths:  'stylesheets,components',
    javascriptPaths:  'javascript,components',

    librariesPath:    'assets/vendor'
  }));

  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-sassdoc');
  grunt.loadNpmTasks('grunt-autoprefixer');
  grunt.loadNpmTasks('grunt-modernizr');
  grunt.loadNpmTasks('grunt-svgstore');

  grunt.registerTask('all', [
    'sass',
    'autoprefixer',
    'sassdoc',
    'svgstore',
  ]);

  grunt.registerTask('css', [
    'sass',
    'autoprefixer',
  ]);
};
