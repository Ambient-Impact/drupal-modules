module.exports = function(grunt, options) {

  'use strict';

  return {
    modules: {
      options: {
        map: {
          inline: false
        },
        processors: [
          require('autoprefixer')
        ]
      },
      files: [{
        src:
          '<%= pathTemplates.components %>/**/*.css',
        ext:  '.css',
        extDot: 'last',
        expand: true,
      }]
    }
  };

};
