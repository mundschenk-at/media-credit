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
	    wp_readme_to_markdown: {
	        readme: {
	            files: {
	              'README.md': 'readme.txt',
	            },
  	        },
  	        options: {
  	        	screenshot_url: '{screenshot}.png',
  	        }
	    },
		copy: {
			main: {
				files:[
					{expand: true, nonull: true, src: ['readme.txt','*.php'], dest: 'build/'},
					{expand: true, nonull: true, src: ['css/**','js/**','templates/**','translations/**'], dest: 'build/'},
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
	});

	grunt.registerTask( 'default', [
	    'wp_readme_to_markdown',
		'makepot',
    ]);

	grunt.registerTask( 'build', [
		'wp_readme_to_markdown',
		'copy',
  	]);

  	grunt.registerTask('deploy' ,[
  		'wp_readme_to_markdown',
  		'copy',
  		'wp_deploy'
  	]);
};
