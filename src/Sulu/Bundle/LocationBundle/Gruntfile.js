module.exports = function (grunt) {

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            public: {
                files: [
                    {
                        expand: true, 
                        cwd: 'Resources/public', 
                        src: ['**', '!**/scss/**'], 
                        dest: '../../../../../../web/bundles/sululocation/'
                    }
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
            }
        },
        clean: {
            options: { force: true },
            public: {
                files: [
                    {
                        dot: true,
                        src: ['../../../../../../web/bundles/sululocation/']
                    }
                ]
            }
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
        }
    });

    grunt.registerTask('publish', [
        'compass:dev',
        'clean:public',
        'copy:bower',
        'copy:public'
    ]);

    grunt.registerTask('default', [
        'watch'
    ]);

    grunt.registerTask('build', [
        'publish'
    ]);
};
