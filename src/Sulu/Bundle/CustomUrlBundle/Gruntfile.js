module.exports = function(grunt) {
    var BUNDLE_NAME = 'sulucustomurl',
        SOURCE_PATH = BUNDLE_NAME + '/js',
        DIST_PATH = BUNDLE_NAME + '/dist',
        replaceVariables = {},
        min = {},
        path = require('path'),
        srcpath = 'Resources/public/js',
        destpath = 'Resources/public/dist';

    // Build config "min" object dynamically.
    grunt.file.expand({cwd: srcpath}, '**/*.js').forEach(function(relpath) {
        // Create a target Using the verbose "target: {src: src, dest: dest}" format.
        min[relpath] = {
            src: path.join(srcpath, relpath),
            dest: path.join(destpath, relpath)
        };
        // The more compact "dest: src" format would work as well.
        // min[path.join(destpath, relpath)] = path.join(srcpath, relpath);
    });

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    replaceVariables[SOURCE_PATH] = DIST_PATH;

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        uglify: min,
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
                    prefix: ''
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        },
        compass: {
            main: {
                options: {
                    sassDir: 'Resources/public/scss/',
                    specify: ['Resources/public/scss/main.scss'],
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
        'copy:templates',
        'uglify',
        'replace:build'
    ]);

    grunt.registerTask('build:css', [
        'compass',
        'cssmin'
    ]);

    grunt.registerTask('build', [
        'build:js',
        'build:css'
    ]);

    grunt.registerTask('default', [
        'build'
    ]);
};
