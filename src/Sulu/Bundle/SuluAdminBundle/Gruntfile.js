module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            main: {
                files: [
                    {expand: true, cwd: 'Resources/public', src: ['**'], dest: '../../../../../../web/bundles/suluadmin/'}
                ]
            }
        },
        watch: {
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['copy']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('default', ['watch']);
}