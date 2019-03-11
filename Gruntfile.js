'use strict';
const sass = require('node-sass');

module.exports = function( grunt ) {
	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),

		clean: {
			build: [ "build/*" ],
			autoloader: [ "build/tests", "build/composer.*", "build/vendor/composer/*.json", "build/vendor/mundschenk-at/composer-for-wordpress/**" ]
		},

		composer: {
			build: {
					options: {
							//flags: ['quiet'],
							cwd: 'build',
					},
			},
			dev: {
					options : {
							flags: [],
							cwd: '.',
					},
			},
		},

		"string-replace": {
        autoloader: {
            files: {
                "build/": "build/vendor/composer/autoload_{classmap,psr4,static}.php",
            },
            options: {
                replacements: [{
                    pattern: /\s+'Dangoodman\\\\ComposerForWordpress\\\\' =>\s+array\s*\([^,]+,\s*\),/g,
                    replacement: ''
                }, {
                    pattern: /\s+'Dangoodman\\\\ComposerForWordpress\\\\.*,(?=\n)/g,
                    replacement: ''
                }]
            }
        }
    },

		wp_readme_to_markdown: {
			readme: {
				files: {
					'README.md': 'readme.txt',
				},
			},
			options: {
				screenshot_url: 'wp-assets/{screenshot}.png',
			}
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
						'admin/**',
						'public/**',
						'includes/**',
						'!**/scss/**',
						'composer.*',
						'vendor/composer/**',
						'vendor/mundschenk-at/check-wp-requirements/*.php',
						'vendor/mundschenk-at/check-wp-requirements/partials/*.php',
						'vendor/mundschenk-at/wp-data-storage/src/**/*.php',
						'vendor/mundschenk-at/wp-settings-ui/src/**/*.php',
						'vendor/mundschenk-at/wp-settings-ui/partials/**/*.php',
						'vendor/level-2/dice/**/*.php',
						'!**/scss/**',
						'!**/tests/**'
					],
					dest: 'build/'
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
					],
					dest: 'build/'
				} ],
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

		phpcs: {
			plugin: {
				src: ['includes/**/*.php', 'admin/**/*.php', 'public/**/*.php']
			},
			options: {
				bin: 'vendor/bin/phpcs -p -s -v -n ',
				standard: './phpcs.xml'
			}
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
	});

	// load all tasks
	require( 'load-grunt-tasks' )( grunt, { scope: 'devDependencies' } );


	grunt.registerTask( 'default', [
			'newer:wp_readme_to_markdown',
			'newer:eslint',
			'newer:phpcs',
			'newer:sass:dev',
			'newer:postcss:dev'
	] );

	grunt.registerTask( 'build', [
			'newer:wp_readme_to_markdown',
			'clean:build',
			'newer:sass:dist',
			'newer:postcss:dist',
			'newer:minify',
			'copy:main',
			'copy:meta',
			'composer:build:build-wordpress',
			'composer:build:dump-autoload:classmap-authoritative:no-dev',
			'clean:autoloader',
			'string-replace:autoloader',
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
			'phpcs',
			'eslint',
			'build',
			'wp_deploy:release'
	] );

	grunt.registerTask('trunk', [
			'wp_readme_to_markdown',
			'phpcs',
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
