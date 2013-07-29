module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            public: {
                files: [
                    {expand: true, cwd: 'Resources/public', src: ['**'], dest: '../../../../../../web/bundles/sulutranslate/'}
                ]
            }
        },
        clean: {
            public: {
                files: [
                    {
                        dot: true,
                        src: ['../../../../../../web/bundles/sulutranslate/']
                    }
                ]
            }
        },
        watch: {
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['publish']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');

    grunt.registerTask('publish', [
        'clean:public',
        'copy:public'
    ]);
};