module.exports = function(grunt) {

  'use strict';

  const childProcess = require('child_process');

  const componentPaths = JSON.parse(childProcess.execSync(
    'drush ambientimpact:component-paths --providers=ambientimpact_*'
  ).toString().trim());

  let pathTemplates = {
    components: componentPaths,
  };

  for (const propertyName in pathTemplates) {

    if (!pathTemplates.hasOwnProperty(propertyName)) {
      continue;
    }

    // Convert each path into a string, joined with a comma if multiple items
    // are found.
    pathTemplates[propertyName] = pathTemplates[propertyName].join(',');

    // Add braces if a comma is found, so that expansion of multiple paths can
    // occur. Note that we have to do this conditionally because a set of braces
    // with a single string will not have braces expanded but output with the
    // braces, resulting in a path that won't match.
    //
    // @see https://www.gnu.org/software/bash/manual/html_node/Brace-Expansion.html
    // @see https://gruntjs.com/configuring-tasks#templates
    if (pathTemplates[propertyName].indexOf(',') > -1) {
      pathTemplates[propertyName] = '{' + pathTemplates[propertyName] + '}';
    }

  }

  // Load our Grunt task configs from external files in the 'grunt' directory.
  require('load-grunt-config')(grunt, {
    init: true,
    data: {
      componentPaths: componentPaths,
      pathTemplates:  pathTemplates,
    }
  });

  grunt.registerTask('all', [
    'sass',
    'postcss',
    'svgstore',
  ]);

  grunt.registerTask('css', [
    'sass',
    'postcss',
  ]);

  grunt.registerTask('icons', [
    'svgstore',
  ]);

  grunt.registerTask('default', ['all']);

};
