module.exports = function(grunt) {

    var time = new Date().getTime();

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
            rev: {
                files: [
                    {
                        src: ['Resources/public/dist/app.min.css'],
                        dest: 'Resources/public/dist/app.min.' + time + '.css'
                    },
                    {
                        src: ['Resources/public/dist/app.min.js'],
                        dest: 'Resources/public/dist/app.min.' + time + '.js'
                    }
                ]
            },
            build: {
                files: [
                    //copy fonts
                    {expand: true, cwd: 'Resources/public/js/vendor/husky/', src: ['fonts/**'], dest: 'Resources/public/dist/'},
                    //only needed due to wrong path generation
                    {src: ['Resources/views/Admin/index.html.twig'], dest: 'Resources/public/index.html.twig'},
                    {expand: true, cwd: 'Resources/public', src: ['**'], dest: 'Resources/public/bundles/suluadmin/'},
                    // copy cultures
                    {expand: true, cwd: 'Resources/public/js/vendor/globalize', src: ['cultures/**'], dest: 'Resources/public/dist/vendor/globalize'}
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
                        src: ['bower_components/parsleyjs/parsley.js'], dest: 'Resources/public/js/vendor/parsleyjs'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/globalize/lib/',
                        src: ['**'],
                        dest: 'Resources/public/js/vendor/globalize/'
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
                        'app.min': '/bundles/suluadmin/dist/app.min.' + time,
                        'bundles/suluadmin/js/vendor/husky/husky.js': '/bundles/suluadmin/js/vendor/husky/husky.min.js'
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
        'usemin',
        'copy:rev',
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
