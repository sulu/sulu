module.exports = function(grunt) {
    var BUNDLE_NAME = 'suluroute',
        SOURCE_PATH = BUNDLE_NAME + '/js',
        DIST_PATH = BUNDLE_NAME + '/dist',
        replaceVariables = {},
        min = {},
        path = require('path'),
        srcpath = 'Resources/public/js',
        destpath = 'Resources/public/dist';

    // Build config "min" object dynamically.
    grunt.file.expand({cwd: srcpath}, '**/*.js').forEach(function(filename) {
        min[filename] = {
            src: path.join(srcpath, filename),
            dest: path.join(destpath, filename)
        };
    });

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    replaceVariables[SOURCE_PATH] = DIST_PATH;

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        uglify: min,
        clean: {
            js: [destpath]
        },
        copy: {
            templates: {
                files: [
                    {expand: true, cwd: srcpath, src: ['**/*.html'], dest: destpath}
                ]
            }
        },
        replace: {
            build: {
                options: {
                    variables: replaceVariables,
                    prefix: '',
                    force: true
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        },
        compass: {
            dev: {
                options: {
                    sassDir: 'Resources/scss/',
                    specify: ['Resources/scss/main.scss'],
                    cssDir: 'Resources/public/css/',
                    relativeAssets: false
                }
            }
        },
        cssmin: {
            compress: {
                files: {
                    'Resources/public/css/main.min.css': ['Resources/public/css/main.css']
                }
            }
        }
    });

    grunt.registerTask('build:js', [
        'clean',
        'uglify',
        'replace:build',
        'copy:templates'
    ]);

    grunt.registerTask('build', [
        'build:js'
    ]);

    grunt.registerTask('default', [
        'build'
    ]);
};
