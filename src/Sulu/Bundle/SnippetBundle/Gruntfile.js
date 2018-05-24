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
                files: ['Resources/public/**', 'Resources/public_dev/**'],
                tasks: ['publish']
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
        replace: {
            build: {
                options: {
                    variables: {
                        'sulusnippet/js': 'sulusnippet/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        },
        cssmin: {
            // TODO: options: { banner: '<%= meta.banner %>' },
            compress: {
                files: {
                    'Resources/public/css/main.min.css': ['Resources/public/css/main.css']
                }
            }
        }
    });

    grunt.registerTask('default', [
        'watch'
    ]);

    grunt.registerTask('build:css', [
        'compass:dev',
        'cssmin'
    ]);

    grunt.registerTask('build:js', [
        'uglify',
        'replace:build'
    ]);

    grunt.registerTask('build', [
        'build:css',
        'build:js'
    ]);
};
