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

    grunt.registerTask('default', [
        'watch'
    ]);
};
