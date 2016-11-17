module.exports = function(grunt) {
    var min = {},
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

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            templates: {
                files: [
                    {expand: true, cwd: srcpath, src: ['**/*.html'], dest: destpath}
                ]
            }
        },
        clean: {
            options: {force: true}
        },
        watch: {
            options: {
                nospawn: true
            },
            compass: {
                files: ['Resources/public/scss/{,*/}*.{scss,sass}'],
                tasks: ['build:css']
            },
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['build']
            }
        },
        jshint: {
            options: {
                jshintrc: '.jshintrc'
            }
        },
        uglify: min,
        replace: {
            build: {
                options: {
                    variables: {
                        'suluautomation/js': 'suluautomation/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: [destpath + '/main.js'], dest: destpath + '/main.js'}
                ]
            }
        }
    });

    grunt.registerTask('build', [
        'build:js'
    ]);

    grunt.registerTask('build:js', [
        'uglify',
        'replace:build',
        'copy:templates'
    ]);

    grunt.registerTask('default', [
        'watch'
    ]);
};
