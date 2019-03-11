module.exports = function(grunt) {

	grunt.initConfig({
		pkg:				grunt.file.readJSON('package.json'),

		modulePaths:		'ambientimpact_core,' +
							'ambientimpact_block',
		stylesheetPaths:	'stylesheets,components',
		javascriptPaths:	'javascript,components',

		librariesPath:		'assets/vendor',

		sass: {
			module: {
				options: {
					outputStyle:	'compressed',
					sourceMap:		true
				},
				files: [{
					src:
						'{<%= modulePaths %>}/{<%= stylesheetPaths %>}/**/' +
						'*.scss',
					ext:	'.css',
					extDot:	'last',
					expand:	true,
				}]
			}
		},
		autoprefixer: {
			module: {
				options: {
					map: true
				},
				files: [{
					src:
						'{<%= modulePaths %>}/{<%= stylesheetPaths %>}/**/' +
						'*.css',
					ext:	'.css',
					extDot:	'last',
					expand:	true,
				}]
			}
		},
		modernizr: {
			dist: {
				dest:
					'ambientimpact_core/<%= librariesPath %>/modernizr/' +
					'modernizr.min.js',
				crawl:	false,
				uglify:	true,
				tests:	[
					// Used by several things, including icons.
					'svg',
					// This is needed to know if we can afford to bind to
					// transitionend events. If these don't fire, we could end
					// up with various stuff in unusable states.
					'csstransitions',
					// These are required by Drupal core.
					'details',
					'inputtypes',
					'touchevents'
				],
				options	: [
					'domPrefixes',
					'prefixed',
					'html5shiv',
					'mq',
					// These are required by Drupal core.
					'addtest',
					'prefixes',
					'setClasses',
					'teststyles'
				]
			}
		},
		uglify: {
			module: {
				options: {
					compress: {
						// Note that this removes all console.* calls, not just
						// console.log():
						// https://github.com/gruntjs/grunt-contrib-uglify#turn-off-console-warnings
						drop_console: true
					}
				},
				src:	[
					'{<%= modulePaths %>}/{<%= javascriptPaths %>}/**/*.js',
					'!**/*.min.js'
				],
				ext:	'.min.js',
				extDot:	'last',
				expand:	true,
			}
		},
		svgstore: {
			icons_core: {
				options: {
					prefix: 'icon-',
				},
				files: {
					'ambientimpact_core/icons/core.svg': [
						'ambientimpact_core/icons/core/*.svg'
					]
				}
			},
			icons_brands: {
				options: {
					prefix: 'icon-',
				},
				files: {
					'ambientimpact_core/icons/brands.svg': [
						'ambientimpact_core/icons/brands/*.svg'
					]
				}
			},
			icons_libricons: {
				options: {
					prefix: 'icon-',
				},
				files: {
					'ambientimpact_core/icons/libricons.svg': [
						'ambientimpact_core/icons/libricons/*.svg'
					]
				}
			},
			icons_photoswipe: {
				options: {
					prefix: 'icon-',
				},
				files: {
					'ambientimpact_core/components/photoswipe/icons/photoswipe.svg': [
						'ambientimpact_core/components/photoswipe/icons/photoswipe/*.svg'
					]
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-modernizr');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-svgstore');

	grunt.registerTask('all', [
		'sass',
		'autoprefixer',
		'uglify',
		'svgstore',
	]);

	grunt.registerTask('css', [
		'sass',
		'autoprefixer',
	]);
};
