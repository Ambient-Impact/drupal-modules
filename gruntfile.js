module.exports = function(grunt) {
  'use strict';

  var paths = {
    // Drupal extension directory names/paths relative to this Gruntfile.
    // Extensions are either modules or themes.
    extensions: [
      'ambientimpact_core',
      'ambientimpact_block',
      'ambientimpact_icon',
      'ambientimpact_media',
      'ambientimpact_ux',
      'ambientimpact_web/ambientimpact_web_components',
    ],
    // Stylesheet directory names/paths, relative to extension directories.
    stylesheets: [
      'stylesheets',
      'components',
    ],
    // JavaScript directory names/paths, relative to extension directories.
    javascript: [
      'javascript',
      'components',
    ],
    // Libraries path, relative to extension directories.
    libraries: [
      'assets/vendor',
    ],
  };

  for (const propertyName in paths) {
    if (!paths.hasOwnProperty(propertyName)) {
      continue;
    }

    // Convert each path into a string, joined with a comma if multiple items
    // are found.
    paths[propertyName] = paths[propertyName].join(',');

    // Add braces if a comma is found, so that expansion of multiple paths can
    // occur. Note that we have to do this conditionally because a set of braces
    // with a single string will not have braces expanded but output with the
    // braces, resulting in a path that won't match.
    //
    // @see https://www.gnu.org/software/bash/manual/html_node/Brace-Expansion.html
    // @see https://gruntjs.com/configuring-tasks#templates
    if (paths[propertyName].indexOf(',') > -1) {
      paths[propertyName] = '{' + paths[propertyName] + '}';
    }
  }

  // Load our Grunt task configs from external files in the 'grunt' directory.
  require('load-grunt-config')(grunt, {
    init: true,
    data: {
      extensionPaths:   paths.extensions,
      stylesheetPaths:  paths.stylesheets,
      javascriptPaths:  paths.javascript,
      librariesPath:    paths.libraries
    }
  });

  grunt.registerTask('all', [
    'sass',
    'postcss',
    //'sassdoc',
    'svgstore',
    'modernizr',
  ]);

  grunt.registerTask('css', [
    'sass',
    'postcss',
  ]);

  grunt.registerTask('icons', [
    'svgstore',
  ]);

};
