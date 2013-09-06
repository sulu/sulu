module.exports = function (grunt) {

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            public: {
                files: [
                    {expand: true, cwd: 'Resources/public', src: ['**', '!**/scss/**'], dest: '../../../../../../web/bundles/suluadmin/'}
                ]
            },
            build: {
                files: [
                    //copy fonts
                    {expand: true, cwd: 'Resources/public/js/vendor/husky/', src: ['fonts/**'], dest: 'Resources/public/dist/'},
                    //only needed due to wrong path generation
                    {src: ['Resources/views/Admin/index.html.twig'], dest: 'Resources/public/index.html.twig'},
                    {expand: true, cwd: 'Resources/public', src: ['**'], dest: 'Resources/public/bundles/suluadmin/'}
                ]
            },
            buildResult: {
                files: [
                    {src: ['Resources/public/index.html.twig'], dest: 'Resources/views/Admin/index.html.dist.twig'}
                ]
            },
            bower: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/backbone/backbone.js', 'bower_components/backbone/backbone-min.js', 'bower_components/backbone/backbone-min.map'], dest: 'Resources/public/js/vendor/backbone'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/backbone-relational/backbone-relational.js'], dest: 'Resources/public/js/vendor/backbone-relational'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/husky/dist/',
                        src: ['**'],
                        dest: 'Resources/public/js/vendor/husky/'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/jquery/jquery.js', 'bower_components/jquery/jquery.min.map', 'bower_components/jquery/jquery.min.js'], dest: 'Resources/public/js/vendor/jquery'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/requirejs/require.js'], dest: 'Resources/public/js/vendor/requirejs'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/requirejs-text/text.js'], dest: 'Resources/public/js/vendor/requirejs-text'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/parsleyjs/parsley.js'], dest: 'Resources/public/js/vendor/parsleyjs'
                    },
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/underscore/underscore.js', 'bower_components/underscore/underscore-min.js', 'bower_components/underscore/underscore-min.map'], dest: 'Resources/public/js/vendor/underscore'
                    }
                ]
            }
        },
        clean: {
            options: { force: true },
            public: {
                files: [
                    {
                        dot: true,
                        src: ['../../../../../../web/bundles/suluadmin/']
                    }
                ]
            },
            dist: {
                files: [
                    {src: ['Resources/public/dist']}
                ]
            },
            build: {
                files: [
                    {src: ['Resources/public/index.html.twig']},
                    {src: ['Resources/public/bundles']}
                ]
            },
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
        replace: {  //FIXME: remove as soon as usemin bugs are fixed (leading slashes, basedir)
            buildResult: {
                options: {
                    variables: {
                        'app.min': '/bundles/suluadmin/dist/app.min',
                        'bundles/suluadmin': '/bundles/suluadmin'
                    },
                    prefix: ''
                },
                files: [
                    {src: ['Resources/views/Admin/index.html.dist.twig'], dest: 'Resources/views/Admin/index.html.dist.twig'}
                ]
            }
        },
        requirejs: {
            compile: {
                options: {
                    baseUrl: 'Resources/public/js/',
                    mainConfigFile: 'Resources/public/js/main.js',
                    preserveLicenseComments: false
                }
            }
        },
        rev: {
            dist: {
                files: {
                    src: [
                        'Resources/public/dist/app.min.css',
                        'Resources/public/dist/app.min.js'
                    ]
                }
            }
        },
        useminPrepare: {
            html: 'Resources/public/index.html.twig',
            options: {
                dest: 'Resources/public/dist'
            }
        },
        usemin: {
            html: ['Resources/public/index.html.twig'],
            options: {
                basedir: '/bundles/suluadmin/dist'
            }
        },
        watch: {
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['publish']
            },
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
                    'Resources/public/dist/main.min.css': ['Resources/public/css/main.css']
                }
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

    grunt.registerTask('publish', [
        'clean:public',
        'copy:public'
    ]);

    grunt.registerTask('build', [
        'clean:dist',
        'copy:build',
        'useminPrepare',
        'requirejs',
        'concat',
        'compass:dev',
        'cssmin',
        //'rev',    FIXME: use rev as soon as usemin can handle it correctly
        'usemin',
        'copy:buildResult',
        'replace:buildResult',
        'clean:build',
        'publish'
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
