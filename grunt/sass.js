module.exports = function(grunt, options) {

  'use strict';

  // grunt-sass requires that we pass the Sass implementation in the options,
  // which we cannot do via a YAML file. See:
  // https://github.com/sindresorhus/grunt-sass/issues/288
  const sass = require('sass');

  const moduleImporter = require('sass-module-importer');

  // Make a copy of the component paths via Array.prototype.slice().
  let includePaths = options.componentPaths.slice();

  return {
    module: {
      options: {
        implementation: sass,
        importer:       moduleImporter(),
        includePaths:   includePaths,
        outputStyle:    'compressed',
        sourceMap:      true
      },
      files: [{
        src:
          '<%= extensionPaths %>/<%= stylesheetPaths %>/**/*.scss',
        ext:  '.css',
        extDot: 'last',
        expand: true,
      }]
    }
  };

};
