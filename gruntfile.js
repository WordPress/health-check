/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	var PATH_SCSS = [
			'assets/sass/**/*.scss'
		],

		PATH_JS = [
			'assets/javascript/**/*.js'
		],

		PATH_PHP = [
			'src/**/*'
		],

		autoprefixer = require( 'autoprefixer' ),

		matchdep = require( 'matchdep' ),

		scssStylelintConfig = require('stylelint-config-wordpress/scss.js');

	matchdep.filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),
		checktextdomain: {
			options: {
				text_domain: 'health-check',
				correct_domain: false,
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: [
					'src/**/*.php'
				],
				expand: true
			}
		},
		checkDependencies: {
			options: {
				packageManager: 'npm'
			},
			src: {}
		},
		copy: {
			files: {
				files: [
					{
						cwd: 'src/',
						src: '**/*',
						dest: 'build/',
						expand: true
					}
				]
			},
			documents: {
				cwd: 'docs/plugin/',
				src: [ '**/*', '!assets/*' ],
				dest: 'build/',
				expand: true
			},
			dependencies: {
				cwd: 'node_modules/clipboard/dist/',
				src: [ '**/*.min.js' ],
				dest: 'build/assets/javascript/',
				expand: true
			}
		},
		jscs: {
			src: PATH_JS,
			options: {
				config: '.jscsrc',
				fix: false // Autofix code style violations when possible.
			}
		},
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: [ 'gruntfile.js' ]
			},
			core: {
				expand: true,
				src: PATH_JS
			}
		},
		jsvalidate:{
			options:{
				globals: {},
				esprimaOptions:{},
				verbose: false
			},
			files: {
				src: PATH_JS
			}
		},
		postcss: {
			options: {
				map: false,
				processors: [
					autoprefixer({
						browsers: [ 'extends @wordpress/browserslist-config' ],
						cascade: false
					})
				],
				failOnError: false
			},
			healthcheck: {
				expand: true,
				src: [ 'build/assets/css/health-check.css', 'build/assets/css/health-check-troubleshooting-mode.css' ]
			}
		},
		sass: {
			healthcheck: {
				expand: true,
				ext: '.css',
				cwd: 'assets/sass/',
				dest: 'build/assets/css/',
				src: [ 'health-check.scss', 'health-check-troubleshooting-mode.scss' ],
				options: {
					indentType: 'tab',
					indentWidth: 1,
					outputStyle: 'expanded'
				}
			}
		},
		concat: {
			healthcheck: {
				src: [
					'assets/javascript/**/*.js'
				],
				dest: 'build/assets/javascript/health-check.js'
			}
		},
		stylelint: {
			scss: {
				options: {
					config: scssStylelintConfig,
					syntax: 'scss'
				},
				expand: true,
				src: PATH_SCSS
			}
		},
		watch: {
			config: {
				files: 'gruntfile.js'
			},
			sass: {
				files: PATH_SCSS,
				tasks: [ 'sass' ]
			},
			js: {
				files: PATH_JS,
				tasks: [ 'concat' ]
			},
			php: {
				files: PATH_PHP,
				tasks: [ 'copy' ]
			}
		},
		phpcs: {
			application: {
				src: [ 'src/**/*.php' ]
			},
			options: {
				bin: 'vendor/bin/phpcs --standard=phpcs.xml.dist',
				showSniffCodes: true
			}
		}
	});

	// CSS test task.
	grunt.registerTask( 'csstest', 'Runs all CSS tasks.', [ 'stylelint' ] );

	// JavaScript test task.
	grunt.registerTask( 'jstest', 'Runs all JavaScript tasks.', [ 'jsvalidate', 'jshint', 'jscs' ] );

	// PHP test task.
	grunt.registerTask( 'phptest', 'Runs all PHP tasks.', [ 'checktextdomain' ] );

	// Travis CI Task
	grunt.registerTask( 'travis', 'Runs Travis CI tasks.',[ 'csstest', 'jstest', 'phptest', 'phpcs' ] );

	// Build with all checks included
	grunt.registerTask( 'checkedBuild', 'Runs the build, and also runs linting etc against the code.', [ 'checkDependencies', 'copy', 'csstest', 'jstest', 'phptest', 'concat', 'sass', 'postcss' ] );

	// Default task.
	grunt.registerTask( 'default', [
		'checkDependencies',
		'copy',
		'concat',
		'sass'
	] );
};
