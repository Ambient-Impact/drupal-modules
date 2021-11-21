module.exports = function(grunt, options) {

  'use strict';

  return {
    module: {
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
