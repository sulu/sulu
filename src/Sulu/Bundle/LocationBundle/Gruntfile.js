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
    require('matchdep').filterDev('grunt-*').forEach(function(name) {
        if ('grunt-cli' !== name) {
            grunt.loadNpmTasks(name);
        }
    });

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            templates: {
                files: [
                    {expand: true, cwd: srcpath, src: ['**/*.html'], dest: destpath}
                ]
            },
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
            },
            vendor: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: [
                            'Resources/public/js/vendor/leaflet/images/*',
                        ],
                        dest: 'Resources/public/dist/vendor/leaflet/images'
                    },
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
                    'Resources/public/css/main.min.css': ['Resources/public/css/main.css'],
                    'Resources/public/dist/vendor/leaflet/leaflet.css': ['Resources/public/js/vendor/leaflet/*.css'],
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
        'copy:bower',
        'copy:vendor',
        'copy:templates',
        'replace:build'
    ]);
};
