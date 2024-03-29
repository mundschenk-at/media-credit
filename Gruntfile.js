'use strict';

// Which SASS implementation to use.
const sass = require('sass');

module.exports = function( grunt ) {
	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),

		wpversion: grunt.file.read( 'media-credit.php' ).toString().match(/Version:\s*([0-9](?:\w|\.|\-)*)\s|\Z/)[1],

		clean: {
			build: [ "build/**/*" ],
			autoloader: [
				"build/tests",
				"build/composer.*",
				"build/vendor-scoped/composer/*.json",
				"build/vendor-scoped/composer/InstalledVersions.php",
				"build/vendor-scoped/composer/installed.php",
				"build/vendor-scoped/scoper-autoload.php",
				"build/vendor-scoped/mundschenk-at/composer-for-wordpress/**",
				"build/vendor-scoped/dangoodman"
			]
		},

		// Replacement for grunt-composer
		exec: {
			composer_build: {
				command: "composer --quiet <%= grunt.task.current.args[0] %>",
				cwd: 'build'
			},
			composer_dev: {
				command: "composer <%= grunt.task.current.args[0] %>",
			}
		},

		"string-replace": {
        autoloader: {
            files: {
                "build/": "build/vendor-scoped/composer/autoload_{classmap,psr4,static}.php",
            },
            options: {
                replacements: [{
                    pattern: /\s+'Dangoodman\\\\ComposerForWordpress\\\\' =>\s+array\s*\([^,]+,\s*\),/g,
                    replacement: ''
                }, {
                    pattern: /\s+'Dangoodman\\\\ComposerForWordpress\\\\.*,(?=\n)/g,
                    replacement: ''
								}, {
	                  pattern: /\s+'Composer\\\\InstalledVersions.*,(?=\n)/g,
	                  replacement: ''
                }]
            }
        },
				"composer-vendor-dir": {
						options: {
								replacements: [{
										pattern: /"vendor-dir":\s*"vendor"/g,
										replacement: '"vendor-dir": "vendor-scoped"'
								}],
						},
						files: [{
								expand: true,
								flatten: false,
								src: ['build/composer.json'],
								dest: '',
						}]
				},
				"vendor-dir": {
						options: {
								replacements: [{
										pattern: /vendor\//g,
										replacement: 'vendor-scoped/'
								}],
						},
						files: [{
								expand: true,
								flatten: false,
								src: ['build/**/*.php'],
								dest: '',
						}]
				},
				namespaces: {
					options: {
						replacements: [{
							pattern: '', // Set later.
							replacement: `\$1Media_Credit\\Vendor\\\$2`
						}],
					},
					files: [{
						expand: true,
						flatten: false,
						src: ['build/includes/**/*.php'],
						dest: '',
					}]
				},

    },

		copy: {
			main: {
				files: [ {
					expand: true,
					nonull: true,
					src: [
						'readme.txt',
						'CHANGELOG.md',
						'LICENSE.md',
						'*.php',
						'includes/**',
						'admin/**',
						'public/**',
						'!**/scss/**',
						'vendor/**/partials/**',
					],
					dest: 'build/',
					rename: function(dest, src) {
						return dest + src.replace( /\bvendor\b/, 'vendor-scoped');
					}
				}	],
			},
			meta: {
				files: [ {
					expand: true,
					nonull: false,
					src: [
						'vendor/{composer,mundschenk-at,level-2}/**/LICENSE*',
						'vendor/{composer,mundschenk-at,level-2}/**/README*',
						'vendor/{composer,mundschenk-at,level-2}/**/CREDITS*',
						'vendor/{composer,mundschenk-at,level-2}/**/COPYING*',
						'vendor/{composer,mundschenk-at,level-2}/**/CHANGE*',
						'!vendor/composer/package-versions-deprecated/**',
					],
					dest: 'build/',
					rename: function(dest, src) {
							return dest + src.replace( /\bvendor\b/, 'vendor-scoped');
					}
				} ],
			}
		},

		rename: {
				vendor: {
						files: [{
								src: "build/vendor",
								dest: "build/vendor-scoped"
						}]
				}
		},

		wp_deploy: {
			options: {
				plugin_slug: 'media-credit',
				svn_url: "https://plugins.svn.wordpress.org/{plugin-slug}/",
				// svn_user: 'your-wp-repo-username',
				build_dir: 'build', //relative path to your build directory
				assets_dir: 'wp-assets', //relative path to your assets directory (optional).
				max_buffer: 1024 * 1024
			},
			release: {
				// nothing
				deploy_trunk: true,
				deploy_tag: true,
			},
			trunk: {
				options: {
					deploy_trunk: true,
					deploy_assets: true,
					deploy_tag: false,
				}
			},
			assets: {
				options: {
					deploy_assets: true,
					deploy_trunk: false,
					deploy_tag: false,
				}
			}
		},

		eslint: {
			src: [
				'admin/js/**/*.js',
				'public/js/**/*.js',
				'!**/*.min.js',
				'!admin/js/**/tinymce-noneditable.js'
			]
		},

		sass: {
			options: {
				implementation: sass,
			},
			dist: {
				options: {
					outputStyle: 'compressed',
					sourceComments: false,
					sourcemap: 'none',
				},
				files: [ {
					expand: true,
					cwd: 'admin/scss',
					src: [ '**/*.scss' ],
					dest: 'build/admin/css',
					ext: '.min.css'
				},
				{
					expand: true,
					cwd: 'public/scss',
					src: [ '**/*.scss' ],
					dest: 'build/public/css',
					ext: '.min.css'
				} ]
			},
			dev: {
				options: {
					outputStyle: 'expanded',
					sourceComments: false,
					sourceMapEmbed: true,
				},
				files: [ {
					expand: true,
					cwd: 'admin/scss',
					src: [ '**/*.scss' ],
					dest: 'admin/css',
					ext: '.css'
				},
				{
					expand: true,
					cwd: 'public/scss',
					src: [ '**/*.scss' ],
					dest: 'public/css',
					ext: '.css'
				} ]
			}
		},

		postcss: {
			options: {
				map: true, // inline sourcemaps.
				processors: [
					require('pixrem')(), // add fallbacks for rem units
					require('autoprefixer')() // add vendor prefixes
				]
			},
			dev: {
				files: [ {
					expand: true,
					cwd: 'admin/css',
					src: [ '**/*.css' ],
					dest: 'admin/css',
					ext: '.css'
				},
				{
					expand: true,
					cwd: 'public/css',
					src: [ '**/*.css' ],
					dest: 'public/css',
					ext: '.css'
				} ]
			},
			dist: {
				files: [ {
					expand: true,
					cwd: 'build/admin/css',
					src: [ '**/*.css' ],
					dest: 'build/admin/css',
					ext: '.css'
				},
				{
					expand: true,
					cwd: 'build/public/css',
					src: [ '**/*.css' ],
					dest: 'build/public/css',
					ext: '.css'
				} ]
			}
		},

		// uglify targets are dynamically generated by the minify task
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= ugtargets[grunt.task.current.target].filename %> <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
				report: 'min',
			},
		},

		minify: {
			dist: {
				expand: true,
				//dest: 'build/',
				files: grunt.file.expandMapping( [ 'admin/js/**/*.js', '!admin/js/**/*min.js', 'public/js/**/*.js', '!public/js/**/*min.js' ], 'build/', {
					rename: function(destBase, destPath) {
						return destBase + destPath.replace('.js', '.min.js');
					}
				})
			},
		},

		compress: {
			beta: {
				options: {
					mode: 'zip',
					archive: '<%= pkg.name %>-<%= wpversion %>.zip'
				},
				files: [{
						expand: true,
						cwd: 'build/',
						src: [ '**/*' ],
						dest: '<%= pkg.name %>/',
				}],
			}
		},

	});

	// Set correct pattern for naemspace replacement.
	grunt.config(
		'string-replace.namespaces.options.replacements.0.pattern',
		new RegExp( '([^\\w\\\\]|\\B\\\\?)((?:' + grunt.config('pkg.phpPrefixNamespaces').join('|') + ')\\\\[\\w_]+)', 'g' )
	);
	// load all tasks
	require( 'load-grunt-tasks' )( grunt, { scope: 'devDependencies' } );


	grunt.registerTask( 'default', [
			'newer:eslint',
			'newer:exec:composer_dev:phpcs',
			'newer:sass:dev',
			'newer:postcss:dev'
	] );

	grunt.registerTask( 'build', [
		// Clean house
		'clean:build',
		// Scope dependencies
		'exec:composer_dev:scope-dependencies',
		// Rename vendor directory
		'string-replace:composer-vendor-dir',
		'rename:vendor',
		// Generate stylesheets
		'newer:sass:dist',
		'newer:postcss:dist',
		'newer:minify',
		// Copy other files
		'copy:main',
		'copy:meta',
		// Use scoped dependencies
        'string-replace:namespaces',
		'exec:composer_build:build-wordpress',
		'clean:autoloader',
		'string-replace:vendor-dir',
		'string-replace:autoloader',
	] );

	grunt.registerTask( 'build-beta', [
			'build',
			'compress:beta',
	] );

	// dynamically generate uglify targets
	grunt.registerMultiTask('minify', function () {
		this.files.forEach(function (file) {
			var path = file.src[0],
			target = path.match(/([^.]*)\.js/)[1];

			// store some information about this file in config
			grunt.config('ugtargets.' + target, {
				path: path,
				filename: path.split('/').pop()
			});

			// create and run an uglify target for this file
			grunt.config('uglify.' + target + '.files', [{
				src: [path],
				dest: path.replace(/^(.*)\.js$/, 'build/$1.min.js')
			}]);
			grunt.task.run('uglify:' + target);
		});
	});

	grunt.registerTask('deploy', [
			'exec:composer_dev:phpcs',
			'eslint',
			'build',
			'wp_deploy:release'
	] );

	grunt.registerTask('trunk', [
			'exec:composer_dev:phpcs',
			'eslint',
			'build',
			'wp_deploy:trunk'
	] );

	grunt.registerTask('assets', [
			'clean:build',
			'copy',
			'wp_deploy:assets'
	] );

};
