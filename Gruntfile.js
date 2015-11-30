'use strict';
module.exports = function(grunt) {

	// load all tasks
	require('load-grunt-tasks')(grunt, {scope: 'devDependencies'});

    grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
	    makepot: {
	        target: {
	            options: {
	                domainPath: '/translations/', // Where to save the POT file.
	                potFilename: 'media-credit.pot', // Name of the POT file.
	                type: 'wp-plugin',
	                exclude: ['build/.*'],
	                updateTimestamp: false
	            }
	        }
	    },
//	    phpunit: {
//	        classes: {
//	            options: {
//	            	testsuite: 'media-credit',
//	            }
//	        },
//	        options: {
//	            colors: true,
//	            configuration: 'phpunit.xml',
//	        }
//	    }
	    clean: {
	    	  build: ["build/*"]//,
	    	  //release: ["path/to/another/dir/one", "path/to/another/dir/two"]
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
				files:[
					{expand: true, nonull: true, src: ['readme.txt','*.php'], dest: 'build/'},
					{expand: true, nonull: true, src: ['admin/**','public/**','includes/**','translations/**'], dest: 'build/'},
				],
			}
		},
	    wp_deploy: {
	        deploy: { 
	            options: {
	                plugin_slug: 'media-credit',
//	                svn_user: 'your-wp-repo-username',  
	                build_dir: 'build', //relative path to your build directory
	                assets_dir: 'wp-assets' //relative path to your assets directory (optional).
	            },
	        }
	    },
        jshint: {
            files: [
                'admin/js/*.js',
                'public/js/**/*.js'
            ],
            options: {
                expr: true,
                globals: {
                    jQuery: true,
                    console: true,
                    module: true,
                    document: true
                }
            }
        },    
        sass: {
            dist: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.min.css <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
                    style: 'compressed'
                },
                files: [{
                    expand: true,
                    cwd: 'assets/scss',
                    src: [
                        '*.scss'
                    ],
                    dest: 'assets/css',
                    ext: '.min.css'
                }]
            },
            dev: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.css <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
                    style: 'expanded'
                },
                files: [{
                    expand: true,
                    cwd: 'assets/scss',
                    src: [
                        '*.scss'
                    ],
                    dest: 'assets/css',
                    ext: '.css'
                }]
            }
        },
        uglify: {
            dist: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.min.js <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
                    report: 'gzip'
                },
                files: grunt.file.expandMapping(['admin/js/**/*.js', 'public/js/**/*.js'], 'build/', {
                    rename: function(destBase, destPath) {
                        return destBase+destPath.replace('.js', '.min.js');
                    }
                })
            },
//            dev: {
//                options: {
//                    banner: '/*! <%= pkg.name %> <%= pkg.version %> filename.js <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
//                    beautify: true,
//                    compress: false,
//                    mangle: false
//                },
//                files: {
//                    'assets/js/filename.js' : [
//                        'assets/path/to/file.js',
//                        'assets/path/to/another/file.js',
//                        'assets/dynamic/paths/**/*.js'
//                    ]
//                }
//            }
        }
	});

	grunt.registerTask( 'default', [
	    'wp_readme_to_markdown',
		'makepot',
    ]);

	grunt.registerTask( 'build', [
		'wp_readme_to_markdown',
		'copy',
		'uglify:dist'
  	]);

  	grunt.registerTask('deploy' ,[
  		'wp_readme_to_markdown',
		'clean:build',
  		'copy',
		'uglify:dist',
  		'wp_deploy'
  	]);
};
