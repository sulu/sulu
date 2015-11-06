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
        'uglify'
    ]);

    grunt.registerTask('build', [
        // 'build:css', TODO uncomment if css is not empty
        'build:js'
    ]);
};
