// Ici c'est du javascript
module.exports = function(grunt) {
  grunt.initConfig({
    // On lit le fichier de package
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      scripts : {
       files: ['assets/js/*.js'],
       tasks: ['jshint:dev']
      }
    },
    jshint : {
      dev : {
        src: [ 'Gruntfile.js', 'assets/*.js' ],
        options: {
          // options here to override JSHint defaults
          globals: {
            jQuery: true,
            console: true,
            document: true
          }
        }
      }
    },
    uglify : {
      dist : {
        files: {
          'assets/js/sis.min.js': [
            'assets/js/sis.js',
          ],
          'assets/js/sis-attachments.min.js': [
            'assets/js/sis-attachments.js',
            
          ]
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-jshint');

  grunt.registerTask('default', ['watch:scripts'] );

  grunt.registerTask('dist', ['uglify:dist'] );
};