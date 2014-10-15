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
            public: {
                files: [
                    {
                        expand: true, 
                        cwd: 'Resources/public', 
                        src: ['**', '!**/scss/**'], 
                        dest: '../../../web/bundles/sulusnippet/'
                    }
                ]
            },
            public_dev: {
                files: [
                    {
                        expand: true, 
                        cwd: 'Resources/public_dev', 
                        src: ['**', '!**/scss/**'], 
                        dest: '../../../web/bundles/sulusnippet/'
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
                        src: ['../../../web/bundles/sulusnippet/']
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
        uglify: min
    });

    grunt.registerTask('publish', [
        'clean:public',
        'copy:public',
        'copy:public_dev'
    ]);

    grunt.registerTask('default', [
        'watch'
    ]);

    grunt.registerTask('build', [
        'uglify',
        'publish'
    ]);
};
