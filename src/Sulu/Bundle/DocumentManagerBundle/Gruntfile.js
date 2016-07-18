module.exports = function(grunt) {
    var BUNDLE_NAME = 'suludocumentmanager',
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
        }
    });

    grunt.registerTask('build:js', [
        'replace:build',
        'copy:templates',
        'uglify'
    ]);

    grunt.registerTask('build', [
        'build:js'
    ]);

    grunt.registerTask('default', [
        'build'
    ]);
};
