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
	              'README.md': 'readme.txt'
	            },
	        },
	    },	    
	});

	grunt.registerTask( 'default', [
	    'wp_readme_to_markdown',
		'makepot',
    ]);

};
