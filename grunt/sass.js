// grunt-sass requires that we pass the Sass implementation in the options,
// which we cannot do via a YAML file. See:
// https://github.com/sindresorhus/grunt-sass/issues/288
module.exports = function(grunt, options) {
  'use strict';

  const sass = require('sass');

  return {
    module: {
      options: {
        implementation: sass,
        outputStyle:  'compressed',
        sourceMap:    true
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
