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
            },
            bower: {
                files: [
                    {
                        expand: true,
                        cwd: 'bower_components/wookmark-jquery',
                        src: ['wookmark.js'],
                        dest: 'Resources/public/js/vendor/wookmark/'
                    }
                ]
            }
        },
        clean: {
            options: {force: true},
            bower_after: {
                files: {
                    src: [
                        'bower_components'
                    ]
                }
            },
            bower_before: {
                files: {
                    src: [
                        'Resources/public/js/vendor'
                    ]
                }
            }
        },
        replace: {
            build: {
                options: {
                    variables: {
                        'sulumedia/js': 'sulumedia/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: [destpath + '/main.js'], dest: destpath + '/main.js'}
                ]
            }
        },
        watch: {
            options: {
                nospawn: true
            },
            compass: {
                files: ['Resources/public/scss/{,*/}*.{scss,sass}'],
                tasks: ['compass:dev']
            }
        },
        jshint: {
            options: {
                jshintrc: '.jshintrc'
            }
        },
        cssmin: {
            // TODO: options: { banner: '<%= meta.banner %>' },
            compress: {
                files: {
                    'dist/main.min.css': ['Resources/public/css/']
                }
            }
        },
        uglify: min,
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
        bower: {
            install: {
                options: {
                    copy: false,
                    layout: 'byComponent',
                    install: true,
                    verbose: false,
                    cleanTargetDir: false,
                    cleanBowerDir: false
                }
            }
        }
    });

    grunt.registerTask('build:css', [
        'compass:dev'
    ]);

    grunt.registerTask('build:js', [
        'uglify',
        'replace:build',
        'copy:templates'
    ]);

    grunt.registerTask('build', [
        'build:js',
        'build:css'
    ]);

    grunt.registerTask('update', [
        'clean:bower_before',
        'bower:install',
        'copy:bower',
        'clean:bower_after'
    ]);

    grunt.registerTask('default', [
        'watch'
    ]);
};
