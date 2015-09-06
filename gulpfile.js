/*Load all plugin define in package.json*/
var browserify = require('browserify');
var gulp = require('gulp');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var uglify = require('gulp-uglify');
var sourcemaps = require('gulp-sourcemaps');
var gutil = require('gulp-util');
var cssmin = require('gulp-cssmin');

/**
 * Browserify
 */
gulp.task('javascript', function () {

    var b = browserify({
        entries: ['assets/js/src/main.js']
    } );

    return b.bundle()
        .pipe(source('app.js'))
        .pipe(buffer())
        .pipe(sourcemaps.init({loadMaps: true}))
        // Add transformation tasks to the pipeline here.
       // .pipe(uglify())
        .on('error', gutil.log)
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./assets/js/build/'));
});

gulp.task('css', function () {
   return gulp.src([
        'assets/css/src/sis-style.css'
    ])
        .pipe(cssmin())
        .pipe(gulp.dest('assets/css/build/'));
});

// On default task, just compile on demand
gulp.task('default', function () {
    gulp.watch(['assets/js/src/**/*.js'], ['javascript']);
    gulp.watch(['assets/css/src/**/*.css'], ['css']);
});