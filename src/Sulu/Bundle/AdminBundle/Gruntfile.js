module.exports = function(grunt) {

    var time = new Date().getTime();

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            rev: {
                files: [
                    {
                        src: ['Resources/public/dist/app.min.css'],
                        dest: 'Resources/public/dist/app.min.' + time + '.css'
                    },
                    {
                        src: ['Resources/public/dist/app.min.js'],
                        dest: 'Resources/public/dist/app.min.' + time + '.js'
                    },
                    {
                        src: ['Resources/public/dist/login.min.js'],
                        dest: 'Resources/public/dist/login.min.' + time + '.js'
                    },
                    {
                        src: ['Resources/public/dist/login.min.css'],
                        dest: 'Resources/public/dist/login.min.' + time + '.css'
                    }
                ]
            },
            build: {
                files: [
                    //copy fonts
                    {
                        expand: true,
                        cwd: 'Resources/public/js/vendor/husky/',
                        src: ['fonts/**'],
                        dest: 'Resources/public/dist/'
                    },
                    //only needed due to wrong path generation
                    {src: ['Resources/views/Admin/index.html.twig'], dest: 'Resources/public/index.html.twig'},
                    {src: ['Resources/views/Security/login.html.twig'], dest: 'Resources/public/login.html.twig'},
                    {expand: true, cwd: 'Resources/public', src: ['**'], dest: 'Resources/public/bundles/suluadmin/'},
                    // copy cultures
                    {
                        expand: true,
                        cwd: 'Resources/public/js/vendor/globalize',
                        src: ['cultures/**'],
                        dest: 'Resources/public/dist/vendor/globalize'
                    },
                    // copy files
                    {
                        expand: true,
                        cwd: 'Resources/public/js/vendor/husky/vendor',
                        src: ['*'],
                        dest: 'Resources/public/dist/vendor',
                        filter: 'isFile'
                    }

                ]
            },
            buildResult: {
                files: [
                    {src: ['Resources/public/index.html.twig'], dest: 'Resources/views/Admin/index.html.dist.twig'},
                    {src: ['Resources/public/login.html.twig'], dest: 'Resources/views/Security/login.html.dist.twig'}
                ]
            },
            bower: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/backbone-relational/backbone-relational.js'],
                        dest: 'Resources/public/js/vendor/backbone-relational'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/husky/dist/',
                        src: ['**'],
                        dest: 'Resources/public/js/vendor/husky/'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/globalize/lib/',
                        src: ['**'],
                        dest: 'Resources/public/js/vendor/globalize/'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/iban',
                        src: ['iban.js'],
                        dest: 'Resources/public/js/vendor/iban-converter/'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/require-css',
                        src: ['css.js'],
                        dest: 'Resources/public/js/vendor/require-css/'
                    },
                    {
                        expand: true,
                        cwd: 'bower_components/clipboard/dist',
                        src: ['clipboard.js'],
                        dest: 'Resources/public/js/vendor/clipboard/'
                    }
                ]
            }
        },
        clean: {
            options: {force: true},
            dist: {
                files: [
                    {src: ['Resources/public/dist']}
                ]
            },
            build: {
                files: [
                    {src: ['Resources/public/index.html.twig']},
                    {src: ['Resources/public/login.html.twig']},
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
        replace: {
            buildResult: {
                options: {
                    variables: {
                        'app.min': '/bundles/suluadmin/dist/app.min.' + time,
                        'login.min': '/bundles/suluadmin/dist/login.min.' + time,
                        'bundles/suluadmin/js/vendor/husky/husky.js': '/bundles/suluadmin/js/vendor/husky/husky.min.js?v=' + time,
                        'debug: true': 'debug: false'
                    },
                    prefix: ''
                },
                files: [
                    {
                        src: ['Resources/views/Admin/index.html.dist.twig'],
                        dest: 'Resources/views/Admin/index.html.dist.twig'
                    },
                    {
                        src: ['Resources/views/Security/login.html.dist.twig'],
                        dest: 'Resources/views/Security/login.html.dist.twig'
                    }
                ]
            },
            buildMain: {
                options: {
                    patterns: [
                        {
                            match: /v=develop/,
                            replacement: 'v=' + time.toString()
                        },
                        {
                            match: /\/js\/main\.js/,
                            replacement: '/dist/main.js'
                        }
                    ]
                },
                files: [
                    {src: ['Resources/public/dist/app.min.js'], dest: 'Resources/public/dist/app.min.js'}
                ]
            },
            buildLogin: {
                options: {
                    patterns: [
                        {
                            match: /v=develop/,
                            replacement: 'v=' + time.toString()
                        }
                    ]
                },
                files: [
                    {src: ['Resources/public/dist/login.min.js'], dest: 'Resources/public/dist/login.min.js'}
                ]
            }
        },
        requirejs: {
            app: {
                options: {
                    baseUrl: 'Resources/public/js/',
                    mainConfigFile: 'Resources/public/js/main.js',
                    preserveLicenseComments: false,
                    useStrict: false
                }
            },
            login: {
                options: {
                    baseUrl: 'Resources/public/js/',
                    mainConfigFile: 'Resources/public/js/login.js',
                    preserveLicenseComments: false,
                    out: 'Resources/public/dist/login.min.js',
                    useStrict: false
                }
            }
        },
        useminPrepare: {
            html: ['Resources/public/index.html.twig', 'Resources/public/login.html.twig'],
            options: {
                dest: 'Resources/public/dist'
            }
        },
        usemin: {
            html: ['Resources/public/index.html.twig', 'Resources/public/login.html.twig'],
            options: {
                basedir: '/bundles/suluadmin/dist'
            }
        },
        watch: {
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['compass']
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
            compress: {}
        },
        compass: {
            dev: {
                options: {
                    sourcemap: false,
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

    grunt.registerTask('build', [
        'clean:dist',
        'copy:build',
        'useminPrepare',
        'requirejs:app',
        'requirejs:login',
        'concat',
        'compass:dev',
        'cssmin',
        'usemin',
        'replace:buildMain',
        'replace:buildLogin',
        'copy:rev',
        'copy:buildResult',
        'replace:buildResult',
        'clean:build'
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
