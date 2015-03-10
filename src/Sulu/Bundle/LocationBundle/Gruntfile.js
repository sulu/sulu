module.exports = function (grunt) {
    var min = {},
        path = require('path'),
        srcpath = 'Resources/public/js',
        destpath = 'Resources/public/dist';

    grunt.file.expand({cwd: srcpath}, '**/*.js').forEach(function(relpath) {
        min[relpath] = {
            src: path.join(srcpath, relpath),
            dest: path.join(destpath, relpath)
        };
    });

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            bower: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: [
                            'bower_components/leaflet/dist/leaflet.js',
                            'bower_components/leaflet/dist/leaflet.css',
                        ], 
                        dest: 'Resources/public/js/vendor/leaflet'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: [
                            'bower_components/leaflet/dist/images/*',
                        ], 
                        dest: 'Resources/public/js/vendor/leaflet/images'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: [
                            'bower_components/requirejs-plugins/src/async.js'
                        ], 
                        dest: 'Resources/public/js/vendor/requirejs-plugins'
                    }
                ]
            }
        },
        clean: {
            options: { force: true }
        },
        watch: {
            options: {
                nospawn: false
            },
            compass: {
                files: ['Resources/public/scss/{,*/}*.{scss,sass}'],
                tasks: ['compass:dev']
            },
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['publish']
            }
        },
        compass: {
            dev: {
                options: {
                    sassDir: 'Resources/public/scss/',
                    specify: ['Resources/public/scss/main.scss'],
                    cssDir: 'Resources/public/css/',
                    relativeAssets: false
                }
            }
        },
        replace: {
            build: {
                options: {
                    variables: {
                        'sululocation/js': 'sululocation/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        },
        cssmin: {
            compress: {
                files: {
                    'Resources/public/css/main.min.css': ['Resources/public/css/main.css']
                }
            }
        },
        uglify: min
    });

    grunt.registerTask('default', [
        'watch'
    ]);

    grunt.registerTask('build', [
        'uglify',
        'compass:dev',
        'cssmin',
        'copy:bower'
    ]);
};
