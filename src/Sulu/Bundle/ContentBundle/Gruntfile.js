module.exports = function (grunt) {
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
            hooks: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: [
                            'bin/hooks/*'
                        ],
                        dest: '.git/hooks/'
                    }
                ]
            },
            templates: {
                files: [
                    {expand: true, cwd: srcpath, src: ['**/*.html'], dest: destpath}
                ]
            }
        },

        exec: {
            hookrights: {
                command: 'chmod +x .git/hooks/pre-push'
            }
        },

        clean: {
            options: { force: true },
            hooks: ['.git/hooks/*']
        },
        watch: {
            options: {
                nospawn: true
            },
            compass: {
                files: ['Resources/public/scss/{,*/}*.{scss,sass}'],
                tasks: ['publish']
            },
            scripts: {
                files: ['Resources/public/js/**/*.js'],
                tasks: ['publish']
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
                    'Resources/public/css/main.min.css': ['Resources/public/css/main.css']
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
        requirejs: {
            // TODO doesn´t work (combine anonymous module, same url)
            complete: {
                options: {
                    out: 'Resources/public/dist/content.js',
                    baseUrl: 'Resources/public/js/',
                    preserveLicenseComments: false,
                    paths: {
                        'sulucontent': './',
                        'type/default': '../AdminBundle/Resources/public/js/vendor/husky/husky',
                        'form/util': '../AdminBundle/Resources/public/js/vendor/husky/husky',
                        'app-config': '../AdminBundle/Resources/public/js/vendor/husky/husky',
                        'mvc/relationalmodel': '../AdminBundle/Resources/public/js/components/app-config/main',

                        'sulucontent/main': 'main',
                        'type/resourceLocator': 'validation/types/resourceLocator',
                        'type/textEditor': 'validation/types/textEditor',
                        'type/smartContent': 'validation/types/smartContent',

                        '__component__$content@sulucontent': 'components/content/main',
                        '__component__$content/components/form@sulucontent': 'components/content/components/form/main',
                        '__component__$content/components/content@sulucontent': 'components/content/components/content/main',
                        '__component__$content/components/column@sulucontent': 'components/content/components/column/main',
                        '__component__$content/components/list@sulucontent': 'components/content/components/list/main',
                        '__component__$content/components/split-screen@sulucontent': 'components/content/components/split-screen/main'
                    },
                    exclude: [
                        'type/default',
                        'form/util',
                        'app-config',
                        'mvc/relationalmodel'
                    ],
                    include: [
                        'sulucontent/main',
                        'type/resourceLocator',
                        'type/textEditor',
                        'type/smartContent',
                        '__component__$content@sulucontent',
                        '__component__$content/components/form@sulucontent',
                        '__component__$content/components/content@sulucontent',
                        '__component__$content/components/column@sulucontent',
                        '__component__$content/components/list@sulucontent',
                        '__component__$content/components/split-screen@sulucontent'
                    ]
                }
            }
        },
        uglify: min,
        replace: {
            build: {
                options: {
                    variables: {
                        'sulucontent/js': 'sulucontent/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        }
    });

    grunt.registerTask('build:css', [
        'compass:dev',
        'cssmin'
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

    grunt.registerTask('default', [
        'watch'
    ]);

    grunt.registerTask('install:hooks', [
        'clean:hooks',
        'copy:hooks',
        'exec:hookrights'
    ]);
};
